<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Features\CompanyManagement\Http\Requests\ApproveCompanyRequestRequest;
use App\Features\CompanyManagement\Http\Requests\RejectCompanyRequestRequest;
use App\Features\CompanyManagement\Http\Resources\CompanyApprovalResource;
use App\Features\CompanyManagement\Http\Resources\CompanyRejectionResource;

/**
 * CompanyRequestAdminController
 *
 * Controlador REST para acciones administrativas sobre solicitudes de empresas.
 * Solo accesible por PLATFORM_ADMIN.
 *
 * Métodos implementados:
 * - approve() - POST /api/v1/company-requests/{companyRequest}/approve
 * - reject() - POST /api/v1/company-requests/{companyRequest}/reject
 *
 * @package App\Features\CompanyManagement\Http\Controllers
 */
class CompanyRequestAdminController extends Controller
{
    /**
     * Aprobar solicitud de empresa
     *
     * Aprueba una solicitud pendiente de empresa y crea la empresa automáticamente.
     * Si el admin_email no existe, crea un nuevo usuario con credenciales generadas.
     * Si existe, le asigna el rol COMPANY_ADMIN a ese usuario.
     *
     * Endpoint: POST /api/v1/company-requests/{companyRequest}/approve
     * Auth: Requiere PLATFORM_ADMIN
     *
     * @param CompanyRequest $companyRequest Solicitud a aprobar (route model binding)
     * @param ApproveCompanyRequestRequest $request Request validado
     * @param CompanyRequestService $requestService Servicio de solicitudes
     * @return CompanyApprovalResource
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException Si no tiene permiso
     * @throws \Illuminate\Validation\ValidationException Si la solicitud no está en estado 'pending'
     */
    public function approve(
        CompanyRequest $companyRequest,
        ApproveCompanyRequestRequest $request,
        CompanyRequestService $requestService
    ): CompanyApprovalResource {
        // Aprobar la solicitud usando el Service
        $company = $requestService->approve($companyRequest, JWTHelper::getAuthenticatedUser());

        // Determinar si se creó nuevo usuario (verificar propiedad wasRecentlyCreated)
        $adminUser = $company->admin;
        $newUserCreated = property_exists($adminUser, 'wasRecentlyCreated') && $adminUser->wasRecentlyCreated;

        // Construir mensaje según si es usuario nuevo o existente
        $message = $newUserCreated
            ? "Solicitud aprobada exitosamente. Se ha creado la empresa '{$company->name}' y se envió un email con las credenciales de acceso a {$adminUser->email}."
            : "Solicitud aprobada exitosamente. Se ha creado la empresa '{$company->name}' y se asignó el rol de administrador al usuario existente.";

        // Preparar datos para el Resource
        $data = [
            'message' => $message,
            'company' => $company,
            'admin_email' => $adminUser->email,
            'admin_name' => $adminUser->profile->display_name ?? $adminUser->email,
            'new_user_created' => $newUserCreated,
            'notification_sent_to' => $adminUser->email,
        ];

        return new CompanyApprovalResource($data);
    }

    /**
     * Rechazar solicitud de empresa
     *
     * Rechaza una solicitud pendiente de empresa con una razón específica.
     * Envía un email de notificación al solicitante con la razón del rechazo.
     *
     * Endpoint: POST /api/v1/company-requests/{companyRequest}/reject
     * Auth: Requiere PLATFORM_ADMIN
     *
     * @param CompanyRequest $companyRequest Solicitud a rechazar (route model binding)
     * @param RejectCompanyRequestRequest $request Request validado (contiene 'reason')
     * @param CompanyRequestService $requestService Servicio de solicitudes
     * @return CompanyRejectionResource
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException Si no tiene permiso
     * @throws \Illuminate\Validation\ValidationException Si la solicitud no está en estado 'pending'
     */
    public function reject(
        CompanyRequest $companyRequest,
        RejectCompanyRequestRequest $request,
        CompanyRequestService $requestService
    ): CompanyRejectionResource {
        // Guardar datos antes del rechazo (el Service puede modificar el objeto)
        $companyName = $companyRequest->company_name;
        $requestCode = $companyRequest->request_code;
        $notificationEmail = $companyRequest->admin_email;

        // Rechazar la solicitud usando el Service
        $rejected = $requestService->reject(
            $companyRequest,
            JWTHelper::getAuthenticatedUser(),
            $request->reason
        );

        // Preparar datos para el Resource
        $data = [
            'message' => "La solicitud de empresa '{$companyName}' ha sido rechazada. Se ha enviado un email a {$notificationEmail} con la razón del rechazo.",
            'reason' => $request->reason,
            'notification_sent_to' => $notificationEmail,
            'request_code' => $requestCode,
        ];

        return new CompanyRejectionResource($data);
    }
}
