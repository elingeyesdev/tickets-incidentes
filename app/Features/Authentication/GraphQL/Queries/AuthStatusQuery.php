<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Queries;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\Authentication\Services\TokenService;
use App\Shared\GraphQL\Queries\BaseQuery;
use App\Shared\Exceptions\AuthenticationException;

/**
 * Query: authStatus
 *
 * Retorna el estado completo de autenticación del usuario actual.
 * Incluye información de usuario, sesión activa y token.
 *
 * Requiere @jwt directive en schema.
 *
 * @usage GraphQL
 * ```graphql
 * query AuthStatus {
 *   authStatus {
 *     isAuthenticated
 *     user { id email displayName }
 *     currentSession { sessionId deviceName lastUsedAt }
 *     tokenInfo { expiresIn issuedAt tokenType }
 *   }
 * }
 * ```
 */
class AuthStatusQuery extends BaseQuery
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {}

    /**
     * Get authentication status
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array AuthStatus
     * @throws AuthenticationException
     */
    public function __invoke($root, array $args, $context = null): array
    {
        // Usuario garantizado por @jwt directive
        $user = $context->user;

        if (!$user) {
            throw AuthenticationException::unauthenticated();
        }

        // NO hacer eager loading aquí - dejar que los DataLoaders lo manejen
        // profile y roleContexts se cargarán SOLO si el frontend los solicita
        // Los DataLoaders previenen N+1 queries automáticamente

        // Obtener access token del header
        $request = $context->request();
        $authHeader = $request->header('Authorization');
        $accessToken = null;

        if ($authHeader) {
            // Formato 1: "Bearer <token>" (estándar OAuth 2.0)
            if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
                $accessToken = $matches[1];
            }
            // Formato 2: Token directo (para Apollo Studio compatibility)
            elseif (substr_count($authHeader, '.') >= 2) {
                $accessToken = $authHeader;
            }
        }

        // Validar y obtener payload del token
        $tokenPayload = null;
        $tokenExpiresAt = null;
        $sessionId = null;

        if ($accessToken) {
            try {
                $tokenPayload = $this->tokenService->validateAccessToken($accessToken);
                $tokenExpiresAt = $tokenPayload->exp ?? null;
                $sessionId = $tokenPayload->session_id ?? null;
            } catch (\Exception $e) {
                // Token inválido - se manejará abajo
            }
        }

        // Obtener sesión actual usando session_id del JWT
        $currentSession = null;
        if ($sessionId) {
            $currentSession = $this->getCurrentSessionById($sessionId);
        }

        // Construir respuesta AuthStatus
        // NOTA: roleContexts ahora se resuelve automáticamente por UserAuthInfoRoleContextsResolver
        // IMPORTANTE: Devolver el modelo User, no un array manual, para que los field resolvers funcionen
        return [
            'isAuthenticated' => true,
            'user' => $user, // Devolver modelo completo para que los resolvers funcionen
            'currentSession' => $currentSession,
            'tokenInfo' => [
                'expiresIn' => $tokenExpiresAt ? max(0, $tokenExpiresAt - time()) : 0,
                'issuedAt' => now()->toIso8601String(),
                'tokenType' => 'Bearer',
            ],
        ];
    }

    /**
     * Get current session info by session ID from JWT
     *
     * @param string $sessionId Session ID from JWT payload
     * @return array|null SessionInfo or null
     */
    private function getCurrentSessionById(string $sessionId): ?array
    {
        // Buscar sesión por ID
        $session = RefreshToken::where('id', $sessionId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            return null;
        }

        return [
            'sessionId' => $session->id,
            'deviceName' => $session->device_name,
            'ipAddress' => $session->ip_address,
            'userAgent' => $session->user_agent,
            'lastUsedAt' => $session->last_used_at?->toIso8601String() ?? $session->created_at->toIso8601String(),
            'expiresAt' => $session->expires_at->toIso8601String(),
            'isCurrent' => true, // Always true since we're querying with current token
            'location' => null, // TODO: Implement GeoIP later
        ];
    }
}