<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * Directiva @company
 *
 * Valida que el usuario autenticado tenga acceso al contexto de empresa solicitado
 * Verifica roles de company_admin o agent en la empresa específica
 *
 * Uso en schema:
 * @company(requireOwnership: true)
 */
class CompanyDirective extends BaseDirective implements FieldMiddleware
{
    /**
     * Nombre de la directiva en el schema
     */
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
directive @company(
  requireOwnership: Boolean = false
) on FIELD_DEFINITION
GRAPHQL;
    }

    /**
     * Middleware que valida contexto de empresa antes de resolver el campo
     *
     * @param  FieldValue  $fieldValue
     * @param  \Closure  $next
     * @return FieldValue
     */
    public function handleField(FieldValue $fieldValue): void
    {
        // TODO: Implementar validación real de ownership de empresa
        // Por ahora, directiva dummy que no hace nada (permite todas las requests)
        //
        // Implementación futura:
        // 1. Usar wrapResolver con firma correcta según versión de Lighthouse
        // 2. Obtener companyId de $args o $root
        // 3. Verificar que el usuario tenga rol en esa empresa
        // 4. Si requireOwnership=true, verificar que sea company_admin

        // Dummy: no hace nada, el resolver se ejecuta normalmente
    }
}