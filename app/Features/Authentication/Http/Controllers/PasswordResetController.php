<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Requests\PasswordResetRequest;
use App\Features\Authentication\Http\Requests\PasswordResetConfirmRequest;
use App\Features\Authentication\Http\Resources\PasswordResetStatusResource;
use App\Features\Authentication\Http\Resources\PasswordResetResultResource;
use App\Features\Authentication\Services\PasswordResetService;
use App\Shared\Utilities\DeviceInfoParser;
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
        description: 'Request a password reset. Always returns success for security.',
        tags: ['Password Reset'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reset requested (always success)'),
            new OA\Response(response: 422, description: 'Validation error'),
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
        description: 'Confirm password reset with new password and token/code',
        tags: ['Password Reset'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password', 'passwordConfirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', nullable: true, description: 'Reset token (32 chars)'),
                    new OA\Property(property: 'code', type: 'string', nullable: true, description: 'Reset code (6 digits)'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'passwordConfirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Invalid token/code'),
            new OA\Response(response: 401, description: 'Token expired'),
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

            // === VALIDACIONES (replicar mutation) ===
            if (!$password) {
                throw ValidationException::fieldRequired('password');
            }

            if (!$passwordConfirmation) {
                throw ValidationException::fieldRequired('passwordConfirmation');
            }

            if ($password !== $passwordConfirmation) {
                throw ValidationException::withField('passwordConfirmation', 'The passwords do not match');
            }

            if (strlen($password) < 8) {
                throw ValidationException::withField('password', 'The password must be at least 8 characters');
            }

            // Validar que hay token O código, pero NO ambos
            if ($token && $code) {
                throw ValidationException::withField('input', 'Provide either token or code, not both');
            }

            if (!$token && !$code) {
                throw ValidationException::withField('input', 'Provide either token or code');
            }

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
                    minutes: 43200,
                    path: '/',
                    domain: null,
                    secure: !app()->isLocal(),
                    httpOnly: true,
                    sameSite: 'lax'
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
        description: 'Validate password reset token and return its status',
        tags: ['Password Reset'],
        parameters: [
            new OA\Parameter(
                name: 'token',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', description: 'Reset token')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Token status retrieved'),
            new OA\Response(response: 404, description: 'Token not found'),
            new OA\Response(response: 410, description: 'Token expired'),
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
