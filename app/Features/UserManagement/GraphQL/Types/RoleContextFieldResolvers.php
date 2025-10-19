<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Types;

use App\Shared\GraphQL\DataLoaders\CompanyByIdBatchLoader;
use Nuwave\Lighthouse\Execution\BatchLoader\BatchLoaderRegistry;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use GraphQL\Type\Definition\ResolveInfo as GraphQLResolveInfo;

/**
 * RoleContext Field Resolvers
 *
 * Resolvers de campos para el tipo RoleContext (shared type).
 * Resuelve el campo 'company' utilizando DataLoader para prevenir N+1.
 *
 * El tipo RoleContext es usado en:
 * - AuthPayload.roleContexts (Authentication)
 * - User.roleContexts (UserManagement)
 * - AuthStatusResponse.roleContexts (Authentication)
 *
 * @see graphql/shared/base-types.graphql (línea 127-139)
 * @see app/Shared/GraphQL/DataLoaders/CompanyByIdBatchLoader.php
 */
class RoleContextFieldResolvers
{
    /**
     * Resuelve el campo 'company' del tipo RoleContext
     *
     * Utiliza CompanyByIdBatchLoader para batching automático.
     * Si GraphQL solicita company de N roles, ejecuta UNA sola query.
     *
     * Lógica:
     * - Si el rol NO tiene company_id (USER, PLATFORM_ADMIN): retorna null
     * - Si el rol tiene company_id (AGENT, COMPANY_ADMIN): usa DataLoader
     *
     * El DataLoader retorna un objeto Company que se mapea automáticamente
     * al tipo RoleCompanyContext definido en el schema.
     *
     * @param array $root Datos del RoleContext (proviene de UserFieldResolvers::roleContexts)
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return \Illuminate\Support\Promise|\App\Features\CompanyManagement\Models\Company|null
     */
    public function company($root, array $args, $context, $resolveInfo)
    {
        // Si no hay company_id, retornar null (roles USER y PLATFORM_ADMIN)
        if (empty($root['company_id'])) {
            return null;
        }

        // Get or create BatchLoader instance for this field path
        $batchLoader = BatchLoaderRegistry::instance(
            $resolveInfo->path,
            static fn (): CompanyByIdBatchLoader => new CompanyByIdBatchLoader(),
        );

        return $batchLoader->load($root['company_id']);
    }
}
