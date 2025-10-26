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
        $input = $args['input'] ?? [];
        $token = $input['token'] ?? null;
        $code = $input['code'] ?? null;
        $newPassword = $input['password'] ?? $input['newPassword'] ?? null;

        // === VALIDACIONES ===
        if (!$newPassword) {
            throw ValidationException::fieldRequired('newPassword');
        }

        // Validar que hay token O código, pero NO ambos
        if ($token && $code) {
            throw ValidationException::withField('input', 'Provide either token or code, not both');
        }

        if (!$token && !$code) {
            throw ValidationException::withField('input', 'Provide either token or code');
        }

        // === CONFIRMAR RESET ===
        if ($token) {
            $user = $this->passwordResetService->confirmReset($token, $newPassword);
        } else {
            $user = $this->passwordResetService->confirmResetWithCode($code, $newPassword);
        }

        // === GENERAR TOKENS JWT ===
        // Generar access token
        $accessToken = $this->tokenService->generateAccessToken($user);
        
        // Crear refresh token
        $refreshTokenData = $this->tokenService->createRefreshToken($user, [
            'name' => 'Password Reset Login',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'success' => true,
            'message' => 'Password reset successful',
            'accessToken' => $accessToken,
            'refreshToken' => $refreshTokenData['token'],
            'user' => $user,
        ];
    }
}
