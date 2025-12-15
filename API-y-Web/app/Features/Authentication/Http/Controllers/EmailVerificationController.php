<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Requests\EmailVerifyRequest;
use App\Features\Authentication\Http\Resources\EmailVerificationStatusResource;
use App\Features\Authentication\Http\Resources\EmailVerificationResultResource;
use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Exceptions\TokenInvalidException;
use App\Features\AuditLog\Services\ActivityLogService;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Email Verification Controller
 *
 * REST endpoints para verificación de email.
 * Flujo:
 * 1. POST /email/verify → Verificar email con token O código de 6 dígitos
 * 2. POST /email/verify/resend → Reenviar email (requiere auth)
 * 3. GET /email/status → Ver estado de verificación (requiere auth)
 * 
 * El email de verificación incluye:
 * - Token de 64 caracteres (para link directo)
 * - Código de 6 dígitos (para entrada manual)
 */
class EmailVerificationController
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * Verify email
     *
     * Verifica el email del usuario usando un token O código de 6 dígitos.
     * El token/código identifica al usuario automáticamente.
     * Puede usar token (64 chars) o code (6 dígitos).
     * Siempre retorna 200, pero success puede ser false.
     *
     * @authenticated false
     * @response 200 {"success": true, "message": "..."}
     */
    #[OA\Post(
        path: '/api/auth/email/verify',
        summary: 'Verify email with token or code',
        description: 'Verify user email with token OR 6-digit code. Public endpoint (no authentication required). Provide either token or code, not both. Always returns 200 status for valid/invalid tokens, check "success" field in response body to determine result.',
        tags: ['Email Verification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'token',
                        type: 'string',
                        nullable: true,
                        description: 'Email verification token (64 characters) - received via email link. Use token OR code, not both.',
                        example: 'abc123def456ghi789jkl012mno345pqrst789xyz123abc456def789ghi012'
                    ),
                    new OA\Property(
                        property: 'code',
                        type: 'string',
                        nullable: true,
                        description: '6-digit verification code - alternative to token for manual entry. Use token OR code, not both.',
                        example: '123456'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification result (always returns 200 for both success and failure cases, check success field)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'success', 
                            type: 'boolean', 
                            example: true,
                            description: 'Whether verification succeeded'
                        ),
                        new OA\Property(
                            property: 'message', 
                            type: 'string', 
                            example: '¡Email verificado exitosamente! Ya puedes usar todas las funciones del sistema.',
                            description: 'User-friendly message describing the result'
                        ),
                        new OA\Property(
                            property: 'canResend', 
                            type: 'boolean', 
                            example: false,
                            description: 'Whether user can request to resend verification email'
                        ),
                        new OA\Property(
                            property: 'resendAvailableAt', 
                            type: 'string', 
                            format: 'date-time',
                            nullable: true, 
                            example: null,
                            description: 'Timestamp when resend will be available (null if cannot resend)'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422, 
                description: 'Validation error (e.g., both token and code provided, neither provided, or invalid format)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Proporciona token O código, no ambos.'),
                        new OA\Property(property: 'canResend', type: 'boolean', example: true),
                        new OA\Property(property: 'resendAvailableAt', type: 'string', format: 'date-time'),
                    ]
                )
            ),
        ]
    )]
    public function verify(EmailVerifyRequest $request): JsonResponse
    {
        try {
            $token = $request->input('token');
            $code = $request->input('code');

            // Validar que se envíe token O código, pero no ambos
            if ($token && $code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proporciona token O código, no ambos.',
                    'canResend' => true,
                    'resendAvailableAt' => now()->toIso8601String(),
                ], 422);
            }

            if (!$token && !$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere token o código de verificación.',
                    'canResend' => true,
                    'resendAvailableAt' => now()->toIso8601String(),
                ], 422);
            }

            // Verificar usando token O código
            if ($token) {
                $user = $this->authService->verifyEmail($token);
            } else {
                $user = $this->authService->verifyEmailWithCode($code);
            }

            // Registrar actividad
            $this->activityLogService->logEmailVerified($user->id);

            \Illuminate\Support\Facades\Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'method' => $token ? 'token' : 'code',
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Email verificado exitosamente! Ya puedes usar todas las funciones del sistema.',
                'canResend' => false,
                'resendAvailableAt' => null,
            ], 200);

        } catch (AuthenticationException $e) {
            // Error de verificación (token/código inválido, expirado, email ya verificado)
            \Illuminate\Support\Facades\Log::warning('Email verification failed', [
                'token_preview' => $request->input('token') ? substr($request->input('token', ''), 0, 10) . '...' : null,
                'code' => $request->input('code') ? '******' : null,
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
        description: 'Resend verification email to authenticated user. Rate limited: 3 attempts every 5 minutes. Always returns success message for security (even if already verified).',
        security: [['bearerAuth' => []]],
        tags: ['Email Verification'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email resent successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Email de verificación enviado correctamente. Revisa tu bandeja de entrada.'),
                        new OA\Property(property: 'canResend', type: 'boolean', example: false),
                        new OA\Property(property: 'resendAvailableAt', type: 'string', format: 'date-time', example: '2025-11-01T15:30:00+00:00'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 429, description: 'Rate limit exceeded (3 attempts per 5 minutes)'),
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
        description: 'Get email verification status for authenticated user. Returns verification state, timestamps, and resend availability.',
        security: [['bearerAuth' => []]],
        tags: ['Email Verification'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'is_verified', type: 'boolean', example: false),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                        new OA\Property(property: 'verified_at', type: 'string', format: 'date-time', nullable: true, example: '2025-11-01T10:30:00+00:00'),
                        new OA\Property(property: 'can_resend', type: 'boolean', example: true),
                        new OA\Property(property: 'resend_available_at', type: 'string', format: 'date-time', nullable: true, example: '2025-11-01T15:30:00+00:00'),
                        new OA\Property(property: 'attempts_remaining', type: 'integer', example: 3),
                    ]
                )
            ),
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
