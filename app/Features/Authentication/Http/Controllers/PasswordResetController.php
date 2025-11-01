<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Requests\PasswordResetRequest;
use App\Features\Authentication\Http\Requests\PasswordResetConfirmRequest;
use App\Features\Authentication\Http\Resources\PasswordResetStatusResource;
use App\Features\Authentication\Http\Resources\PasswordResetResultResource;
use App\Features\Authentication\Services\PasswordResetService;
use App\Shared\Helpers\DeviceInfoParser;
use App\Shared\Exceptions\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

/**
 * Password Reset Controller
 *
 * REST endpoints para reset de contraseña.
 * Flujo:
 * 1. POST /password-reset → Solicitar reset (envía email)
 * 2. GET /password-reset/status → Validar token
 * 3. POST /password-reset/confirm → Confirmar con nueva contraseña
 */
class PasswordResetController
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
    ) {}

    /**
     * Request password reset
     *
     * Solicita un reset de contraseña. Siempre retorna success=true por seguridad.
     * No revela si el email existe en el sistema.
     *
     * @authenticated false
     * @response 200 {"success": true, "message": "..."}
     */
    #[OA\Post(
        path: '/api/auth/password-reset',
        summary: 'Request password reset',
        description: 'Request a password reset email. Public endpoint (no authentication required). ALWAYS returns 200 success for security - does not reveal if email exists in system.',
        tags: ['Password Reset'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['email'],
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        description: 'Email address to send password reset link',
                        example: 'user@example.com'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reset requested successfully (always returns 200, even if email does not exist)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Si el email existe en nuestro sistema, recibirás un enlace para resetear tu contraseña.'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error (invalid email format)'),
        ]
    )]
    public function store(PasswordResetRequest $request): JsonResponse
    {
        try {
            // Replicar exactamente ResetPasswordMutation
            $email = strtolower(trim($request->input('email') ?? ''));

            // Solicitar reset - siempre retorna true
            $this->passwordResetService->requestReset($email);

            // Retornar success=true incluso si email no existe (por seguridad)
            return response()->json([
                'success' => true,
                'message' => 'Si el email existe en nuestro sistema, recibirás un enlace para resetear tu contraseña.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Exception in PasswordResetController::store', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Confirm password reset
     *
     * Confirma el reset de contraseña con la nueva contraseña.
     * Puede usar token (32 chars) o code (6 dígitos).
     * Retorna tokens de sesión automáticamente.
     *
     * @authenticated false
     * @response 200 {"success": true, "message": "...", "accessToken": "...", "user": {...}}
     */
    #[OA\Post(
        path: '/api/auth/password-reset/confirm',
        summary: 'Confirm password reset',
        description: 'Confirm password reset with new password and token/code. Public endpoint (no authentication required). Automatically logs in user and returns session tokens after successful reset.',
        tags: ['Password Reset'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['password', 'passwordConfirmation'],
                properties: [
                    new OA\Property(
                        property: 'token',
                        type: 'string',
                        nullable: true,
                        description: 'Reset token (32 chars) - received via email. Use token OR code, not both.',
                        example: 'abc123def456ghi789jkl012mno345pq'
                    ),
                    new OA\Property(
                        property: 'code',
                        type: 'string',
                        nullable: true,
                        description: 'Reset code (6 digits) - alternative to token. Use token OR code, not both.',
                        example: '123456'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        minLength: 8,
                        description: 'New password (minimum 8 characters)',
                        example: 'NewSecurePass123!'
                    ),
                    new OA\Property(
                        property: 'passwordConfirmation',
                        type: 'string',
                        format: 'password',
                        description: 'Password confirmation (must match password)',
                        example: 'NewSecurePass123!'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset successfully - user automatically logged in',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'accessToken', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                        new OA\Property(property: 'refreshToken', type: 'string', example: 'def502004b4c8a1b3f7e...'),
                        new OA\Property(property: 'tokenType', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expiresIn', type: 'integer', example: 3600),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'userCode', type: 'string', example: 'USER001'),
                            ]
                        ),
                        new OA\Property(property: 'sessionId', type: 'string', format: 'uuid', example: '650e8400-e29b-41d4-a716-446655440001'),
                        new OA\Property(property: 'loginTimestamp', type: 'string', format: 'date-time', example: '2025-11-01T15:30:00+00:00'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error (invalid format, passwords do not match, etc.)'),
            new OA\Response(response: 404, description: 'Invalid token/code - not found in database'),
            new OA\Response(response: 401, description: 'Token expired or already used'),
        ]
    )]
    public function confirm(PasswordResetConfirmRequest $request): JsonResponse
    {
        try {
            // Replicar exactamente ConfirmPasswordResetMutation

            // === EXTRAER Y VALIDAR INPUTS ===
            $token = $request->input('token');
            $code = $request->input('code');
            $password = $request->input('password');
            $passwordConfirmation = $request->input('passwordConfirmation');

            // === VALIDACIONES ===
            // FormRequest ya validó todo: token XOR code, password, passwordConfirmation
            // Solo extraemos los valores validados

            // === PREPARAR DEVICE INFO (con fallback como en mutation) ===
            $deviceInfo = [];
            try {
                $deviceInfo = DeviceInfoParser::fromRequest($request);
                // Sobrescribir name con el estándar de mutation
                $deviceInfo['name'] = 'Password Reset Login';
            } catch (\Exception $e) {
                // En contexto de testing o sin request, usar valores por defecto
                $deviceInfo = ['name' => 'Password Reset Login'];
            }

            // === CONFIRMAR RESET ===
            // Usar token o code
            if ($token) {
                $result = $this->passwordResetService->confirmReset(
                    $token,
                    $password,
                    $deviceInfo
                );
            } else {
                $result = $this->passwordResetService->confirmResetWithCode(
                    $code,
                    $password,
                    $deviceInfo
                );
            }

            return response()
                ->json(new PasswordResetResultResource($result), 200)
                ->cookie(
                    'refresh_token',
                    $result['refresh_token'],
                    43200, // minutes
                    '/', // path
                    null, // domain
                    !app()->isLocal(), // secure
                    true, // httpOnly
                    false, // raw
                    'lax' // sameSite
                );
        } catch (\Exception $e) {
            // Log the exception for debugging (replicar mutation)
            Log::error('ConfirmPasswordResetMutation error', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get password reset status
     *
     * Valida un token de reset y retorna su estado.
     * Usado para verificar si un token es válido antes de mostrar formulario.
     *
     * @authenticated false
     * @response 200 {"isValid": true, "canReset": true, "email": "...", "expiresAt": "...", ...}
     */
    #[OA\Get(
        path: '/api/auth/password-reset/status',
        summary: 'Get password reset status',
        description: 'Validate password reset token and return its status. Public endpoint (no authentication required). Use this before showing the password reset form to verify the token is valid.',
        tags: ['Password Reset'],
        parameters: [
            new OA\Parameter(
                name: 'token',
                in: 'query',
                required: true,
                description: 'Password reset token (32 chars) received via email',
                schema: new OA\Schema(type: 'string', example: 'abc123def456ghi789jkl012mno345pq')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token status retrieved successfully (always returns 200, check is_valid field)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'is_valid', type: 'boolean', example: true, description: 'Whether the token is valid and not expired'),
                        new OA\Property(property: 'can_reset', type: 'boolean', example: true, description: 'Whether password can be reset (same as is_valid)'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'user@example.com', description: 'Email associated with token (null if invalid)'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true, example: '2025-11-01T16:30:00+00:00', description: 'Token expiration timestamp'),
                        new OA\Property(property: 'attempts_remaining', type: 'integer', example: 3, description: 'Number of remaining reset attempts'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Token not found in database'),
            new OA\Response(response: 410, description: 'Token expired (gone)'),
        ]
    )]
    public function status(Request $request): JsonResponse
    {
        try {
            // Replicar exactamente PasswordResetStatusQuery

            $token = $request->input('token');

            if (!$token) {
                return response()->json([
                    'isValid' => false,
                    'canReset' => false,
                    'email' => null,
                    'expiresAt' => null,
                    'attemptsRemaining' => 0,
                ], 200); // Retornar 200 incluso si token falta (como query)
            }

            // Validar token
            $status = $this->passwordResetService->validateResetToken($token);

            // Agregar campos computados (como calcula la query)
            // Convertir timestamp a ISO8601 si es necesario
            $statusFormatted = [
                'is_valid' => $status['is_valid'],
                'can_reset' => $status['is_valid'],
                'email' => $status['email'],
                'expires_at' => $status['expires_at']
                    ? \Carbon\Carbon::createFromTimestamp($status['expires_at'])->toIso8601String()
                    : null,
                'attempts_remaining' => $status['attempts_remaining'] ?? 0,
            ];

            return response()->json(new PasswordResetStatusResource($statusFormatted), 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
