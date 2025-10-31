<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Features\CompanyManagement\Http\Requests\ApproveCompanyRequestRequest;
use App\Features\CompanyManagement\Http\Requests\RejectCompanyRequestRequest;
use App\Features\CompanyManagement\Http\Resources\CompanyApprovalResource;
use App\Features\CompanyManagement\Http\Resources\CompanyRejectionResource;
use OpenApi\Attributes as OA;

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
     * @return JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException Si no tiene permiso
     * @throws \Illuminate\Validation\ValidationException Si la solicitud no está en estado 'pending'
     */
    #[OA\Post(
        path: '/api/company-requests/{companyRequest}/approve',
        operationId: 'approve_company_request',
        summary: 'Aprobar solicitud de empresa',
        description: 'Aprueba una solicitud de empresa. Automáticamente: crea la empresa, crea usuario admin, asigna rol COMPANY_ADMIN, genera contraseña temporal (válida 7 días), envía email con credenciales',
        security: [['bearerAuth' => []]],
        tags: ['Company Requests - Admin']
    )]
    #[OA\Parameter(
        name: 'companyRequest',
        description: 'UUID de la solicitud de empresa',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'notes', type: 'string', description: 'Observaciones adicionales', nullable: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Solicitud aprobada exitosamente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'status', type: 'string', example: 'APPROVED'),
                new OA\Property(
                    property: 'company',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'companyCode', type: 'string')
                    ]
                ),
                new OA\Property(
                    property: 'adminUser',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'temporaryPassword', type: 'string', description: 'Contraseña temporal (solo si es usuario nuevo)')
                    ]
                ),
                new OA\Property(property: 'approvedAt', type: 'string', format: 'date-time')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'No autenticado',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Sin permisos - requiere rol PLATFORM_ADMIN',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Solicitud no encontrada',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Company request not found')
            ]
        )
    )]
    #[OA\Response(
        response: 409,
        description: 'Solicitud ya procesada',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Company request already processed')
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Error de validación',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))
                )
            ]
        )
    )]
    public function approve(
        CompanyRequest $companyRequest,
        ApproveCompanyRequestRequest $request,
        CompanyRequestService $requestService
    ): JsonResponse {
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
     * @return JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException Si no tiene permiso
     * @throws \Illuminate\Validation\ValidationException Si la solicitud no está en estado 'pending'
     */
    #[OA\Post(
        path: '/api/company-requests/{companyRequest}/reject',
        operationId: 'reject_company_request',
        summary: 'Rechazar solicitud de empresa',
        description: 'Rechaza una solicitud de empresa. Automáticamente: marca como REJECTED, envía email al solicitante con motivo del rechazo',
        security: [['bearerAuth' => []]],
        tags: ['Company Requests - Admin']
    )]
    #[OA\Parameter(
        name: 'companyRequest',
        description: 'UUID de la solicitud de empresa',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reason'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', description: 'Motivo del rechazo (requerido)'),
                new OA\Property(property: 'notes', type: 'string', description: 'Observaciones adicionales', nullable: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Solicitud rechazada exitosamente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'status', type: 'string', example: 'REJECTED'),
                new OA\Property(property: 'rejectedAt', type: 'string', format: 'date-time'),
                new OA\Property(property: 'reason', type: 'string', description: 'Motivo del rechazo')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'No autenticado',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Sin permisos - requiere rol PLATFORM_ADMIN',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Solicitud no encontrada',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Company request not found')
            ]
        )
    )]
    #[OA\Response(
        response: 409,
        description: 'Solicitud ya procesada',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Company request already processed')
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Error de validación',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))
                )
            ]
        )
    )]
    public function reject(
        CompanyRequest $companyRequest,
        RejectCompanyRequestRequest $request,
        CompanyRequestService $requestService
    ): JsonResponse {
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
