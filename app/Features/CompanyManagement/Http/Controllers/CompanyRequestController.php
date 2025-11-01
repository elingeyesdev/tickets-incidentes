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
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number',
                schema: new OA\Schema(type: 'integer', minimum: 1)
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - requires PLATFORM_ADMIN role'),
        ],
        security: [['bearerAuth' => []]]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = CompanyRequest::query()
            ->with(['reviewer.profile', 'createdCompany', 'industry']);

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
        summary: 'Create company request',
        description: 'Public endpoint to create new company request. Rate limit: 3 requests per hour. Automatic validation of duplicate email in pending requests.',
        tags: ['Company Requests'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Company request data',
            content: new OA\JsonContent(
                type: 'object',
                required: ['company_name', 'admin_email', 'business_description', 'industry_type'],
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
                        property: 'business_description',
                        type: 'string',
                        description: 'Business description (50-2000 characters)',
                        minLength: 50,
                        maxLength: 2000,
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
                        property: 'industry_type',
                        type: 'string',
                        description: 'Industry type (max 100 characters)',
                        maxLength: 100,
                        example: 'Technology / Software'
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
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'requestCode', type: 'string'),
                        new OA\Property(property: 'companyName', type: 'string'),
                        new OA\Property(property: 'adminEmail', type: 'string', format: 'email'),
                        new OA\Property(property: 'status', type: 'string', enum: ['PENDING']),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error - invalid data or duplicate email'),
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
