<?php

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Events\CompanyRequestApproved;
use App\Features\CompanyManagement\Events\CompanyRequestRejected;
use App\Features\CompanyManagement\Events\CompanyRequestSubmitted;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyOnboardingDetails;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Services\RoleService;
use App\Features\UserManagement\Services\UserService;
use App\Shared\Errors\ErrorWithExtensions;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Support\Facades\DB;

/**
 * CompanyRequestService
 * 
 * Servicio para gestionar solicitudes de empresas.
 * 
 * ARQUITECTURA NORMALIZADA:
 * - Las solicitudes ahora se crean directamente como Company con status='pending'
 * - Los datos del proceso de onboarding se guardan en CompanyOnboardingDetails
 * - Al aprobar, solo se cambia el status a 'active' (no se duplican datos)
 * - Al rechazar, se cambia el status a 'rejected' y se guarda la razón
 */
class CompanyRequestService
{
    public function __construct(
        private CompanyService $companyService,
        private UserService $userService,
        private RoleService $roleService
    ) {
    }

    /**
     * Enviar una nueva solicitud de empresa.
     * 
     * ANTES: Creaba un CompanyRequest separado
     * AHORA: Crea directamente un Company con status='pending' + CompanyOnboardingDetails
     * 
     * @return Company La empresa creada con status 'pending'
     */
    public function submit(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            // Generar códigos únicos
            $requestCode = CodeGenerator::generate('business.company_onboarding_details', 'REQ', 'request_code');
            $companyCode = CodeGenerator::generate('business.companies', 'CMP', 'company_code');

            // 1. Crear empresa con status 'pending'
            $company = Company::withoutGlobalScope('activeOnly')->create([
                'company_code' => $companyCode,
                'name' => $data['company_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'description' => $data['company_description'],
                'support_email' => $data['admin_email'],
                'website' => $data['website'] ?? null,
                'industry_id' => $data['industry_id'],
                'contact_address' => $data['contact_address'] ?? null,
                'contact_city' => $data['contact_city'] ?? null,
                'contact_country' => $data['contact_country'] ?? null,
                'contact_postal_code' => $data['contact_postal_code'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'status' => 'pending',
                'admin_user_id' => null, // Se asigna al aprobar
            ]);

            // 2. Crear detalles de onboarding
            CompanyOnboardingDetails::create([
                'company_id' => $company->id,
                'request_code' => $requestCode,
                'request_message' => $data['request_message'],
                'estimated_users' => $data['estimated_users'] ?? null,
                'submitter_email' => $data['admin_email'],
            ]);

            // Refrescar para obtener timestamps de la BD y relaciones
            $company->refresh();
            $company->load('onboardingDetails');

            // Disparar evento
            event(new CompanyRequestSubmitted($company));

            return $company;
        });
    }

    /**
     * Aprobar una solicitud de empresa.
     *
     * Este es un proceso simplificado gracias a la normalización:
     * 1. Buscar o crear usuario admin
     * 2. Cambiar status de la empresa a 'active'
     * 3. Asignar rol COMPANY_ADMIN al usuario admin
     * 4. Disparar evento (envía email)
     * 
     * NOTA: Ya no hay "copia" de datos porque la empresa ya existe
     */
    public function approve(Company $company, User $reviewer): Company
    {
        // Validar que la empresa esté pendiente
        if (!$company->isPending()) {
            throw ErrorWithExtensions::validation(
                'Only pending companies can be approved',
                'COMPANY_NOT_PENDING',
                ['companyId' => $company->id, 'currentStatus' => $company->status]
            );
        }

        // Obtener email del solicitante desde onboarding details
        $submitterEmail = $company->onboardingDetails?->submitter_email ?? $company->support_email;

        // Ejecutar dentro de transacción
        $data = DB::transaction(function () use ($company, $reviewer, $submitterEmail) {
            // 1. Buscar o crear usuario admin
            $adminUser = User::where('email', $submitterEmail)->first();

            // Determinar si se está creando un nuevo usuario
            $temporaryPassword = null;

            if (!$adminUser) {
                // Crear nuevo usuario desde datos de solicitud
                $result = $this->userService->createFromCompanyRequest(
                    $submitterEmail,
                    $company->name
                );

                $adminUser = $result['user'];
                $temporaryPassword = $result['temporary_password'];
            }

            // 2. Aprobar la empresa (cambia status + asigna admin)
            $company->approve($adminUser, $reviewer);

            // 3. Asignar rol COMPANY_ADMIN al usuario admin
            $this->roleService->assignRoleToUser(
                userId: $adminUser->id,
                roleCode: 'COMPANY_ADMIN',
                companyId: $company->id,
                assignedBy: $reviewer->id
            );

            return [
                'company' => $company,
                'adminUser' => $adminUser,
                'temporaryPassword' => $temporaryPassword,
            ];
        });

        // 4. Disparar evento DESPUÉS de que la transacción se complete
        event(new CompanyRequestApproved(
            $data['company']->fresh(),
            $data['company'],
            $data['adminUser'],
            $data['temporaryPassword']
        ));

        return $data['company'];
    }

    /**
     * Rechazar una solicitud de empresa.
     */
    public function reject(Company $company, User $reviewer, string $reason): Company
    {
        // Validar que la empresa esté pendiente
        if (!$company->isPending()) {
            throw ErrorWithExtensions::validation(
                'Only pending companies can be rejected',
                'COMPANY_NOT_PENDING',
                ['companyId' => $company->id, 'currentStatus' => $company->status]
            );
        }

        DB::transaction(function () use ($company, $reviewer, $reason) {
            // Marcar como rechazada
            $company->reject($reviewer, $reason);
        });

        // Disparar evento DESPUÉS de que la transacción se complete
        event(new CompanyRequestRejected($company, $reason));

        return $company->fresh();
    }

    /**
     * Obtener empresas pendientes de aprobación.
     */
    public function getPending(int $limit = 15): \Illuminate\Database\Eloquent\Collection
    {
        return Company::pending()
            ->with('onboardingDetails', 'industry')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener todas las empresas por status (para admin).
     */
    public function getAll(?string $status = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = Company::withAllStatuses()->with('onboardingDetails', 'industry');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Verificar si el email ya tiene una solicitud pendiente.
     */
    public function hasPendingRequest(string $email): bool
    {
        return Company::pending()
            ->where('support_email', $email)
            ->exists();
    }

    /**
     * Obtener una empresa pendiente por ID (sin filtro de GlobalScope).
     */
    public function findPendingById(string $id): ?Company
    {
        return Company::pending()
            ->with('onboardingDetails')
            ->find($id);
    }
}
