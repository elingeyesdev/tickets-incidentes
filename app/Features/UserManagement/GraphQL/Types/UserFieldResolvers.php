<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Types;

use App\Shared\GraphQL\DataLoaders\UserProfileBatchLoader;
use App\Shared\GraphQL\DataLoaders\UserRoleContextsBatchLoader;
use Nuwave\Lighthouse\Execution\BatchLoader\BatchLoaderRegistry;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use GraphQL\Type\Definition\ResolveInfo as GraphQLResolveInfo;

/**
 * User Field Resolvers
 *
 * Resolvers de campos para el tipo User que utilizan DataLoaders
 * para prevenir problemas de N+1 queries.
 *
 * Este enfoque permite:
 * - Batching automático de queries (1 query para N usuarios)
 * - Carga condicional (solo si GraphQL solicita el campo)
 * - Escalabilidad y rendimiento óptimo
 *
 * @see app/Shared/GraphQL/DataLoaders/UserProfileByUserIdLoader.php
 * @see app/Shared/GraphQL/DataLoaders/UserRolesByUserIdLoader.php
 */
class UserFieldResolvers
{
    /**
     * Resuelve el campo 'profile' del tipo User
     *
     * Utiliza BatchLoader para prevenir N+1 queries cuando se cargan
     * múltiples usuarios con sus perfiles en una sola operación GraphQL.
     *
     * @param \App\Features\UserManagement\Models\User $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return \GraphQL\Deferred
     */
    public function profile($root, array $args, $context, $resolveInfo)
    {
        // Get or create BatchLoader instance for this field path
        $batchLoader = BatchLoaderRegistry::instance(
            $resolveInfo->path,
            static fn (): UserProfileBatchLoader => new UserProfileBatchLoader(),
        );

        return $batchLoader->load($root);
    }

    /**
     * Resuelve el campo 'roleContexts' del tipo User
     *
     * Utiliza BatchLoader para prevenir N+1 queries cuando se cargan
     * múltiples usuarios con sus roles en una sola operación GraphQL.
     * Solo retorna roles ACTIVOS (is_active = true).
     *
     * Transforma los UserRole en formato RoleContext según schema:
     * - roleCode: del UserRole
     * - roleName: del Role relacionado
     * - company: del UserRole (nullable)
     * - dashboardPath: del Role relacionado
     *
     * @param \App\Features\UserManagement\Models\User $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return \GraphQL\Deferred
     */
    public function roleContexts($root, array $args, $context, $resolveInfo)
    {
        // Get or create BatchLoader instance for this field path
        // This BatchLoader already transforms UserRole to RoleContext format
        $batchLoader = BatchLoaderRegistry::instance(
            $resolveInfo->path,
            static fn (): UserRoleContextsBatchLoader => new UserRoleContextsBatchLoader(),
        );

        // Return the Deferred directly - it already contains the transformed data
        return $batchLoader->load($root);
    }

    /**
     * Resuelve el campo 'ticketsCount' del tipo User
     *
     * NOTA: Por ahora retorna 0 hasta que se implemente el feature de Ticketing.
     * Cuando Tickets esté listo, usar un DataLoader para contar tickets.
     *
     * @param \App\Features\UserManagement\Models\User $root
     * @return int
     */
    public function ticketsCount($root): int
    {
        // TODO: Implementar cuando exista el feature Ticketing
        // return $context->dataLoader(TicketsCountByUserIdLoader::class)->load($root->id);
        return 0;
    }

    /**
     * Resuelve el campo 'resolvedTicketsCount' del tipo User
     *
     * NOTA: Por ahora retorna 0 hasta que se implemente el feature de Ticketing.
     *
     * @param \App\Features\UserManagement\Models\User $root
     * @return int
     */
    public function resolvedTicketsCount($root): int
    {
        // TODO: Implementar cuando exista el feature Ticketing
        return 0;
    }

    /**
     * Resuelve el campo 'averageRating' del tipo User
     *
     * NOTA: Por ahora retorna null hasta que se implemente el feature de Ratings.
     *
     * @param \App\Features\UserManagement\Models\User $root
     * @return float|null
     */
    public function averageRating($root): ?float
    {
        // TODO: Implementar cuando exista el feature Ratings
        return null;
    }
}
