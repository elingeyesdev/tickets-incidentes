<?php

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Events\CompanyActivated;
use App\Features\CompanyManagement\Events\CompanyCreated;
use App\Features\CompanyManagement\Events\CompanySuspended;
use App\Features\CompanyManagement\Events\CompanyUpdated;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    /**
     * Create a new company.
     */
    public function create(array $data, User $adminUser): Company
    {
        return DB::transaction(function () use ($data, $adminUser) {
            // Generate unique company code
            $companyCode = CodeGenerator::generate('CMP');

            // Create company
            $company = Company::create([
                'company_code' => $companyCode,
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'admin_user_id' => $adminUser->id,
                'support_email' => $data['support_email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
                'contact_address' => $data['contact_address'] ?? null,
                'contact_city' => $data['contact_city'] ?? null,
                'contact_state' => $data['contact_state'] ?? null,
                'contact_country' => $data['contact_country'] ?? null,
                'contact_postal_code' => $data['contact_postal_code'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'legal_representative' => $data['legal_representative'] ?? null,
                'business_hours' => $data['business_hours'] ?? null,
                'timezone' => $data['timezone'] ?? 'America/La_Paz',
                'logo_url' => $data['logo_url'] ?? null,
                'favicon_url' => $data['favicon_url'] ?? null,
                'primary_color' => $data['primary_color'] ?? '#007bff',
                'secondary_color' => $data['secondary_color'] ?? '#6c757d',
                'settings' => $data['settings'] ?? [],
                'status' => 'active',
                'created_from_request_id' => $data['created_from_request_id'] ?? null,
            ]);

            // Fire event
            event(new CompanyCreated($company));

            return $company;
        });
    }

    /**
     * Update an existing company.
     */
    public function update(Company $company, array $data): Company
    {
        DB::transaction(function () use ($company, $data) {
            $company->update(array_filter([
                'name' => $data['name'] ?? null,
                'legal_name' => $data['legal_name'] ?? null,
                'support_email' => $data['support_email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
                'contact_address' => $data['contact_address'] ?? null,
                'contact_city' => $data['contact_city'] ?? null,
                'contact_state' => $data['contact_state'] ?? null,
                'contact_country' => $data['contact_country'] ?? null,
                'contact_postal_code' => $data['contact_postal_code'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'legal_representative' => $data['legal_representative'] ?? null,
                'business_hours' => $data['business_hours'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'logo_url' => $data['logo_url'] ?? null,
                'favicon_url' => $data['favicon_url'] ?? null,
                'primary_color' => $data['primary_color'] ?? null,
                'secondary_color' => $data['secondary_color'] ?? null,
                'settings' => $data['settings'] ?? null,
            ], fn($value) => $value !== null));

            // Fire event
            event(new CompanyUpdated($company));
        });

        return $company->fresh();
    }

    /**
     * Suspend a company (deactivate).
     */
    public function suspend(Company $company, ?string $reason = null): Company
    {
        DB::transaction(function () use ($company, $reason) {
            // Update status
            $company->update(['status' => 'suspended']);

            // Deactivate all agents and company_admins of this company
            $company->userRoles()
                ->whereIn('role_code', ['agent', 'company_admin'])
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Fire event
            event(new CompanySuspended($company, $reason));
        });

        return $company->fresh();
    }

    /**
     * Activate a suspended company.
     */
    public function activate(Company $company): Company
    {
        DB::transaction(function () use ($company) {
            // Update status
            $company->update(['status' => 'active']);

            // Note: We don't auto-reactivate user roles
            // They must be manually reactivated

            // Fire event
            event(new CompanyActivated($company));
        });

        return $company->fresh();
    }

    /**
     * Get company statistics.
     */
    public function getStats(Company $company): array
    {
        return [
            'active_agents_count' => $company->userRoles()
                ->where('role_code', 'agent')
                ->where('is_active', true)
                ->count(),
            'total_users_count' => $company->userRoles()
                ->where('is_active', true)
                ->distinct('user_id')
                ->count('user_id'),
            'followers_count' => $company->followers()->count(),
            'total_tickets_count' => 0, // TODO: Implement when tickets feature is ready
            'open_tickets_count' => 0,  // TODO: Implement when tickets feature is ready
            'average_rating' => 0.0,    // TODO: Implement when ratings feature is ready
        ];
    }

    /**
     * Find company by ID.
     */
    public function findById(string $id): ?Company
    {
        return Company::find($id);
    }

    /**
     * Find company by code.
     */
    public function findByCode(string $code): ?Company
    {
        return Company::where('company_code', $code)->first();
    }

    /**
     * Get all active companies.
     */
    public function getActive(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Company::active()
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if user is admin of company.
     */
    public function isAdmin(Company $company, User $user): bool
    {
        return $company->admin_user_id === $user->id;
    }
}
