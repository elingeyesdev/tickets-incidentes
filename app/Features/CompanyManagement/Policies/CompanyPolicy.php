<?php

namespace App\Features\CompanyManagement\Policies;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;

class CompanyPolicy
{
    /**
     * Determine if user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        // PLATFORM_ADMIN can view all companies
        // COMPANY_ADMIN can view their own company
        // Other users can view public company list
        return true;
    }

    /**
     * Determine if user can view a specific company.
     */
    public function view(User $user, Company $company): bool
    {
        // PLATFORM_ADMIN can view any company
        if ($user->hasRole('platform_admin')) {
            return true;
        }

        // COMPANY_ADMIN can view their own company
        if ($user->hasRole('company_admin') && $user->hasRoleInCompany('company_admin', $company->id)) {
            return true;
        }

        // AGENT can view their company
        if ($user->hasRole('agent') && $user->hasRoleInCompany('agent', $company->id)) {
            return true;
        }

        // Anyone can view active companies (for exploration)
        return $company->isActive();
    }

    /**
     * Determine if user can create a company.
     */
    public function create(User $user): bool
    {
        // Only PLATFORM_ADMIN can create companies directly
        return $user->hasRole('platform_admin');
    }

    /**
     * Determine if user can update a company.
     */
    public function update(User $user, Company $company): bool
    {
        // PLATFORM_ADMIN can update any company
        if ($user->hasRole('platform_admin')) {
            return true;
        }

        // COMPANY_ADMIN can update their own company
        if ($user->hasRole('company_admin') && $user->hasRoleInCompany('company_admin', $company->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can delete a company.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only PLATFORM_ADMIN can delete companies
        return $user->hasRole('platform_admin');
    }

    /**
     * Determine if user can suspend a company.
     */
    public function suspend(User $user, Company $company): bool
    {
        // Only PLATFORM_ADMIN can suspend companies
        return $user->hasRole('platform_admin');
    }

    /**
     * Determine if user can activate a company.
     */
    public function activate(User $user, Company $company): bool
    {
        // Only PLATFORM_ADMIN can activate companies
        return $user->hasRole('platform_admin');
    }

    /**
     * Determine if user can manage company requests.
     */
    public function manageRequests(User $user): bool
    {
        // Only PLATFORM_ADMIN can approve/reject requests
        return $user->hasRole('platform_admin');
    }

    /**
     * Determine if user can view company statistics.
     */
    public function viewStats(User $user, Company $company): bool
    {
        // PLATFORM_ADMIN can view any company stats
        if ($user->hasRole('platform_admin')) {
            return true;
        }

        // COMPANY_ADMIN can view their own company stats
        if ($user->hasRole('company_admin') && $user->hasRoleInCompany('company_admin', $company->id)) {
            return true;
        }

        // AGENT can view basic stats of their company
        if ($user->hasRole('agent') && $user->hasRoleInCompany('agent', $company->id)) {
            return true;
        }

        return false;
    }
}
