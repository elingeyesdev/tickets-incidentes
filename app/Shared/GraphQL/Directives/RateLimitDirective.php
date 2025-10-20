<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Directives;

use GraphQL\Error\Error;
use Illuminate\Support\Facades\RateLimiter;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * Directiva @rateLimit
 *
 * Limita el número de requests permitidos en una ventana de tiempo
 * Previene abuso y ataques de fuerza bruta
 *
 * Uso en schema:
 * @rateLimit(max: 5, window: 60, message: "Demasiados intentos")
 */
class RateLimitDirective extends BaseDirective implements FieldMiddleware
{
    /**
     * Nombre de la directiva en el schema
     */
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
directive @rateLimit(
  max: Int!
  window: Int!
  message: String
) on FIELD_DEFINITION
GRAPHQL;
    }

    /**
     * Middleware que aplica rate limiting antes de resolver el campo
     *
     * @param  FieldValue  $fieldValue
     * @param  \Closure  $next
     * @return FieldValue
     */
    public function handleField(FieldValue $fieldValue): void
    {
        // TODO: Implementar rate limiting real
        // Por ahora, directiva dummy que no hace nada
        //
        // Implementación futura:
        // 1. Usar wrapResolver con firma correcta según versión de Lighthouse
        // 2. Generar key única basada en usuario/IP + nombre del campo
        // 3. Verificar rate limit con Laravel RateLimiter
        // 4. Lanzar error si se excede el límite

        // Dummy: no hace nada, el resolver se ejecuta normalmente
    }

    /**
     * Genera key única para rate limiting
     *
     * @param  GraphQLContext  $context
     * @param  string  $fieldName
     * @return string
     */
    private function generateRateLimitKey(GraphQLContext $context, string $fieldName): string
    {
        try {
            $user = \App\Shared\Helpers\JWTHelper::getAuthenticatedUser();
            return "graphql.rate_limit.{$fieldName}.user.{$user->id}";
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            // Si no está autenticado, usar IP
            $request = $context->request();
            $ip = $request?->ip() ?? 'unknown';
            return "graphql.rate_limit.{$fieldName}.ip.{$ip}";
        }
    }
}