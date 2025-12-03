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
        summary: 'List company requests',
        description: 'Returns paginated list of company requests. Requires PLATFORM_ADMIN role. Includes eager loading of reviewer and createdCompany.',
        tags: ['Company Requests'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Filter by request status',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['PENDING', 'APPROVED', 'REJECTED']
                )
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Search by company name',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                required: false,
                description: 'Field to sort by',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Sort direction',
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Number of items per page',
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number',
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of company requests',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                    new OA\Property(property: 'requestCode', type: 'string', example: 'REQ-20251101-001'),
                                    new OA\Property(property: 'companyName', type: 'string', example: 'TechCorp Solutions'),
                                    new OA\Property(property: 'legalName', type: 'string', nullable: true, example: 'TechCorp Solutions S.A.'),
                                    new OA\Property(property: 'adminEmail', type: 'string', format: 'email', example: 'admin@techcorp.com'),
                                    new OA\Property(property: 'businessDescription', type: 'string', nullable: true, example: 'We are a leading technology solutions company with over 10 years of experience providing enterprise software solutions to businesses worldwide.'),
                                    new OA\Property(property: 'requestMessage', type: 'string', nullable: true, example: 'We need a professional helpdesk system for our customer support team of 50+ agents.'),
                                    new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true, example: 'https://techcorp.com'),
                                    new OA\Property(property: 'industryId', type: 'string', format: 'uuid', nullable: true, example: '650e8400-e29b-41d4-a716-446655440001'),
                                    new OA\Property(
                                        property: 'industry',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '650e8400-e29b-41d4-a716-446655440001'),
                                            new OA\Property(property: 'code', type: 'string', example: 'TECH'),
                                            new OA\Property(property: 'name', type: 'string', example: 'Technology'),
                                        ]
                                    ),
                                    new OA\Property(property: 'estimatedUsers', type: 'integer', nullable: true, example: 500),
                                    new OA\Property(property: 'contactAddress', type: 'string', nullable: true, example: 'Main Avenue 123, Office 456'),
                                    new OA\Property(property: 'contactCity', type: 'string', nullable: true, example: 'Santiago'),
                                    new OA\Property(property: 'contactCountry', type: 'string', nullable: true, example: 'Chile'),
                                    new OA\Property(property: 'contactPostalCode', type: 'string', nullable: true, example: '8340000'),
                                    new OA\Property(property: 'taxId', type: 'string', nullable: true, example: '12.345.678-9'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['PENDING', 'APPROVED', 'REJECTED'], example: 'APPROVED'),
                                    new OA\Property(property: 'reviewedAt', type: 'string', format: 'date-time', nullable: true, example: '2025-11-01T14:30:00Z'),
                                    new OA\Property(property: 'rejectionReason', type: 'string', nullable: true, example: null),
                                    new OA\Property(
                                        property: 'reviewer',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '750e8400-e29b-41d4-a716-446655440002'),
                                            new OA\Property(property: 'user_code', type: 'string', example: 'USR-ADMIN-001'),
                                            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'platform.admin@helpdesk.com'),
                                            new OA\Property(property: 'name', type: 'string', example: 'John Administrator'),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: 'createdCompany',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '850e8400-e29b-41d4-a716-446655440003'),
                                            new OA\Property(property: 'companyCode', type: 'string', example: 'COMP-TECH-001'),
                                            new OA\Property(property: 'name', type: 'string', example: 'TechCorp Solutions'),
                                            new OA\Property(property: 'logoUrl', type: 'string', nullable: true, example: 'https://storage.example.com/logos/techcorp.png'),
                                        ]
                                    ),
                                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-11-01T10:00:00Z'),
                                    new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', example: '2025-11-01T14:30:00Z'),
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - requires PLATFORM_ADMIN role'),
        ],
        security: [['bearerAuth' => []]]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = CompanyRequest::query()
            ->with(['reviewer.profile', 'createdCompany.industry', 'industry']);

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
     *
     * VALIDACIÓN DE DUPLICADOS - 4 CAPAS:
     *
     * 1. TAX ID (NIT/RUC)
     *    - Validación: Exacta (0 tolerancia)
     *    - Acción: Bloquea creación (error)
     *    - Fuente: Solicitudes pendientes + Empresas registradas
     *    - Mensaje: Especifica empresa/solicitud duplicada
     *
     * 2. ADMIN EMAIL + NOMBRE SIMILAR
     *    - Validación: Email exacto + similitud de nombre
     *    - Umbrales:
     *      * Similitud > 70%: Bloquea creación (error sospechoso)
     *      * Similitud 30-70%: Advertencia (no bloquea)
     *    - Fuente: support_email en empresas registradas
     *    - Intención: Prevenir misma persona creando empresa duplicada
     *
     * 3. WEBSITE DOMAIN + NOMBRE SIMILAR
     *    - Validación: Dominio exacto + similitud de nombre
     *    - Umbral: Similitud > 50% = Bloquea creación (error)
     *    - Fuente: Empresas registradas
     *    - Intención: Prevenir usar mismo dominio corporativo
     *
     * 4. NOMBRE MUY SIMILAR
     *    - Validación: Similitud de nombre > 85%
     *    - Acción: Advertencia (NO bloquea, solo alerta)
     *    - Fuente: Empresas registradas + Solicitudes pendientes
     *    - Mensaje: "ADVERTENCIA: Ya existe empresa con nombre muy similar"
     *
     * Algoritmo: Levenshtein Distance normalizado (0-1) después de normalizar nombres.
     * Normalización: lowercase + sin acentos + solo alfanuméricos
     */
    #[OA\Post(
        path: '/api/company-requests',
        operationId: 'create_company_request',
        summary: 'Create company request',
        description: 'Public endpoint to create new company request. Rate limit: 3 requests per hour. Includes advanced duplicate detection using tax ID, email, website domain, and name similarity algorithms.',
        tags: ['Company Requests'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Company request data',
            content: new OA\JsonContent(
                type: 'object',
                required: ['company_name', 'admin_email', 'company_description', 'industry_id'],
                properties: [
                    new OA\Property(
                        property: 'company_name',
                        type: 'string',
                        description: 'Company name (2-200 characters)',
                        minLength: 2,
                        maxLength: 200,
                        example: 'TechCorp Solutions'
                    ),
                    new OA\Property(
                        property: 'legal_name',
                        type: 'string',
                        description: 'Legal company name (2-200 characters)',
                        nullable: true,
                        minLength: 2,
                        maxLength: 200,
                        example: 'TechCorp Solutions S.A.'
                    ),
                    new OA\Property(
                        property: 'admin_email',
                        type: 'string',
                        format: 'email',
                        description: 'Administrator email (max 255 characters)',
                        maxLength: 255,
                        example: 'admin@techcorp.com'
                    ),
                    new OA\Property(
                        property: 'company_description',
                        type: 'string',
                        description: 'Company description (50-1000 characters)',
                        minLength: 50,
                        maxLength: 1000,
                        example: 'We are a leading technology solutions company with over 10 years of experience...'
                    ),
                    new OA\Property(
                        property: 'website',
                        type: 'string',
                        format: 'uri',
                        description: 'Company website (max 255 characters)',
                        nullable: true,
                        maxLength: 255,
                        example: 'https://techcorp.com'
                    ),
                    new OA\Property(
                        property: 'industry_id',
                        type: 'string',
                        format: 'uuid',
                        description: 'Industry UUID (reference to company_industries)',
                        example: '550e8400-e29b-41d4-a716-446655440000'
                    ),
                    new OA\Property(
                        property: 'request_message',
                        type: 'string',
                        description: 'Request message (10-500 characters)',
                        minLength: 10,
                        maxLength: 500,
                        example: 'We need a professional helpdesk system for our customer support'
                    ),
                    new OA\Property(
                        property: 'estimated_users',
                        type: 'integer',
                        description: 'Estimated number of users (1-10000)',
                        nullable: true,
                        minimum: 1,
                        maximum: 10000,
                        example: 500
                    ),
                    new OA\Property(
                        property: 'contact_address',
                        type: 'string',
                        description: 'Contact address (max 255 characters)',
                        nullable: true,
                        maxLength: 255,
                        example: 'Main Avenue 123'
                    ),
                    new OA\Property(
                        property: 'contact_city',
                        type: 'string',
                        description: 'City (max 100 characters)',
                        nullable: true,
                        maxLength: 100,
                        example: 'Santiago'
                    ),
                    new OA\Property(
                        property: 'contact_country',
                        type: 'string',
                        description: 'Country (max 100 characters)',
                        nullable: true,
                        maxLength: 100,
                        example: 'Chile'
                    ),
                    new OA\Property(
                        property: 'contact_postal_code',
                        type: 'string',
                        description: 'Postal code (max 20 characters)',
                        nullable: true,
                        maxLength: 20,
                        example: '8340000'
                    ),
                    new OA\Property(
                        property: 'tax_id',
                        type: 'string',
                        description: 'Tax ID - RUT/NIT (max 50 characters)',
                        nullable: true,
                        maxLength: 50,
                        example: '12.345.678-9'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Company request created successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                        new OA\Property(property: 'requestCode', type: 'string', example: 'REQ-20251101-001'),
                        new OA\Property(property: 'companyName', type: 'string', example: 'TechCorp Solutions'),
                        new OA\Property(property: 'legalName', type: 'string', nullable: true, example: 'TechCorp Solutions S.A.'),
                        new OA\Property(property: 'adminEmail', type: 'string', format: 'email', example: 'admin@techcorp.com'),
                        new OA\Property(property: 'businessDescription', type: 'string', nullable: true, example: 'We are a leading technology solutions company...'),
                        new OA\Property(property: 'requestMessage', type: 'string', nullable: true, example: 'We need a professional helpdesk system for our customer support'),
                        new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true, example: 'https://techcorp.com'),
                        new OA\Property(property: 'industryId', type: 'string', format: 'uuid', nullable: true, example: '550e8400-e29b-41d4-a716-446655440000'),
                        new OA\Property(
                            property: 'industry',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'code', type: 'string', example: 'TECH'),
                                new OA\Property(property: 'name', type: 'string', example: 'Technology'),
                            ]
                        ),
                        new OA\Property(property: 'estimatedUsers', type: 'integer', nullable: true, example: 500),
                        new OA\Property(property: 'contactAddress', type: 'string', nullable: true, example: 'Main Avenue 123'),
                        new OA\Property(property: 'contactCity', type: 'string', nullable: true, example: 'Santiago'),
                        new OA\Property(property: 'contactCountry', type: 'string', nullable: true, example: 'Chile'),
                        new OA\Property(property: 'contactPostalCode', type: 'string', nullable: true, example: '8340000'),
                        new OA\Property(property: 'taxId', type: 'string', nullable: true, example: '12.345.678-9'),
                        new OA\Property(property: 'status', type: 'string', enum: ['PENDING'], example: 'PENDING'),
                        new OA\Property(property: 'reviewedAt', type: 'string', format: 'date-time', nullable: true, example: null),
                        new OA\Property(property: 'rejectionReason', type: 'string', nullable: true, example: null),
                        new OA\Property(
                            property: 'reviewer',
                            type: 'object',
                            nullable: true,
                            example: null,
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'user_code', type: 'string'),
                                new OA\Property(property: 'email', type: 'string', format: 'email'),
                                new OA\Property(property: 'name', type: 'string'),
                            ]
                        ),
                        new OA\Property(
                            property: 'createdCompany',
                            type: 'object',
                            nullable: true,
                            example: null,
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'companyCode', type: 'string'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'logoUrl', type: 'string', nullable: true),
                            ]
                        ),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-11-01T12:00:00Z'),
                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', example: '2025-11-01T12:00:00Z'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - invalid data or duplicate detection',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'success',
                            type: 'boolean',
                            example: false
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Error de validación'
                        ),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            description: 'Errores de validación por campo. Puede incluir: campo_requerido, formato_inválido, o errores_de_duplicados',
                            properties: [
                                new OA\Property(
                                    property: 'tax_id',
                                    type: 'array',
                                    description: 'Error: NIT/Tax ID duplicado (bloquea creación)',
                                    items: new OA\Items(
                                        type: 'string',
                                        example: 'Ya existe una solicitud pendiente con el NIT/Tax ID "12.345.678-9" para la empresa "TechCorp S.A.".'
                                    )
                                ),
                                new OA\Property(
                                    property: 'admin_email',
                                    type: 'array',
                                    description: 'Error: Email admin duplicado con nombre similar >70% (bloquea creación)',
                                    items: new OA\Items(
                                        type: 'string',
                                        example: 'El email "admin@techcorp.com" ya es el email de soporte de la empresa "TechCorp Solutions" (código: TECH-001). Si deseas administrar esta empresa, contacta con el administrador de plataforma.'
                                    )
                                ),
                                new OA\Property(
                                    property: 'website',
                                    type: 'array',
                                    description: 'Error: Dominio de website duplicado con nombre similar >50% (bloquea creación)',
                                    items: new OA\Items(
                                        type: 'string',
                                        example: 'Ya existe una empresa con el mismo sitio web (dominio: techcorp.com): "TechCorp Solutions". Si deseas formar parte de esta empresa, contacta con el administrador de plataforma.'
                                    )
                                ),
                                new OA\Property(
                                    property: 'company_name',
                                    type: 'array',
                                    description: 'Advertencia: Nombre muy similar >85% (no bloquea, solo advierte)',
                                    items: new OA\Items(
                                        type: 'string',
                                        example: 'ADVERTENCIA: Ya existe una empresa con nombre muy similar: "TechCorp Solutions" (código: TECH-001). Si es la misma empresa, contacta con el administrador de plataforma.'
                                    )
                                ),
                                new OA\Property(
                                    property: 'company_name',
                                    type: 'array',
                                    description: 'Advertencia: Email admin con nombre débilmente similar 30-70% (no bloquea, solo advierte)',
                                    items: new OA\Items(
                                        type: 'string',
                                        example: 'ADVERTENCIA: El email "admin@techcorp.com" ya está asociado a la empresa "TechCorp" que tiene un nombre similar. Verifica que no sean la misma empresa.'
                                    )
                                ),
                            ],
                            example: new \stdClass()
                        ),
                    ]
                )
            ),
            new OA\Response(response: 429, description: 'Rate limit exceeded - maximum 3 requests per hour'),
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
