<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Resolvers;

use App\Features\CompanyManagement\GraphQL\DataLoaders\FollowersCountByCompanyIdBatchLoader;
use App\Features\CompanyManagement\GraphQL\DataLoaders\CompanyStatsBatchLoader;
use App\Shared\GraphQL\DataLoaders\UserByIdLoader;

/**
 * Field resolvers para Company type
 *
 * Estos resolvers usan DataLoaders para prevenir N+1 queries
 * cuando GraphQL solicita campos calculados en listas de empresas.
 *
 * IMPORTANTE: Estos resolvers deben registrarse en el schema GraphQL usando:
 * @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@followersCount")
 */
class CompanyFieldResolvers
{
    /**
     * Resolver para Company.followersCount
     *
     * Usa DataLoader para cargar conteos en batch, evitando N+1.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return int
     */
    public function followersCount($company): int
    {
        $loader = app(FollowersCountByCompanyIdBatchLoader::class);
        return $loader->load($company->id);
    }

    /**
     * Resolver para Company.activeAgentsCount
     *
     * Usa DataLoader para cargar stats en batch, evitando N+1.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return int
     */
    public function activeAgentsCount($company): int
    {
        $loader = app(CompanyStatsBatchLoader::class);
        $stats = $loader->load($company->id);
        return $stats['active_agents_count'];
    }

    /**
     * Resolver para Company.totalUsersCount
     *
     * Usa DataLoader para cargar stats en batch, evitando N+1.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return int
     */
    public function totalUsersCount($company): int
    {
        $loader = app(CompanyStatsBatchLoader::class);
        $stats = $loader->load($company->id);
        return $stats['total_users_count'];
    }

    /**
     * Resolver para Company.adminName
     *
     * Usa DataLoader para cargar admin User en batch, evitando N+1.
     * El UserByIdLoader ya carga profiles con eager loading.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return string
     */
    public function adminName($company): string
    {
        $loader = app(UserByIdLoader::class);
        $admin = $loader->load($company->admin_user_id);

        if (!$admin) {
            return 'Unknown';
        }

        $profile = $admin->profile;
        if (!$profile) {
            return $admin->email;
        }

        return $profile->first_name . ' ' . $profile->last_name;
    }

    /**
     * Resolver para Company.adminEmail
     *
     * Usa DataLoader para cargar admin User en batch, evitando N+1.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return string
     */
    public function adminEmail($company): string
    {
        $loader = app(UserByIdLoader::class);
        $admin = $loader->load($company->admin_user_id);

        return $admin?->email ?? 'unknown@example.com';
    }
}
