<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Types;

use App\Shared\GraphQL\DataLoaders\CompanyByIdLoader;
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
     * Carga directa de la empresa (optimización con DataLoader pendiente)
     *
     * @param \App\Features\UserManagement\Models\UserRole $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return \App\Features\CompanyManagement\Models\Company|null
     */
    public function company($root, array $args, $context, $resolveInfo)
    {
        // Si no hay company_id, retornar null (roles USER y PLATFORM_ADMIN)
        if ($root->company_id === null) {
            return null;
        }

        // Cargar company directamente desde relación Eloquent
        // TODO: Optimizar con DataLoader cuando se resuelvan issues de Lighthouse
        if (!$root->relationLoaded('company')) {
            $root->load('company');
        }

        return $root->company;
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
