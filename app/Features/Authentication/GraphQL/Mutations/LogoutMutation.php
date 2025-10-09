<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\AuthService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Support\Facades\Log;

/**
 * LogoutMutation
 *
 * Cierra sesión del usuario autenticado.
 * - everywhere=false: solo sesión actual (default)
 * - everywhere=true: todas las sesiones (todos los dispositivos)
 *
 * Requiere autenticación JWT (@jwt directive).
 *
 * @usage GraphQL
 * ```graphql
 * # Logout de sesión actual
 * mutation Logout {
 *   logout
 * }
 *
 * # Logout de todas las sesiones
 * mutation LogoutEverywhere {
 *   logout(everywhere: true)
 * }
 * ```
 */
class LogoutMutation extends BaseMutation
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Logout
     *
     * @param  mixed  $root
     * @param  array{everywhere: bool}  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return bool Siempre retorna true si el logout fue exitoso
     * @throws AuthenticationException
     */
    public function __invoke($root, array $args, $context = null): bool
    {
        // Obtener usuario autenticado (garantizado por @jwt directive)
        $user = $context->user;

        if (!$user) {
            throw new AuthenticationException('Authentication required');
        }

        $everywhere = $args['everywhere'] ?? false;
        $request = $context->request();

        if ($everywhere) {
            // Logout de todas las sesiones (todos los dispositivos)
            $revokedCount = $this->authService->logoutAllDevices($user->id);

            Log::info('User logged out from all devices', [
                'user_id' => $user->id,
                'sessions_revoked' => $revokedCount,
            ]);
        } else {
            // Logout de sesión actual solamente
            // Necesitamos el access token actual y el refresh token

            // Access token viene del Authorization header (ya validado por middleware)
            $authHeader = $request->header('Authorization');
            $accessToken = null;

            if ($authHeader && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
                $accessToken = $matches[1];
            }

            if (!$accessToken) {
                throw new AuthenticationException('Access token required for logout');
            }

            // Refresh token viene de X-Refresh-Token header o cookie
            $refreshToken = $request->header('X-Refresh-Token')
                ?? $request->cookie('refresh_token');

            if (!$refreshToken) {
                // Si no hay refresh token, solo invalidamos el access token
                Log::warning('Logout without refresh token - only access token will be blacklisted', [
                    'user_id' => $user->id,
                ]);
            }

            // Llamar al servicio para logout
            $this->authService->logout($accessToken, $refreshToken ?? '', $user->id);

            Log::info('User logged out from current session', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        return true;
    }
}
