<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * Directiva @audit
 *
 * Registra la operación en el audit log del sistema
 * Mantiene trazabilidad de operaciones críticas
 *
 * Uso en schema:
 * @audit(action: "create_user", includePayload: true)
 */
class AuditDirective extends BaseDirective implements FieldMiddleware
{
    /**
     * Nombre de la directiva en el schema
     */
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
directive @audit(
  action: String!
  includePayload: Boolean = false
) on FIELD_DEFINITION
GRAPHQL;
    }

    /**
     * Middleware que registra la operación en audit log
     *
     * @param  FieldValue  $fieldValue
     * @param  \Closure  $next
     * @return FieldValue
     */
    public function handleField(FieldValue $fieldValue): void
    {
        // TODO: Implementar registro real en audit log
        // Por ahora, directiva dummy que no hace nada
        //
        // Implementación futura:
        // 1. Crear modelo AuditLog
        // 2. Usar wrapResolver con firma correcta según versión de Lighthouse
        // 3. Registrar: user_id, action, payload (si includePayload=true), timestamp, IP
        // 4. Almacenar en schema audit.audit_logs

        // Dummy: no hace nada, el resolver se ejecuta normalmente
    }
}