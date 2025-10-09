<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Queries;

use App\Features\Authentication\Models\RefreshToken;
use App\Shared\GraphQL\Queries\BaseQuery;
use App\Shared\Exceptions\AuthenticationException;

/**
 * Query: mySessions
 *
 * Retorna lista de todas las sesiones activas del usuario autenticado.
 * Permite gestión de dispositivos conectados.
 *
 * Requiere @jwt directive en schema.
 *
 * @usage GraphQL
 * ```graphql
 * query MySessions {
 *   mySessions {
 *     sessionId
 *     deviceName
 *     ipAddress
 *     lastUsedAt
 *     expiresAt
 *     isCurrent
 *   }
 * }
 * ```
 */
class MySessionsQuery extends BaseQuery
{
    /**
     * Get all active sessions for authenticated user
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array Array of SessionInfo
     * @throws AuthenticationException
     */
    public function __invoke($root, array $args, $context = null): array
    {
        // Usuario garantizado por @jwt directive
        $user = $context->user;

        if (!$user) {
            throw AuthenticationException::unauthenticated();
        }

        // Obtener refresh token actual (si existe)
        $request = $context->request();
        $currentRefreshToken = $request->header('X-Refresh-Token')
            ?? $request->cookie('refresh_token');

        $currentTokenHash = $currentRefreshToken ? hash('sha256', $currentRefreshToken) : null;

        // Obtener todas las sesiones activas del usuario
        // Ordenar por último uso (o creación si nunca se usó)
        $sessions = RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderByRaw('COALESCE(last_used_at, created_at) DESC')
            ->get();

        // Mapear a formato SessionInfo
        return $sessions->map(function ($session) use ($currentTokenHash) {
            return [
                'sessionId' => $session->id,
                'deviceName' => $session->device_name,
                'ipAddress' => $session->ip_address,
                'userAgent' => $session->user_agent,
                'lastUsedAt' => $session->last_used_at?->toIso8601String() ?? $session->created_at->toIso8601String(),
                'expiresAt' => $session->expires_at->toIso8601String(),
                'isCurrent' => $currentTokenHash && $session->token_hash === $currentTokenHash,
                'location' => null, // TODO: Implement GeoIP later
            ];
        })->toArray();
    }
}