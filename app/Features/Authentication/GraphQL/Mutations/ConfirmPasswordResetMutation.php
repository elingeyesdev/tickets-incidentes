<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\PasswordResetService;
use App\Features\Authentication\Services\TokenService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\GraphQL\Mutations\BaseMutation;

/**
 * Confirm Password Reset Mutation
 *
 * Confirma el reset de contraseña usando token O código de 6 dígitos.
 * 
 * Soporta:
 * - Reset con token (32 caracteres)
 * - Reset con código (6 dígitos)
 * - NO permite ambos en el mismo request
 *
 * Retorna:
 * - success: bool
 * - accessToken: JWT para auto-login
 * - refreshToken: JWT para refresh
 * - user: Usuario completo
 */
class ConfirmPasswordResetMutation extends BaseMutation
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
        private readonly TokenService $tokenService
    ) {}

    /**
     * @param mixed $root
     * @param array{input: array{token?: string, code?: string, newPassword: string}} $args
     * @param mixed $context
     * @return array{success: bool, accessToken: string, refreshToken: string, user: object}
     */
    public function __invoke($root, array $args, $context = null): array
    {
        try {
            $input = $args['input'] ?? [];
            $token = $input['token'] ?? null;
            $code = $input['code'] ?? null;
            $newPassword = $input['password'] ?? $input['newPassword'] ?? null;
            $passwordConfirmation = $input['passwordConfirmation'] ?? null;

            // === VALIDACIONES ===
            if (!$newPassword) {
                throw ValidationException::fieldRequired('password');
            }

            if (!$passwordConfirmation) {
                throw ValidationException::fieldRequired('passwordConfirmation');
            }

            if ($newPassword !== $passwordConfirmation) {
                throw ValidationException::withField('passwordConfirmation', 'The passwords do not match');
            }

            if (strlen($newPassword) < 8) {
                throw ValidationException::withField('password', 'The password must be at least 8 characters');
            }

            // Validar que hay token O código, pero NO ambos
            if ($token && $code) {
                throw ValidationException::withField('input', 'Provide either token or code, not both');
            }

            if (!$token && !$code) {
                throw ValidationException::withField('input', 'Provide either token or code');
            }

            // === CONFIRMAR RESET ===
            // Prepare device info
            $deviceInfo = [];
            try {
                $deviceInfo = [
                    'name' => 'Password Reset Login',
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ];
            } catch (\Exception $e) {
                // En contexto de testing o sin request, usar valores por defecto
                $deviceInfo = ['name' => 'Password Reset Login'];
            }

            // Call service (ahora retorna array con tokens)
            if ($token) {
                $result = $this->passwordResetService->confirmReset($token, $newPassword, $deviceInfo);
            } else {
                $result = $this->passwordResetService->confirmResetWithCode($code, $newPassword, $deviceInfo);
            }

            // Service ya genera los tokens, solo formatear respuesta
            return [
                'success' => true,
                'message' => 'Password reset successful',
                'accessToken' => $result['access_token'],
                'refreshToken' => $result['refresh_token'],
                'user' => $result['user'],
            ];
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('ConfirmPasswordResetMutation error', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
