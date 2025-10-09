<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\AuthService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Helpers\DeviceInfoParser;

/**
 * RefreshTokenMutation
 *
 * Genera nuevo access token usando refresh token válido.
 * Invalida el refresh token anterior por seguridad (rotate tokens).
 *
 * Requiere autenticación JWT (@jwt directive).
 *
 * @usage GraphQL
 * ```graphql
 * mutation RefreshToken {
 *   refreshToken {
 *     accessToken
 *     refreshToken
 *     tokenType
 *     expiresIn
 *   }
 * }
 * ```
 */
class RefreshTokenMutation extends BaseMutation
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Renovar tokens
     *
     * IMPORTANTE: Esta mutation requiere @jwt directive.
     * El usuario debe estar autenticado con un token válido.
     * Necesitamos extraer el refresh token del request.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array RefreshPayload
     * @throws AuthenticationException
     */
    public function __invoke($root, array $args, $context = null): array
    {
        // Obtener usuario autenticado (garantizado por @jwt directive)
        $user = $context->user;

        if (!$user) {
            throw new AuthenticationException('Authentication required');
        }

        // Obtener refresh token del header o body
        // Por seguridad, el refresh token debe enviarse en el Authorization header
        // o en una cookie HttpOnly (implementación futura)
        $request = $context->request();

        // Intentar obtener refresh token de diferentes fuentes:
        // 1. Header X-Refresh-Token (recomendado para GraphQL)
        // 2. Cookie refresh_token (para web)
        $refreshToken = $request->header('X-Refresh-Token')
            ?? $request->cookie('refresh_token');

        if (!$refreshToken) {
            throw new AuthenticationException(
                'Refresh token required. Send it via X-Refresh-Token header or refresh_token cookie.'
            );
        }

        // Extraer información del dispositivo
        $deviceInfo = DeviceInfoParser::fromGraphQLContext($context);

        // Llamar al servicio para renovar tokens
        $result = $this->authService->refreshToken($refreshToken, $deviceInfo);

        // Retornar en formato RefreshPayload (más simple que AuthPayload)
        return [
            'accessToken' => $result['access_token'],
            'refreshToken' => $result['refresh_token'],
            'tokenType' => 'Bearer',
            'expiresIn' => $result['expires_in'],
        ];
    }
}
