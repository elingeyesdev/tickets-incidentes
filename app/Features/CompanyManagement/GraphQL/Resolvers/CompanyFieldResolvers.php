<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Resolvers;

use App\Shared\Helpers\JWTHelper;

/**
 * Field resolvers para Company type
 *
 * Estos resolvers usan relaciones Eloquent y queries directas.
 *
 * IMPORTANTE: Estos resolvers deben registrarse en el schema GraphQL usando:
 * @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@followersCount")
 */
class CompanyFieldResolvers
{
    /**
     * Resolver para Company.followersCount
     *
     * Cuenta los followers directamente.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return int
     */
    public function followersCount($company): int
    {
        // Contar followers directamente
        return \App\Features\CompanyManagement\Models\CompanyFollower::where('company_id', $company->id)
            ->count();
    }

    /**
     * Resolver para Company.activeAgentsCount
     *
     * Cuenta usuarios con rol AGENT en esta empresa.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return int
     */
    public function activeAgentsCount($company): int
    {
        // Contar usuarios con rol AGENT en esta empresa
        return \App\Features\UserManagement\Models\UserRole::where('company_id', $company->id)
            ->whereHas('role', function ($query) {
                $query->where('role_code', 'AGENT');
            })
            ->whereHas('user', function ($query) {
                $query->where('status', 'ACTIVE');
            })
            ->count();
    }

    /**
     * Resolver para Company.totalUsersCount
     *
     * Cuenta todos los usuarios de esta empresa.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return int
     */
    public function totalUsersCount($company): int
    {
        // Contar todos los usuarios de esta empresa
        return \App\Features\UserManagement\Models\UserRole::where('company_id', $company->id)
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Resolver para Company.adminName
     *
     * Usa relación Eloquent directamente.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return string
     */
    public function adminName($company): string
    {
        // Usar relación Eloquent directamente
        $admin = $company->admin;

        if (!$admin) {
            return 'Unknown';
        }

        $profile = $admin->profile;
        if (!$profile) {
            return 'Unknown';
        }

        return trim("{$profile->first_name} {$profile->last_name}");
    }

    /**
     * Resolver para Company.adminEmail
     *
     * Usa relación Eloquent directamente.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @return string
     */
    public function adminEmail($company): string
    {
        // Usar relación Eloquent directamente
        $admin = $company->admin;

        return $admin ? $admin->email : 'unknown@example.com';
    }

    /**
     * Resolver para isFollowedByMe
     *
     * Verifica si el usuario autenticado sigue esta empresa.
     *
     * @param \App\Features\CompanyManagement\Models\Company $company
     * @param array $args
     * @param mixed $context
     * @return bool
     */
    public function isFollowedByMe($company, array $args = [], $context = null): bool
    {
        // Get request from context if available
        $request = $context ? $context->request() : request();

        // Check if user is authenticated via JWT
        $user = $request->attributes->get('jwt_user');

        if (!$user) {
            return false;
        }

        return \App\Features\CompanyManagement\Models\CompanyFollower::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->exists();
    }
}
