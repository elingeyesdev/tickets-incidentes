<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Types;

use App\Shared\GraphQL\DataLoaders\UserProfileByUserIdLoader;
use App\Shared\GraphQL\DataLoaders\UserRolesByUserIdLoader;
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
     * Utiliza UserProfileByUserIdLoader para batching automático.
     * Si GraphQL solicita profile de 50 usuarios, ejecuta UNA sola query.
     *
     * @param \App\Features\UserManagement\Models\User $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return \Illuminate\Support\Promise|\App\Features\UserManagement\Models\UserProfile
     */
    public function profile($root, array $args, $context, $resolveInfo)
    {
        return $context->dataLoader(UserProfileByUserIdLoader::class)
            ->load($root->id);
    }

    /**
     * Resuelve el campo 'roleContexts' del tipo User
     *
     * Utiliza UserRolesByUserIdLoader para batching automático.
     * Solo carga roles ACTIVOS (is_active = true).
     * El DataLoader ya incluye las relaciones con Role y Company (eager loading).
     *
     * Transforma los UserRole en formato RoleContext según schema:
     * - roleCode: del UserRole
     * - roleName: del Role relacionado
     * - company: del UserRole (nullable, ya resuelto por el DataLoader)
     * - dashboardPath: del Role relacionado
     *
     * IMPORTANTE: El UserRolesByUserIdLoader ya carga las relaciones con
     * ->with(['role', 'company']), por lo que NO necesitamos otro DataLoader.
     *
     * @param \App\Features\UserManagement\Models\User $root
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @param ResolveInfo|GraphQLResolveInfo $resolveInfo
     * @return \Illuminate\Support\Promise|array
     */
    public function roleContexts($root, array $args, $context, $resolveInfo)
    {
        return $context->dataLoader(UserRolesByUserIdLoader::class)
            ->load($root->id)
            ->then(function ($userRoles) {
                // Transformar colección de UserRole a formato RoleContext
                // Misma estructura que Authentication para consistencia
                return $userRoles->map(function ($userRole) {
                    $roleCode = strtoupper($userRole->role_code);

                    // Mapear dashboard paths según rol
                    $dashboardPaths = [
                        'USER' => '/tickets',
                        'AGENT' => '/agent/dashboard',
                        'COMPANY_ADMIN' => '/admin/dashboard',
                        'PLATFORM_ADMIN' => '/platform/dashboard',
                    ];

                    // Mapear nombres legibles de roles
                    $roleNames = [
                        'USER' => 'Cliente',
                        'AGENT' => 'Agente de Soporte',
                        'COMPANY_ADMIN' => 'Administrador de Empresa',
                        'PLATFORM_ADMIN' => 'Administrador de Plataforma',
                    ];

                    $context = [
                        'roleCode' => $roleCode,
                        'roleName' => $roleNames[$roleCode] ?? ($userRole->role->role_name ?? 'Unknown'),
                        'dashboardPath' => $dashboardPaths[$roleCode] ?? '/dashboard',
                    ];

                    // Agregar company solo si el rol requiere empresa
                    // USER y PLATFORM_ADMIN: company es null
                    // AGENT y COMPANY_ADMIN: company tiene datos
                    if ($userRole->company) {
                        $context['company'] = [
                            'id' => $userRole->company->id,
                            'companyCode' => $userRole->company->company_code,
                            'name' => $userRole->company->name,
                            'logoUrl' => $userRole->company->logo_url,
                        ];
                    } else {
                        $context['company'] = null;
                    }

                    return $context;
                })->values()->toArray();
            });
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
