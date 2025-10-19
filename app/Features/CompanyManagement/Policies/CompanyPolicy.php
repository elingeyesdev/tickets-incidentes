<?php

namespace App\Features\CompanyManagement\Policies;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;

class CompanyPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier empresa.
     */
    public function viewAny(User $user): bool
    {
        // PLATFORM_ADMIN puede ver todas las empresas
        // COMPANY_ADMIN puede ver su propia empresa
        // Otros usuarios pueden ver la lista pública de empresas
        return true;
    }

    /**
     * Determinar si el usuario puede ver una empresa específica.
     */
    public function view(User $user, Company $company): bool
    {
        // PLATFORM_ADMIN puede ver cualquier empresa
        if ($user->hasRole('platform_admin')) {
            return true;
        }

        // COMPANY_ADMIN puede ver su propia empresa
        if ($user->hasRole('company_admin') && $user->hasRoleInCompany('company_admin', $company->id)) {
            return true;
        }

        // AGENT puede ver su empresa
        if ($user->hasRole('agent') && $user->hasRoleInCompany('agent', $company->id)) {
            return true;
        }

        // Cualquiera puede ver empresas activas (para exploración)
        return $company->isActive();
    }

    /**
     * Determinar si el usuario puede crear una empresa.
     */
    public function create(User $user): bool
    {
        // Solo PLATFORM_ADMIN puede crear empresas directamente
        return $user->hasRole('platform_admin');
    }

    /**
     * Determinar si el usuario puede actualizar una empresa.
     */
    public function update(User $user, Company $company): bool
    {
        // PLATFORM_ADMIN puede actualizar cualquier empresa
        if ($user->hasRole('platform_admin')) {
            return true;
        }

        // COMPANY_ADMIN puede actualizar su propia empresa
        if ($user->hasRole('company_admin') && $user->hasRoleInCompany('company_admin', $company->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede eliminar una empresa.
     */
    public function delete(User $user, Company $company): bool
    {
        // Solo PLATFORM_ADMIN puede eliminar empresas
        return $user->hasRole('platform_admin');
    }

    /**
     * Determinar si el usuario puede suspender una empresa.
     */
    public function suspend(User $user, Company $company): bool
    {
        // Solo PLATFORM_ADMIN puede suspender empresas
        return $user->hasRole('platform_admin');
    }

    /**
     * Determinar si el usuario puede activar una empresa.
     */
    public function activate(User $user, Company $company): bool
    {
        // Solo PLATFORM_ADMIN puede activar empresas
        return $user->hasRole('platform_admin');
    }

    /**
     * Determinar si el usuario puede gestionar solicitudes de empresa.
     */
    public function manageRequests(User $user): bool
    {
        // Solo PLATFORM_ADMIN puede aprobar/rechazar solicitudes
        return $user->hasRole('platform_admin');
    }

    /**
     * Determinar si el usuario puede ver estadísticas de empresa.
     */
    public function viewStats(User $user, Company $company): bool
    {
        // PLATFORM_ADMIN puede ver estadísticas de cualquier empresa
        if ($user->hasRole('platform_admin')) {
            return true;
        }

        // COMPANY_ADMIN puede ver estadísticas de su propia empresa
        if ($user->hasRole('company_admin') && $user->hasRoleInCompany('company_admin', $company->id)) {
            return true;
        }

        // AGENT puede ver estadísticas básicas de su empresa
        if ($user->hasRole('agent') && $user->hasRoleInCompany('agent', $company->id)) {
            return true;
        }

        return false;
    }
}
