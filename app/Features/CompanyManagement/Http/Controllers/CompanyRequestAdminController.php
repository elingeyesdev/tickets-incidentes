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
        summary: 'Approve company request',
        description: 'Approves a company request. Automatically: creates company, creates admin user, assigns COMPANY_ADMIN role, generates temporary password (valid 7 days), sends email with credentials',
        security: [['bearerAuth' => []]],
        tags: ['Company Requests - Admin'],
        parameters: [
            new OA\Parameter(
                name: 'companyRequest',
                description: 'Company request UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            description: 'Additional approval data (optional)',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'notes', type: 'string', description: 'Additional notes', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Request approved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'success', type: 'boolean'),
                            new OA\Property(property: 'message', type: 'string'),
                            new OA\Property(
                                property: 'company',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'companyCode', type: 'string'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'legalName', type: 'string', nullable: true),
                                    new OA\Property(property: 'status', type: 'string'),
                                    new OA\Property(property: 'adminId', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'adminEmail', type: 'string', format: 'email'),
                                    new OA\Property(property: 'adminName', type: 'string'),
                                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
                                ]
                            ),
                            new OA\Property(property: 'newUserCreated', type: 'boolean'),
                            new OA\Property(property: 'notificationSentTo', type: 'string', format: 'email')
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - requires PLATFORM_ADMIN role'),
            new OA\Response(response: 404, description: 'Request not found'),
            new OA\Response(response: 409, description: 'Request already processed'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
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

        return (new CompanyApprovalResource($data))->response();
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
        summary: 'Reject company request',
        description: 'Rejects a company request. Automatically: marks as REJECTED, sends email to requester with rejection reason',
        security: [['bearerAuth' => []]],
        tags: ['Company Requests - Admin'],
        parameters: [
            new OA\Parameter(
                name: 'companyRequest',
                description: 'Company request UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Rejection reason',
            content: new OA\JsonContent(
                type: 'object',
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', description: 'Rejection reason (required, minimum 3 characters)', minLength: 3),
                    new OA\Property(property: 'notes', type: 'string', description: 'Additional notes', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Request rejected successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'success', type: 'boolean'),
                            new OA\Property(property: 'message', type: 'string'),
                            new OA\Property(property: 'reason', type: 'string', description: 'Rejection reason'),
                            new OA\Property(property: 'notification_sent_to', type: 'string', format: 'email'),
                            new OA\Property(property: 'request_code', type: 'string')
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - requires PLATFORM_ADMIN role'),
            new OA\Response(response: 404, description: 'Request not found'),
            new OA\Response(response: 409, description: 'Request already processed'),
            new OA\Response(response: 422, description: 'Validation error - reason is required'),
        ]
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

        return (new CompanyRejectionResource($data))->response();
    }
}
