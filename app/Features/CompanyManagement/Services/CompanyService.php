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
     * Crear una nueva empresa.
     */
    public function create(array $data, User $adminUser): Company
    {
        return DB::transaction(function () use ($data, $adminUser) {
            // Generar código único de empresa
            $companyCode = CodeGenerator::generate('business.companies', 'CMP', 'company_code');

            // Crear empresa
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

            // Disparar evento
            event(new CompanyCreated($company));

            return $company;
        });
    }

    /**
     * Actualizar una empresa existente.
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

            // Disparar evento
            event(new CompanyUpdated($company));
        });

        return $company->fresh();
    }

    /**
     * Suspender una empresa (desactivar).
     */
    public function suspend(Company $company, ?string $reason = null): Company
    {
        DB::transaction(function () use ($company, $reason) {
            // Actualizar estado
            $company->update(['status' => 'suspended']);

            // Desactivar todos los agentes y company_admins de esta empresa
            $company->userRoles()
                ->whereIn('role_code', ['AGENT', 'COMPANY_ADMIN'])
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Disparar evento
            event(new CompanySuspended($company, $reason));
        });

        return $company->fresh();
    }

    /**
     * Activar una empresa suspendida.
     */
    public function activate(Company $company): Company
    {
        DB::transaction(function () use ($company) {
            // Actualizar estado
            $company->update(['status' => 'active']);

            // Nota: No reactivamos automáticamente los roles de usuario
            // Deben ser reactivados manualmente

            // Disparar evento
            event(new CompanyActivated($company));
        });

        return $company->fresh();
    }

    /**
     * Obtener estadísticas de la empresa.
     */
    public function getStats(Company $company): array
    {
        return [
            'active_agents_count' => $company->userRoles()
                ->where('role_code', 'AGENT')
                ->where('is_active', true)
                ->count(),
            'total_users_count' => $company->userRoles()
                ->where('is_active', true)
                ->distinct('user_id')
                ->count('user_id'),
            'followers_count' => $company->followers()->count(),
            'total_tickets_count' => 0, // TODO: Implementar cuando la funcionalidad de tickets esté lista
            'open_tickets_count' => 0,  // TODO: Implementar cuando la funcionalidad de tickets esté lista
            'average_rating' => 0.0,    // TODO: Implementar cuando la funcionalidad de calificaciones esté lista
        ];
    }

    /**
     * Buscar empresa por ID.
     */
    public function findById(string $id): ?Company
    {
        return Company::find($id);
    }

    /**
     * Buscar empresa por código.
     */
    public function findByCode(string $code): ?Company
    {
        return Company::where('company_code', $code)->first();
    }

    /**
     * Obtener todas las empresas activas.
     */
    public function getActive(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Company::active()
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Verificar si el usuario es admin de la empresa.
     */
    public function isAdmin(Company $company, User $user): bool
    {
        return $company->admin_user_id === $user->id;
    }
}
