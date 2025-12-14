<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use App\Shared\Helpers\JWTHelper;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Features\CompanyManagement\Http\Requests\ApproveCompanyRequestRequest;
use App\Features\CompanyManagement\Http\Requests\RejectCompanyRequestRequest;
use App\Features\CompanyManagement\Http\Resources\CompanyApprovalResource;
use App\Features\CompanyManagement\Http\Resources\CompanyRejectionResource;
use App\Features\AuditLog\Services\ActivityLogService;
use OpenApi\Attributes as OA;

/**
 * CompanyRequestAdminController
 *
 * Controlador REST para acciones administrativas sobre solicitudes de empresas.
 * Solo accesible por PLATFORM_ADMIN.
 *
 * Métodos implementados:
 * - index() - GET /app/admin/requests (Vista Blade)
 * - approve() - POST /api/v1/company-requests/{companyRequest}/approve
 * - reject() - POST /api/v1/company-requests/{companyRequest}/reject
 *
 * @package App\Features\CompanyManagement\Http\Controllers
 */
class CompanyRequestAdminController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    /**
     * Display the company requests management view
     *
     * Renders the view for managing company requests. All data is loaded
     * dynamically via AJAX from the frontend following SPA pattern.
     * The controller only renders the view with authenticated user data.
     *
     * @return View
     */
    public function index(): View
    {
        // Middleware already validated JWT and role (jwt.require + role:PLATFORM_ADMIN)
        // No need to fetch user from backend - JWT defines everything
        return view('app.platform-admin.requests.index');
    }
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
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a')
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
                            new OA\Property(property: 'success', type: 'boolean', example: true),
                            new OA\Property(property: 'message', type: 'string', example: "Solicitud aprobada exitosamente. Se ha creado la empresa 'TechCorp Bolivia' y se envió un email con las credenciales de acceso a admin@techcorp.com.bo."),
                            new OA\Property(
                                property: 'company',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a'),
                                    new OA\Property(property: 'companyCode', type: 'string', example: 'COMP-20250001'),
                                    new OA\Property(property: 'name', type: 'string', example: 'TechCorp Bolivia'),
                                    new OA\Property(property: 'legalName', type: 'string', nullable: true, example: 'TechCorp Bolivia S.R.L.'),
                                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Empresa líder en soluciones tecnológicas para el sector empresarial'),
                                    new OA\Property(property: 'status', type: 'string', example: 'ACTIVE'),
                                    new OA\Property(property: 'industryId', type: 'string', format: 'uuid', nullable: true, example: '7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f'),
                                    new OA\Property(
                                        property: 'industry',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f'),
                                            new OA\Property(property: 'code', type: 'string', example: 'TECH'),
                                            new OA\Property(property: 'name', type: 'string', example: 'Tecnología')
                                        ]
                                    ),
                                    new OA\Property(property: 'adminId', type: 'string', format: 'uuid', example: '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d'),
                                    new OA\Property(property: 'adminEmail', type: 'string', format: 'email', example: 'admin@techcorp.com.bo'),
                                    new OA\Property(property: 'adminName', type: 'string', example: 'Juan Carlos Pérez'),
                                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-11-01T10:30:00+00:00')
                                ]
                            ),
                            new OA\Property(property: 'newUserCreated', type: 'boolean', example: true),
                            new OA\Property(property: 'notificationSentTo', type: 'string', format: 'email', example: 'admin@techcorp.com.bo')
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
        string $companyRequest, // Recibe UUID como string para usar scope manual
        ApproveCompanyRequestRequest $request,
        CompanyRequestService $requestService
    ): JsonResponse {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Usar empresa ya validada por el Form Request (evita doble búsqueda)
        $company = $request->pendingCompany ?? Company::pending()->with('onboardingDetails')->findOrFail($companyRequest);
        $company->loadMissing('onboardingDetails');

        // Aprobar la solicitud usando el Service
        $approvedCompany = $requestService->approve($company, $currentUser);

        // Registrar actividad
        $this->activityLogService->logCompanyRequestApproved(
            adminId: $currentUser->id,
            requestId: $company->id, // Ahora la empresa ES la solicitud
            companyName: $approvedCompany->name,
            createdCompanyId: $approvedCompany->id,
            adminEmail: $approvedCompany->admin->email
        );

        // Determinar si se creó nuevo usuario (verificar propiedad wasRecentlyCreated)
        $adminUser = $approvedCompany->admin;
        $newUserCreated = property_exists($adminUser, 'wasRecentlyCreated') && $adminUser->wasRecentlyCreated;

        // Construir mensaje según si es usuario nuevo o existente
        $message = $newUserCreated
            ? "Solicitud aprobada exitosamente. Se ha creado la empresa '{$approvedCompany->name}' y se envió un email con las credenciales de acceso a {$adminUser->email}."
            : "Solicitud aprobada exitosamente. Se ha creado la empresa '{$approvedCompany->name}' y se asignó el rol de administrador al usuario existente.";

        // Preparar datos para el Resource
        $data = [
            'message' => $message,
            'company' => $approvedCompany,
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
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Rejection reason',
            content: new OA\JsonContent(
                type: 'object',
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', description: 'Rejection reason (required, minimum 10 characters)', minLength: 10),
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
                            new OA\Property(property: 'success', type: 'boolean', example: true),
                            new OA\Property(property: 'message', type: 'string', example: "La solicitud de empresa 'TechCorp Bolivia' ha sido rechazada. Se ha enviado un email a admin@techcorp.com.bo con la razón del rechazo."),
                            new OA\Property(property: 'reason', type: 'string', description: 'Rejection reason', example: 'La documentación proporcionada no cumple con los requisitos mínimos establecidos. Por favor, adjunte el NIT actualizado y el testimonio de constitución.'),
                            new OA\Property(property: 'notificationSentTo', type: 'string', format: 'email', example: 'admin@techcorp.com.bo'),
                            new OA\Property(property: 'requestCode', type: 'string', example: 'REQ-20250001')
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
        string $companyRequest, // Recibe UUID como string para usar scope manual
        RejectCompanyRequestRequest $request,
        CompanyRequestService $requestService
    ): JsonResponse {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Usar empresa ya validada por el Form Request (evita doble búsqueda)
        $company = $request->pendingCompany ?? Company::pending()->with('onboardingDetails')->findOrFail($companyRequest);
        $company->loadMissing('onboardingDetails');

        // Guardar datos antes del rechazo (el Service puede modificar el objeto)
        $companyName = $company->name;
        $onboardingDetails = $company->onboardingDetails;
        $requestCode = $onboardingDetails?->request_code ?? $company->company_code;
        $notificationEmail = $onboardingDetails?->submitter_email ?? $company->support_email;
        $companyId = $company->id;

        // Rechazar la solicitud usando el Service
        $rejected = $requestService->reject(
            $company,
            $currentUser,
            $request->reason
        );

        // Registrar actividad
        $this->activityLogService->logCompanyRequestRejected(
            adminId: $currentUser->id,
            requestId: $companyId,
            companyName: $companyName,
            reason: $request->reason
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
