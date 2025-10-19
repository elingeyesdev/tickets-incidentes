<?php

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Events\CompanyRequestApproved;
use App\Features\CompanyManagement\Events\CompanyRequestRejected;
use App\Features\CompanyManagement\Events\CompanyRequestSubmitted;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Services\RoleService;
use App\Features\UserManagement\Services\UserService;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Support\Facades\DB;

class CompanyRequestService
{
    public function __construct(
        private CompanyService $companyService,
        private UserService $userService,
        private RoleService $roleService
    ) {}

    /**
     * Enviar una nueva solicitud de empresa.
     */
    public function submit(array $data): CompanyRequest
    {
        return DB::transaction(function () use ($data) {
            // Generar código único de solicitud
            $requestCode = CodeGenerator::generate('REQ');

            // Crear solicitud
            $request = CompanyRequest::create([
                'request_code' => $requestCode,
                'company_name' => $data['company_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'admin_email' => $data['admin_email'],
                'business_description' => $data['business_description'],
                'website' => $data['website'] ?? null,
                'industry_type' => $data['industry_type'],
                'estimated_users' => $data['estimated_users'] ?? null,
                'contact_address' => $data['contact_address'] ?? null,
                'contact_city' => $data['contact_city'] ?? null,
                'contact_country' => $data['contact_country'] ?? null,
                'contact_postal_code' => $data['contact_postal_code'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'status' => 'pending',
            ]);

            // Disparar evento
            event(new CompanyRequestSubmitted($request));

            return $request;
        });
    }

    /**
     * Aprobar una solicitud de empresa.
     *
     * Este es un proceso complejo:
     * 1. Buscar o crear usuario admin
     * 2. Crear empresa
     * 3. Asignar rol COMPANY_ADMIN al usuario admin
     * 4. Marcar solicitud como aprobada
     * 5. Disparar evento (envía email)
     */
    public function approve(CompanyRequest $request, User $reviewer): Company
    {
        // Validar que la solicitud esté pendiente
        if (!$request->isPending()) {
            throw new \Exception('Only pending requests can be approved');
        }

        return DB::transaction(function () use ($request, $reviewer) {
            // 1. Buscar o crear usuario admin
            $adminUser = User::where('email', $request->admin_email)->first();

            if (!$adminUser) {
                // Crear nuevo usuario desde datos de solicitud
                $adminUser = $this->userService->createFromCompanyRequest(
                    $request->admin_email,
                    $request->company_name
                );
            }

            // 2. Crear empresa
            $company = $this->companyService->create([
                'name' => $request->company_name,
                'legal_name' => $request->legal_name,
                'support_email' => $request->admin_email,
                'website' => $request->website,
                'contact_address' => $request->contact_address,
                'contact_city' => $request->contact_city,
                'contact_country' => $request->contact_country,
                'contact_postal_code' => $request->contact_postal_code,
                'tax_id' => $request->tax_id,
                'created_from_request_id' => $request->id,
            ], $adminUser);

            // 3. Asignar rol COMPANY_ADMIN al usuario admin
            $this->roleService->assignRoleToUser(
                userId: $adminUser->id,
                roleCode: 'company_admin',
                companyId: $company->id,
                assignedBy: $reviewer->id
            );

            // 4. Marcar solicitud como aprobada
            $request->markAsApproved($reviewer, $company);

            // 5. Disparar evento (dispara envío de email)
            event(new CompanyRequestApproved($request, $company, $adminUser));

            return $company;
        });
    }

    /**
     * Rechazar una solicitud de empresa.
     */
    public function reject(CompanyRequest $request, User $reviewer, string $reason): CompanyRequest
    {
        // Validar que la solicitud esté pendiente
        if (!$request->isPending()) {
            throw new \Exception('Only pending requests can be rejected');
        }

        DB::transaction(function () use ($request, $reviewer, $reason) {
            // Marcar como rechazada
            $request->markAsRejected($reviewer, $reason);

            // Disparar evento (dispara envío de email)
            event(new CompanyRequestRejected($request, $reason));
        });

        return $request->fresh();
    }

    /**
     * Obtener solicitudes pendientes.
     */
    public function getPending(int $limit = 15): \Illuminate\Database\Eloquent\Collection
    {
        return CompanyRequest::pending()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener todas las solicitudes (para admin).
     */
    public function getAll(?string $status = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = CompanyRequest::query();

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
        return CompanyRequest::where('admin_email', $email)
            ->where('status', 'pending')
            ->exists();
    }
}
