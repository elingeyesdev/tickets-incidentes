<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Requests\EmailVerifyRequest;
use App\Features\Authentication\Http\Resources\EmailVerificationStatusResource;
use App\Features\Authentication\Http\Resources\EmailVerificationResultResource;
use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Exceptions\TokenInvalidException;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Email Verification Controller
 *
 * REST endpoints para verificación de email.
 * Flujo:
 * 1. POST /email/verify → Verificar email con token
 * 2. POST /email/verify/resend → Reenviar email (requiere auth)
 * 3. GET /email/status → Ver estado de verificación (requiere auth)
 */
class EmailVerificationController
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Verify email
     *
     * Verifica el email del usuario usando un token.
     * El token identifica al usuario automáticamente.
     * Siempre retorna 200, pero success puede ser false.
     *
     * @authenticated false
     * @response 200 {"success": true, "message": "..."}
     */
    #[OA\Post(
        path: '/api/auth/email/verify',
        summary: 'Verify email',
        description: 'Verify user email with token. Always returns 200.',
        tags: ['Email Verification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Verification result (check success field)'),
        ]
    )]
    public function verify(EmailVerifyRequest $request): JsonResponse
    {
        try {
            $token = $request->input('token');

            // Verificar email usando token (replicar exactamente VerifyEmailMutation)
            $user = $this->authService->verifyEmail($token);

            \Illuminate\Support\Facades\Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Email verificado exitosamente! Ya puedes usar todas las funciones del sistema.',
                'canResend' => false,
                'resendAvailableAt' => null,
            ], 200);

        } catch (AuthenticationException $e) {
            // Error de verificación (token inválido, expirado, email ya verificado)
            \Illuminate\Support\Facades\Log::warning('Email verification failed', [
                'token_preview' => substr($request->input('token', ''), 0, 10) . '...',
                'error' => $e->getMessage(),
            ]);

            // Retornar error como resultado (no throw - compatible con cliente)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'canResend' => true,
                'resendAvailableAt' => now()->toIso8601String(),
            ], 200);
        }
    }

    /**
     * Resend verification email
     *
     * Reenvía el email de verificación al usuario autenticado.
     * Rate limited: 3 intentos cada 5 minutos.
     * Siempre retorna success (no revela si ya verificado).
     *
     * @authenticated true
     * @response 200 {"success": true, "message": "...", "canResend": false, "resendAvailableAt": "..."}
     */
    #[OA\Post(
        path: '/api/auth/email/verify/resend',
        summary: 'Resend verification email',
        description: 'Resend verification email to authenticated user (rate limited: 3 per 5 minutes)',
        tags: ['Email Verification'],
        responses: [
            new OA\Response(response: 200, description: 'Email resent'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 429, description: 'Rate limit exceeded'),
        ]
    )]
    public function resend(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Replicar exactamente ResendVerificationMutation

            // Verificar que el usuario no esté ya verificado
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El email ya está verificado',
                    'canResend' => false,
                    'resendAvailableAt' => null,
                ], 200);
            }

            // Reenviar verificación (AuthService dispara evento UserRegistered que envía email)
            $token = $this->authService->resendEmailVerification($user->id);

            \Illuminate\Support\Facades\Log::info('Email verification resent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email de verificación enviado correctamente. Revisa tu bandeja de entrada.',
                'canResend' => false,
                'resendAvailableAt' => now()->addMinutes(5)->toIso8601String(),
            ], 200);

        } catch (AuthenticationException $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to resend verification email', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Retornar error como resultado (no throw - compatible con cliente)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'canResend' => true,
                'resendAvailableAt' => now()->addMinute()->toIso8601String(),
            ], 200);
        }
    }

    /**
     * Get email verification status
     *
     * Obtiene el estado de verificación de email del usuario autenticado.
     * Retorna si está verificado, cuándo se envió el email, etc.
     *
     * @authenticated true
     * @response 200 {"isVerified": false, "email": "...", "verificationSentAt": "...", ...}
     */
    #[OA\Get(
        path: '/api/auth/email/status',
        summary: 'Get email verification status',
        description: 'Get email verification status for authenticated user',
        tags: ['Email Verification'],
        responses: [
            new OA\Response(response: 200, description: 'Status retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Replicar exactamente EmailVerificationStatusQuery

            // Obtener status del servicio
            $status = $this->authService->getEmailVerificationStatus($user->id);

            // Calcular si puede reenviar (rate limit: 3 intentos cada 5 minutos)
            // Por simplicidad, siempre puede reenviar si no está verificado
            // El rate limit real lo maneja el middleware
            $canResend = !$status['is_verified'];
            $resendAvailableAt = $canResend ? now() : null;

            // Agregar campos computados (como los calcula la query)
            $statusWithComputed = [
                'is_verified' => $status['is_verified'],
                'email' => $status['email'],
                'verified_at' => $status['verified_at'] ?? $user->created_at,
                'can_resend' => $canResend,
                'resend_available_at' => $resendAvailableAt,
                'attempts_remaining' => $canResend ? 3 : 0, // Rate limit de 3 intentos
            ];

            return response()->json(new EmailVerificationStatusResource($statusWithComputed), 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
