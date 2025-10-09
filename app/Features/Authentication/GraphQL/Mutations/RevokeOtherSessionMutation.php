<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Exceptions\CannotRevokeCurrentSessionException;
use App\Features\Authentication\Exceptions\SessionNotFoundException;
use App\Features\Authentication\Models\RefreshToken;
use App\Features\Authentication\Services\TokenService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Exceptions\AuthenticationException;

/**
 * RevokeOtherSessionMutation
 *
 * Revoca una sesión específica de otro dispositivo.
 * No permite revocar la sesión actual (usar logout para eso).
 *
 * Requiere @jwt directive en schema.
 *
 * @usage GraphQL
 * ```graphql
 * mutation RevokeOtherSession($sessionId: String!) {
 *   revokeOtherSession(sessionId: $sessionId)
 * }
 * ```
 */
class RevokeOtherSessionMutation extends BaseMutation
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {}

    /**
     * Revoke specific session
     *
     * @param  mixed  $root
     * @param  array{sessionId: string}  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return bool Always true if successful
     * @throws AuthenticationException
     * @throws SessionNotFoundException
     * @throws CannotRevokeCurrentSessionException
     */
    public function __invoke($root, array $args, $context = null): bool
    {
        // Usuario garantizado por @jwt directive
        $user = $context->user;

        if (!$user) {
            throw AuthenticationException::unauthenticated();
        }

        $sessionId = $args['sessionId'];

        // Buscar la sesión
        $session = RefreshToken::find($sessionId);

        // Validar que la sesión existe
        if (!$session) {
            throw new SessionNotFoundException("Session '{$sessionId}' not found.");
        }

        // Validar que la sesión pertenece al usuario
        if ($session->user_id !== $user->id) {
            throw new SessionNotFoundException("Session '{$sessionId}' does not belong to you.");
        }

        // Validar que la sesión no esté ya revocada
        if ($session->is_revoked) {
            throw new SessionNotFoundException('Session already revoked.');
        }

        // Obtener refresh token actual
        $request = $context->request();
        $currentRefreshToken = $request->header('X-Refresh-Token')
            ?? $request->cookie('refresh_token');

        // Validar que no se está revocando la sesión actual
        if ($currentRefreshToken) {
            $currentTokenHash = hash('sha256', $currentRefreshToken);

            if ($session->token_hash === $currentTokenHash) {
                throw new CannotRevokeCurrentSessionException();
            }
        }

        // Blacklist el session_id para invalidar los access tokens asociados
        $this->tokenService->blacklistToken($session->id);

        // Revocar la sesión (refresh token)
        $session->revoke($user->id);

        return true;
    }
}