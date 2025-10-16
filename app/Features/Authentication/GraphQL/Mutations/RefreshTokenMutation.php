<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Exceptions\RefreshTokenRequiredException;
use App\Features\Authentication\GraphQL\Mutations\Concerns\SetsRefreshTokenCookie;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Helpers\DeviceInfoParser;

/**
 * RefreshTokenMutation
 *
 * Genera nuevo access token usando refresh token válido.
 * Invalida el refresh token anterior por seguridad (rotate tokens).
 *
 * NO requiere access token (puede estar expirado - ese es el punto del refresh!)
 * Solo requiere refresh token válido.
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
    use SetsRefreshTokenCookie;

    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Renovar tokens
     *
     * IMPORTANTE: NO requiere @jwt directive.
     * El access token puede estar expirado - por eso se hace refresh!
     *
     * El refresh token se puede enviar de 3 formas (orden de prioridad):
     * 1. Header X-Refresh-Token (más seguro, recomendado)
     * 2. Cookie refresh_token (para web)
     * 3. Argumento en body (para clientes que no permiten headers personalizados)
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array RefreshPayload
     * @throws RefreshTokenRequiredException
     */
    public function __invoke($root, array $args, $context = null): array
    {
        $request = $context->request();

        // Intentar obtener refresh token de diferentes fuentes (en orden de prioridad):
        // 1. Header X-Refresh-Token (más seguro, recomendado)
        // 2. Cookie refresh_token (para web con HttpOnly cookies)
        // 3. Argumento en body $args['refreshToken'] (para Apollo Studio y clientes limitados)
        $refreshToken = $request->header('X-Refresh-Token')
            ?? $request->cookie('refresh_token')
            ?? $args['refreshToken']
            ?? null;

        if (!$refreshToken) {
            throw new RefreshTokenRequiredException();
        }

        // Extraer información del dispositivo
        $deviceInfo = DeviceInfoParser::fromGraphQLContext($context);

        // Llamar al servicio para renovar tokens
        // El servicio valida el refresh token y obtiene el usuario internamente
        $result = $this->authService->refreshToken($refreshToken, $deviceInfo);

        // Establecer el nuevo refresh token en cookie HttpOnly (más seguro)
        $this->setRefreshTokenCookie($result['refresh_token']);

        // Retornar en formato RefreshPayload (más simple que AuthPayload)
        // NOTA: No retornamos el refresh token real, está en cookie HttpOnly
        return [
            'accessToken' => $result['access_token'],
            'refreshToken' => 'Token stored in secure HttpOnly cookie',
            'tokenType' => 'Bearer',
            'expiresIn' => $result['expires_in'],
        ];
    }
}
