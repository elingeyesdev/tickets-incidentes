<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Types;

use App\Shared\GraphQL\DataLoaders\CompanyByIdBatchLoader;
use Nuwave\Lighthouse\Execution\BatchLoader\BatchLoaderRegistry;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use GraphQL\Type\Definition\ResolveInfo as GraphQLResolveInfo;

/**
 * UserRoleInfo Field Resolvers
 *
 * Resolvers de campos para el tipo UserRoleInfo.
 * Maneja la conversión de snake_case (BD) a camelCase (GraphQL).
 *
 * @see app/Features/UserManagement/GraphQL/Schema/user-management.graphql (línea 337-376)
 */
class UserRoleInfoFieldResolvers
{
    /**
     * Resuelve el campo 'roleCode' del tipo UserRoleInfo
     *
     * Mapea role_code (snake_case en BD) a roleCode (camelCase en GraphQL)
     *
     * @param \App\Features\UserManagement\Models\UserRole $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return string
     */
    public function roleCode($root, array $args, $context, $resolveInfo): string
    {
        return $root->role_code;
    }

    /**
     * Resuelve el campo 'roleName' del tipo UserRoleInfo
     *
     * Obtiene el nombre del rol desde la relación 'role'
     *
     * @param \App\Features\UserManagement\Models\UserRole $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return string
     */
    public function roleName($root, array $args, $context, $resolveInfo): string
    {
        // Load relationship if not already loaded
        if (!$root->relationLoaded('role')) {
            $root->load('role');
        }

        return $root->role->role_name;
    }

    /**
     * Resuelve el campo 'company' del tipo UserRoleInfo
     *
     * Utiliza CompanyByIdBatchLoader para prevenir N+1 queries.
     * Si GraphQL solicita company de N roles, ejecuta UNA sola query.
     *
     * @param \App\Features\UserManagement\Models\UserRole $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return \Illuminate\Support\Promise|\App\Features\CompanyManagement\Models\Company|null
     */
    public function company($root, array $args, $context, $resolveInfo)
    {
        // Si no hay company_id, retornar null (roles USER y PLATFORM_ADMIN)
        if ($root->company_id === null) {
            return null;
        }

        // Get or create BatchLoader instance for this field path
        $batchLoader = BatchLoaderRegistry::instance(
            $resolveInfo->path,
            static fn (): CompanyByIdBatchLoader => new CompanyByIdBatchLoader(),
        );

        return $batchLoader->load($root->company_id);
    }

    /**
     * Resuelve el campo 'isActive' del tipo UserRoleInfo
     *
     * Mapea is_active (snake_case en BD) a isActive (camelCase en GraphQL)
     *
     * @param \App\Features\UserManagement\Models\UserRole $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return bool
     */
    public function isActive($root, array $args, $context, $resolveInfo): bool
    {
        return $root->is_active;
    }

    /**
     * Resuelve el campo 'assignedAt' del tipo UserRoleInfo
     *
     * Mapea assigned_at (snake_case en BD) a assignedAt (camelCase en GraphQL)
     *
     * @param \App\Features\UserManagement\Models\UserRole $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return string
     */
    public function assignedAt($root, array $args, $context, $resolveInfo): string
    {
        return $root->assigned_at->toIso8601String();
    }
}
