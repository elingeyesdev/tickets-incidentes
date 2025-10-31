<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Features\CompanyManagement\Http\Requests\StoreCompanyRequestRequest;
use App\Features\CompanyManagement\Http\Resources\CompanyRequestResource;
use OpenApi\Attributes as OA;

/**
 * CompanyRequestController
 *
 * Controlador REST para gestión de solicitudes de empresas.
 *
 * Métodos implementados:
 * - index() - GET /api/v1/company-requests (PLATFORM_ADMIN)
 * - store() - POST /api/v1/company-requests (PÚBLICO con rate limit)
 *
 * Migración: GraphQL → REST
 * Service: CompanyRequestService (se usa, NO se modifica)
 */
class CompanyRequestController extends Controller
{
    /**
     * Lista de solicitudes de empresas (solo PLATFORM_ADMIN).
     */
    #[OA\Get(
        path: '/api/company-requests',
        operationId: 'list_company_requests',
        summary: 'Listar solicitudes de empresas',
        description: 'Retorna lista paginada de solicitudes de empresas. Requiere rol PLATFORM_ADMIN. Incluye eager loading de reviewer y createdCompany.',
        tags: ['Company Requests'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Filtrar por estado de la solicitud',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['PENDING', 'APPROVED', 'REJECTED']
                )
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Buscar por nombre de empresa',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                required: false,
                description: 'Campo para ordenar',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Dirección de ordenamiento',
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Número de página',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de solicitudes de empresas',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'requestCode', type: 'string'),
                                    new OA\Property(property: 'companyName', type: 'string'),
                                    new OA\Property(property: 'legalName', type: 'string', nullable: true),
                                    new OA\Property(property: 'adminEmail', type: 'string', format: 'email'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['PENDING', 'APPROVED', 'REJECTED']),
                                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                            ]
                        ),
                        new OA\Property(
                            property: 'links',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'first', type: 'string', nullable: true),
                                new OA\Property(property: 'last', type: 'string', nullable: true),
                                new OA\Property(property: 'prev', type: 'string', nullable: true),
                                new OA\Property(property: 'next', type: 'string', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Sin autenticación'),
            new OA\Response(response: 403, description: 'Sin permisos - requiere rol PLATFORM_ADMIN'),
        ],
        security: [['bearerAuth' => []]]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = CompanyRequest::query()
            ->with(['reviewer.profile', 'createdCompany']);

        // Filtro por status (convertir a lowercase para compatibilidad con DB)
        if ($request->filled('status')) {
            $query->where('status', strtolower($request->status));
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => CompanyRequestResource::collection($requests->items()),
            'meta' => [
                'total' => $requests->total(),
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
            ],
            'links' => [
                'first' => $requests->url(1),
                'last' => $requests->url($requests->lastPage()),
                'prev' => $requests->previousPageUrl(),
                'next' => $requests->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Crear solicitud de empresa (público, con rate limit).
     */
    #[OA\Post(
        path: '/api/company-requests',
        operationId: 'create_company_request',
        summary: 'Crear solicitud de empresa',
        description: 'Endpoint público para crear solicitud de nueva empresa. Rate limit: 3 solicitudes por hora. Validación automática de email duplicado en solicitudes pendientes.',
        tags: ['Company Requests'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Datos de la solicitud de empresa',
            content: new OA\JsonContent(
                type: 'object',
                required: ['company_name', 'admin_email', 'business_description', 'industry_type'],
                properties: [
                    new OA\Property(
                        property: 'company_name',
                        type: 'string',
                        description: 'Nombre de la empresa',
                        minLength: 2,
                        maxLength: 200
                    ),
                    new OA\Property(
                        property: 'legal_name',
                        type: 'string',
                        description: 'Nombre legal de la empresa',
                        nullable: true,
                        minLength: 2,
                        maxLength: 200
                    ),
                    new OA\Property(
                        property: 'admin_email',
                        type: 'string',
                        format: 'email',
                        description: 'Email del administrador',
                        maxLength: 255
                    ),
                    new OA\Property(
                        property: 'business_description',
                        type: 'string',
                        description: 'Descripción del negocio',
                        minLength: 50,
                        maxLength: 2000
                    ),
                    new OA\Property(
                        property: 'website',
                        type: 'string',
                        format: 'uri',
                        description: 'Sitio web de la empresa',
                        nullable: true,
                        maxLength: 255
                    ),
                    new OA\Property(
                        property: 'industry_type',
                        type: 'string',
                        description: 'Tipo de industria',
                        maxLength: 100
                    ),
                    new OA\Property(
                        property: 'estimated_users',
                        type: 'integer',
                        description: 'Número estimado de usuarios',
                        nullable: true,
                        minimum: 1,
                        maximum: 10000
                    ),
                    new OA\Property(
                        property: 'contact_address',
                        type: 'string',
                        description: 'Dirección de contacto',
                        nullable: true,
                        maxLength: 255
                    ),
                    new OA\Property(
                        property: 'contact_city',
                        type: 'string',
                        description: 'Ciudad',
                        nullable: true,
                        maxLength: 100
                    ),
                    new OA\Property(
                        property: 'contact_country',
                        type: 'string',
                        description: 'País',
                        nullable: true,
                        maxLength: 100
                    ),
                    new OA\Property(
                        property: 'contact_postal_code',
                        type: 'string',
                        description: 'Código postal',
                        nullable: true,
                        maxLength: 20
                    ),
                    new OA\Property(
                        property: 'tax_id',
                        type: 'string',
                        description: 'Identificador fiscal',
                        nullable: true,
                        maxLength: 50
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Solicitud de empresa creada exitosamente',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'requestCode', type: 'string'),
                        new OA\Property(property: 'companyName', type: 'string'),
                        new OA\Property(property: 'adminEmail', type: 'string', format: 'email'),
                        new OA\Property(property: 'status', type: 'string', enum: ['PENDING']),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Error de validación - datos inválidos o email duplicado'),
            new OA\Response(response: 429, description: 'Rate limit excedido - máximo 3 solicitudes por hora'),
        ]
    )]
    public function store(StoreCompanyRequestRequest $request, CompanyRequestService $requestService): JsonResponse
    {
        $companyRequest = $requestService->submit($request->validated());

        return (new CompanyRequestResource($companyRequest))
            ->response()
            ->setStatusCode(201);
    }
}
