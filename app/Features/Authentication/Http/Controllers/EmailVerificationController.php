<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Requests\EmailVerifyRequest;
use App\Features\Authentication\Http\Resources\EmailVerificationStatusResource;
use App\Features\Authentication\Http\Resources\EmailVerificationResultResource;
use App\Features\Authentication\Services\AuthService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\TokenInvalidException;
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

            // Intentar verificar email
            $this->authService->verifyEmail($token);

            return response()->json([
                'success' => true,
                'message' => 'Email verificado correctamente.',
            ], 200);
        } catch (TokenInvalidException $e) {
            // Token inválido o expirado - retornar error pero con 200
            return response()->json([
                'success' => false,
                'message' => 'El token de verificación es inválido o ha expirado.',
                'canResend' => true,
                'resendAvailableAt' => null,
            ], 200);
        } catch (AuthenticationException $e) {
            // Otros errores de autenticación
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'canResend' => true,
                'resendAvailableAt' => null,
            ], 200);
        } catch (\Exception $e) {
            // Excepciones esperadas se disparan al middleware
            throw $e;
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

            // Verificar que el usuario no esté ya verificado
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El email ya está verificado',
                    'canResend' => false,
                    'resendAvailableAt' => null,
                ], 200);
            }

            // Reenviar verificación
            $this->authService->resendEmailVerification($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Email de verificación enviado correctamente. Revisa tu bandeja de entrada.',
                'canResend' => false,
                'resendAvailableAt' => now()->addMinutes(5)->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            throw $e;
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

            $status = $this->authService->getEmailVerificationStatus($user->id);

            return response()->json(new EmailVerificationStatusResource($status), 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
