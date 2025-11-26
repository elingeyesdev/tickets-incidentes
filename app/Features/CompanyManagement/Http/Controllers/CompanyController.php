<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use App\Features\CompanyManagement\Http\Requests\CreateCompanyRequest;
use App\Features\CompanyManagement\Http\Requests\ListCompaniesRequest;
use App\Features\CompanyManagement\Http\Requests\UpdateCompanyRequest;
use App\Features\CompanyManagement\Http\Requests\UploadCompanyLogoRequest;
use App\Features\CompanyManagement\Http\Requests\UploadCompanyFaviconRequest;
use App\Features\CompanyManagement\Http\Resources\CompanyExploreResource;
use App\Features\CompanyManagement\Http\Resources\CompanyMinimalResource;
use App\Features\CompanyManagement\Http\Resources\CompanyResource;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Services\RoleService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * CompanyController
 *
 * Controlador REST para gestión de empresas (FASE 2: LECTURA + FASE 3: ESCRITURA).
 * Arquitectura: Feature-First PURE
 * Feature: CompanyManagement
 *
 * Métodos implementados:
 * - minimal() - GET /api/v1/companies/minimal (PÚBLICO)
 * - explore() - GET /api/v1/companies/explore (AUTH)
 * - index() - GET /api/v1/companies (ADMIN)
 * - show() - GET /api/v1/companies/{company} (AUTH + Permisos)
 * - store() - POST /api/v1/companies (PLATFORM_ADMIN)
 * - update() - PUT/PATCH /api/v1/companies/{company} (PLATFORM_ADMIN o COMPANY_ADMIN owner)
 */
class CompanyController extends Controller
{
    use AuthorizesRequests;

    #[OA\Get(
        path: '/api/companies/minimal',
        operationId: 'list_companies_minimal',
        description: 'Returns a paginated list of active companies with minimal information (id, code, name, logo). Public endpoint without authentication required.',
        summary: 'List minimal companies for selectors',
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                description: 'Filter companies by name (case-insensitive search)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Number of items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 50)
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number for pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Minimal company list with pagination',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                    new OA\Property(property: 'companyCode', type: 'string', example: 'CMP-2025-00001'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Acme Corporation'),
                                    new OA\Property(property: 'logoUrl', type: 'string', nullable: true, example: 'https://example.com/logo.png'),
                                    new OA\Property(property: 'industryName', type: 'string', nullable: true, example: 'Technology'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 150),
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 3),
                                new OA\Property(property: 'per_page', type: 'integer', example: 50),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'links',
                            properties: [
                                new OA\Property(property: 'first', type: 'string', nullable: true),
                                new OA\Property(property: 'last', type: 'string', nullable: true),
                                new OA\Property(property: 'prev', type: 'string', nullable: true),
                                new OA\Property(property: 'next', type: 'string', nullable: true),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Lista mínima de empresas para selectores.
     */
    public function minimal(ListCompaniesRequest $request): JsonResponse
    {
        $query = Company::query()
            ->select(['id', 'company_code', 'name', 'logo_url', 'industry_id'])
            ->with('industry:id,name')
            ->where('status', 'active')
            ->orderBy('name');

        // Aplicar búsqueda si existe
        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', "%{$request->search}%");
        }

        $companies = $query->paginate($request->input('per_page', 50));

        return response()->json([
            'data' => CompanyMinimalResource::collection($companies->items()),
            'meta' => [
                'total' => $companies->total(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
            ],
            'links' => [
                'first' => $companies->url(1),
                'last' => $companies->url($companies->lastPage()),
                'prev' => $companies->previousPageUrl(),
                'next' => $companies->nextPageUrl(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/companies/explore',
        operationId: 'explore_companies',
        summary: 'Explore companies with filters',
        description: 'Returns paginated list of companies with extended information for exploration. Includes follow indicators specific to authenticated user. Requires JWT authentication.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Search companies by name',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'industry_id',
                in: 'query',
                description: 'Filter by industry UUID',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'country',
                in: 'query',
                description: 'Filter by country',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'followed_by_me',
                in: 'query',
                description: 'Show only companies followed by the user',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                description: 'Field to sort results by',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sort_direction',
                in: 'query',
                description: 'Sort direction',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of items per page',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number for pagination',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Company list with extended information',
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
                                    new OA\Property(property: 'company_code', type: 'string'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'logo_url', type: 'string', nullable: true),
                                    new OA\Property(property: 'website', type: 'string', nullable: true),
                                    new OA\Property(property: 'contact_country', type: 'string', nullable: true),
                                    new OA\Property(property: 'industry_id', type: 'string', format: 'uuid', nullable: true),
                                    new OA\Property(
                                        property: 'industry',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'code', type: 'string'),
                                            new OA\Property(property: 'name', type: 'string'),
                                        ]
                                    ),
                                    new OA\Property(property: 'followers_count', type: 'integer'),
                                    new OA\Property(property: 'is_followed_by_me', type: 'boolean'),
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
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (invalid or missing JWT token)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    /**
     * Explorar empresas con filtros.
     */
    public function explore(ListCompaniesRequest $request): JsonResponse
    {
        $userId = JWTHelper::getUserId();

        // Pre-cargar IDs de empresas seguidas para evitar N+1
        $followedCompanyIds = CompanyFollower::where('user_id', $userId)
            ->pluck('company_id')
            ->toArray();

        $query = Company::query()
            ->with(['industry']);

        // Status filter (default: 'active' for explore context, but can be overridden)
        // Use case-insensitive ILIKE for status comparison
        if ($request->filled('status')) {
            $query->where('status', 'ILIKE', $request->status);
        } else {
            $query->where('status', 'active');  // Default: only active companies in explore
        }

        // Aplicar filtros
        if ($request->filled('industry_id')) {
            $query->where('industry_id', $request->industry_id);
        }

        if ($request->filled('country')) {
            $query->where('contact_country', $request->country);
        }

        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', "%{$request->search}%");
        }

        if ($request->boolean('followed_by_me')) {
            $query->whereIn('id', $followedCompanyIds);
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        if ($sortBy === 'followers_count') {
            $query->withCount('followers')
                  ->orderBy('followers_count', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $companies = $query->paginate($request->input('per_page', 20));

        // Agregar campos calculados en memoria (evita N+1 y problemas con withTimestamps)
        $companies->getCollection()->transform(function ($company) use ($followedCompanyIds) {
            if ($company && isset($company->id)) {
                // followers_count - Query directa igual que GraphQL resolver
                $company->followers_count = CompanyFollower::where('company_id', $company->id)->count();

                // is_followed_by_me
                $company->is_followed_by_me = in_array($company->id, $followedCompanyIds);
            }
            return $company;
        });

        return response()->json([
            'data' => CompanyExploreResource::collection($companies->items()),
            'meta' => [
                'total' => $companies->total(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
            ],
            'links' => [
                'first' => $companies->url(1),
                'last' => $companies->url($companies->lastPage()),
                'prev' => $companies->previousPageUrl(),
                'next' => $companies->nextPageUrl(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/companies',
        operationId: 'list_companies',
        summary: 'List all companies (admin)',
        description: 'Returns complete company list with all information and calculated fields. Requires HELPDESK_ADMIN or COMPANY_ADMIN role. COMPANY_ADMIN users can only see their own company.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Search companies by name',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter by company status',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['active', 'suspended', 'inactive'])
            ),
            new OA\Parameter(
                name: 'industry_id',
                in: 'query',
                description: 'Filter by industry UUID',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                description: 'Field to sort results by',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sort_direction',
                in: 'query',
                description: 'Sort direction',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of items per page',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number for pagination',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Complete company list with administrative information',
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
                                    new OA\Property(property: 'company_code', type: 'string'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'suspended', 'inactive']),
                                    new OA\Property(property: 'industry_id', type: 'string', format: 'uuid', nullable: true),
                                    new OA\Property(
                                        property: 'industry',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'code', type: 'string'),
                                            new OA\Property(property: 'name', type: 'string'),
                                            new OA\Property(property: 'description', type: 'string', nullable: true),
                                        ]
                                    ),
                                    new OA\Property(property: 'admin', type: 'object'),
                                    new OA\Property(property: 'followers_count', type: 'integer'),
                                    new OA\Property(property: 'active_agents_count', type: 'integer'),
                                    new OA\Property(property: 'total_users_count', type: 'integer'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
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
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (invalid or missing JWT token)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (requires administrator role)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Forbidden.'),
                    ]
                )
            ),
        ]
    )]
    /**
     * Listar todas las empresas (admin).
     */
    public function index(ListCompaniesRequest $request)
    {
        $query = Company::query()
            ->with(['admin.profile', 'industry']);

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('industry_id')) {
            $query->where('industry_id', $request->industry_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', "%{$request->search}%");
        }

        // Si es COMPANY_ADMIN, solo su empresa
        $user = JWTHelper::getAuthenticatedUser();
        if ($user->hasRole('COMPANY_ADMIN')) {
            $query->where('admin_user_id', $user->id);
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $companies = $query->paginate($request->input('per_page', 20));

        // Eager load userRoles.role ONLY (avoid loading followers due to withTimestamps issue)
        $companies->load(['userRoles.role']);

        // Calcular counts en memoria (evita problemas con loadCount y distinct)
        // Nota: NO cargamos followers(), usamos query directa como en GraphQL
        $companies->getCollection()->each(function ($company) {
            // followers_count - Query directa (sin cargar relación) igual que GraphQL resolver
            $company->followers_count = CompanyFollower::where('company_id', $company->id)->count();

            // active_agents_count (role_code = AGENT + is_active = true)
            $company->active_agents_count = $company->userRoles
                ->where('is_active', true)
                ->filter(function ($userRole) {
                    return $userRole->role && $userRole->role->role_code === 'AGENT';
                })
                ->count();

            // total_users_count (distinct user_id + is_active = true)
            $company->total_users_count = $company->userRoles
                ->where('is_active', true)
                ->unique('user_id')
                ->count();
        });

        return response()->json([
            'data' => CompanyResource::collection($companies->items()),
            'meta' => [
                'total' => $companies->total(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
            ],
            'links' => [
                'first' => $companies->url(1),
                'last' => $companies->url($companies->lastPage()),
                'prev' => $companies->previousPageUrl(),
                'next' => $companies->nextPageUrl(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/companies/{company}',
        operationId: 'show_company',
        summary: 'View complete company details',
        description: 'Returns all information about a specific company including calculated fields, admin info, and user follow status. Requires authentication and access permissions.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'company',
                in: 'path',
                description: 'Company ID or UUID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Complete company details',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'company_code', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'legal_name', type: 'string', nullable: true),
                        new OA\Property(property: 'status', type: 'string', enum: ['active', 'suspended', 'inactive']),
                        new OA\Property(property: 'support_email', type: 'string', format: 'email'),
                        new OA\Property(property: 'phone', type: 'string', nullable: true),
                        new OA\Property(property: 'website', type: 'string', nullable: true),
                        new OA\Property(property: 'logo_url', type: 'string', nullable: true),
                        new OA\Property(property: 'industry_id', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(
                            property: 'industry',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'code', type: 'string'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'description', type: 'string', nullable: true),
                            ]
                        ),
                        new OA\Property(property: 'admin', type: 'object'),
                        new OA\Property(property: 'followers_count', type: 'integer'),
                        new OA\Property(property: 'active_agents_count', type: 'integer'),
                        new OA\Property(property: 'total_users_count', type: 'integer'),
                        new OA\Property(property: 'is_followed_by_me', type: 'boolean'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (invalid or missing JWT token)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (no permission to view this company)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Forbidden.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Company not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Company not found.'),
                    ]
                )
            ),
        ]
    )]
    /**
     * Ver detalle completo de una empresa.
     */
    public function show(Company $company)
    {
        // Eager load solo relaciones simples (evita N+1)
        $company->load(['admin.profile', 'industry']);

        // Calcular counts usando queries SEPARADAS (evita problemas con loadCount y distinct en PostgreSQL)
        // Sin N+1 porque no hay loops - son queries directas paralelas
        $company->followers_count = CompanyFollower::where('company_id', $company->id)
            ->count();

        $company->active_agents_count = \DB::table('auth.user_roles')
            ->where('auth.user_roles.company_id', $company->id)
            ->where('auth.user_roles.is_active', true)
            ->join('auth.roles', 'auth.roles.role_code', '=', 'auth.user_roles.role_code')
            ->where('auth.roles.role_code', 'AGENT')
            ->distinct('auth.user_roles.user_id')
            ->count();

        $company->total_users_count = \DB::table('auth.user_roles')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->distinct('user_id')
            ->count('user_id');

        // Agregar is_followed_by_me si está autenticado
        if (JWTHelper::isAuthenticated()) {
            $company->is_followed_by_me = CompanyFollower::where('user_id', JWTHelper::getUserId())
                ->where('company_id', $company->id)
                ->exists();
        }

        return new CompanyResource($company);
    }

    #[OA\Post(
        path: '/api/companies',
        operationId: 'create_company',
        summary: 'Create new company',
        description: 'Creates a new company directly without request process. Only available for users with PLATFORM_ADMIN role. Automatically assigns COMPANY_ADMIN role to the designated administrator user.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'New company data',
            content: new OA\JsonContent(
                type: 'object',
                required: ['name', 'legal_name', 'support_email', 'admin_user_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: 'Company trade name', minLength: 2, maxLength: 255, example: 'Acme Corporation'),
                    new OA\Property(property: 'legal_name', type: 'string', description: 'Legal company name', minLength: 2, maxLength: 255, example: 'Acme Corp S.A.'),
                    new OA\Property(property: 'support_email', type: 'string', format: 'email', description: 'Support email address', maxLength: 255, example: 'support@acme.com'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, description: 'Contact phone number', maxLength: 20, example: '+56912345678'),
                    new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true, description: 'Company website', maxLength: 255, example: 'https://acme.com'),
                    new OA\Property(property: 'admin_user_id', type: 'string', format: 'uuid', description: 'User ID who will be the company administrator (required)'),
                    new OA\Property(property: 'contact_address', type: 'string', nullable: true, description: 'Physical address', maxLength: 255),
                    new OA\Property(property: 'contact_city', type: 'string', nullable: true, description: 'City', maxLength: 100),
                    new OA\Property(property: 'contact_state', type: 'string', nullable: true, description: 'State/Region', maxLength: 100),
                    new OA\Property(property: 'contact_country', type: 'string', nullable: true, description: 'Country', maxLength: 100),
                    new OA\Property(property: 'contact_postal_code', type: 'string', nullable: true, description: 'Postal code', maxLength: 20),
                    new OA\Property(property: 'tax_id', type: 'string', nullable: true, description: 'Tax ID (RUT/NIT)', maxLength: 50),
                    new OA\Property(property: 'legal_representative', type: 'string', nullable: true, description: 'Legal representative name', maxLength: 255),
                    new OA\Property(property: 'business_hours', type: 'object', nullable: true, description: 'Business hours (JSONB)', example: ['monday' => ['open' => '09:00', 'close' => '18:00']]),
                    new OA\Property(property: 'timezone', type: 'string', nullable: true, description: 'Timezone (e.g., America/Santiago)', example: 'America/Santiago'),
                    new OA\Property(property: 'settings', type: 'object', nullable: true, description: 'Additional settings (JSONB)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Company created successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'company_code', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'status', type: 'string', example: 'active'),
                        new OA\Property(property: 'admin', type: 'object'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (requires PLATFORM_ADMIN role)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Forbidden.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    /**
     * Crear nueva empresa (PLATFORM_ADMIN).
     */
    public function store(CreateCompanyRequest $request, CompanyService $companyService, RoleService $roleService)
    {
        // Obtener admin user
        $adminUser = User::findOrFail($request->admin_user_id);

        // Preparar datos para el Service
        $data = [
            'name' => $request->name,
            'legal_name' => $request->legal_name,
            'description' => $request->description,
            'industry_id' => $request->industry_id,
            'support_email' => $request->support_email,
            'phone' => $request->phone,
            'website' => $request->website,
            'contact_address' => $request->contact_address,
            'contact_city' => $request->contact_city,
            'contact_state' => $request->contact_state,
            'contact_country' => $request->contact_country,
            'contact_postal_code' => $request->contact_postal_code,
            'tax_id' => $request->tax_id,
            'legal_representative' => $request->legal_representative,
            'business_hours' => $request->business_hours,
            'timezone' => $request->timezone,
            'settings' => $request->settings,
        ];

        // Llamar al Service (NO modificarlo, solo usarlo)
        $company = $companyService->create($data, $adminUser);

        // Asignar rol COMPANY_ADMIN al usuario admin (paridad con GraphQL)
        $roleService->assignRoleToUser(
            userId: $adminUser->id,
            roleCode: 'COMPANY_ADMIN',
            companyId: $company->id,
            assignedBy: JWTHelper::getUserId()
        );

        // Cargar relaciones para el Resource
        $company->load(['admin.profile', 'industry']);

        return (new CompanyResource($company))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/api/companies/{company}',
        operationId: 'update_company',
        summary: 'Update company',
        description: 'Updates information of an existing company. Requires being PLATFORM_ADMIN or COMPANY_ADMIN owner of the company. All fields are optional.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'company',
                in: 'path',
                description: 'Company ID or UUID to update',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Data to update (all fields are optional)',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, description: 'Trade name (2-255 characters)', minLength: 2, maxLength: 255),
                    new OA\Property(property: 'legal_name', type: 'string', nullable: true, description: 'Legal name (2-255 characters)', minLength: 2, maxLength: 255),
                    new OA\Property(property: 'support_email', type: 'string', format: 'email', nullable: true, description: 'Support email (max 255 characters)', maxLength: 255),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, description: 'Phone number (max 20 characters)', maxLength: 20),
                    new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true, description: 'Website URL (max 255 characters)', maxLength: 255),
                    new OA\Property(property: 'contact_address', type: 'string', nullable: true, description: 'Address (max 255 characters)', maxLength: 255),
                    new OA\Property(property: 'contact_city', type: 'string', nullable: true, description: 'City (max 100 characters)', maxLength: 100),
                    new OA\Property(property: 'contact_state', type: 'string', nullable: true, description: 'State/Region (max 100 characters)', maxLength: 100),
                    new OA\Property(property: 'contact_country', type: 'string', nullable: true, description: 'Country (max 100 characters)', maxLength: 100),
                    new OA\Property(property: 'contact_postal_code', type: 'string', nullable: true, description: 'Postal code (max 20 characters)', maxLength: 20),
                    new OA\Property(property: 'tax_id', type: 'string', nullable: true, description: 'Tax ID (RUT/NIT, max 50 characters)', maxLength: 50),
                    new OA\Property(property: 'legal_representative', type: 'string', nullable: true, description: 'Legal representative (max 255 characters)', maxLength: 255),
                    new OA\Property(property: 'business_hours', type: 'object', nullable: true, description: 'Business hours (JSONB)'),
                    new OA\Property(property: 'timezone', type: 'string', nullable: true, description: 'Timezone (e.g., America/Santiago)'),
                    new OA\Property(property: 'logo_url', type: 'string', format: 'uri', nullable: true, description: 'Logo URL', maxLength: 255),
                    new OA\Property(property: 'favicon_url', type: 'string', format: 'uri', nullable: true, description: 'Favicon URL', maxLength: 255),
                    new OA\Property(property: 'primary_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$', nullable: true, description: 'Primary color in hexadecimal format', example: '#FF5733'),
                    new OA\Property(property: 'secondary_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$', nullable: true, description: 'Secondary color in hexadecimal format', example: '#33FF57'),
                    new OA\Property(property: 'settings', type: 'object', nullable: true, description: 'Additional settings (JSONB)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Company updated successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'company_code', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'admin', type: 'object'),
                        new OA\Property(property: 'followers_count', type: 'integer'),
                        new OA\Property(property: 'active_agents_count', type: 'integer'),
                        new OA\Property(property: 'total_users_count', type: 'integer'),
                        new OA\Property(property: 'is_followed_by_me', type: 'boolean'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (no permission to update this company)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Forbidden.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Company not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Company not found.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    /**
     * Actualizar empresa existente.
     */
    public function update(UpdateCompanyRequest $request, Company $company, CompanyService $companyService)
    {
        // NOTA: Autorización ya validada en UpdateCompanyRequest::authorize()
        // No duplicar lógica aquí. FormRequest confía en que solo usuarios autorizados llegan aquí.

        // Preparar datos actualizados (solo campos presentes en request)
        $data = array_filter([
            'name' => $request->name,
            'legal_name' => $request->legal_name,
            'support_email' => $request->support_email,
            'phone' => $request->phone,
            'website' => $request->website,
            'contact_address' => $request->contact_address,
            'contact_city' => $request->contact_city,
            'contact_state' => $request->contact_state,
            'contact_country' => $request->contact_country,
            'contact_postal_code' => $request->contact_postal_code,
            'tax_id' => $request->tax_id,
            'legal_representative' => $request->legal_representative,
            'business_hours' => $request->business_hours,
            'timezone' => $request->timezone,
            'logo_url' => $request->logo_url,
            'favicon_url' => $request->favicon_url,
            'primary_color' => $request->primary_color,
            'secondary_color' => $request->secondary_color,
            'settings' => $request->settings,
        ], fn ($value) => $value !== null);

        // Llamar al Service (NO modificarlo, solo usarlo)
        $updated = $companyService->update($company, $data);

        // Eager load relaciones (ONLY admin.profile, industry and userRoles, avoid followers due to withTimestamps issue)
        $updated->load(['admin.profile', 'industry', 'userRoles.role']);

        // Calcular counts en memoria (evita problemas con loadCount y distinct)
        // followers_count - Query directa (sin cargar relación) igual que GraphQL resolver
        $updated->followers_count = CompanyFollower::where('company_id', $updated->id)->count();

        $updated->active_agents_count = $updated->userRoles
            ->where('is_active', true)
            ->filter(function ($userRole) {
                return $userRole->role && $userRole->role->role_code === 'AGENT';
            })
            ->count();

        $updated->total_users_count = $updated->userRoles
            ->where('is_active', true)
            ->unique('user_id')
            ->count();

        // Agregar is_followed_by_me (contexto autenticado)
        $updated->is_followed_by_me = CompanyFollower::where('user_id', JWTHelper::getUserId())
            ->where('company_id', $updated->id)
            ->exists();

        return new CompanyResource($updated);
    }

    /**
     * Upload company logo image. Throttled: 10 requests/hour.
     */
    #[OA\Post(
        path: '/api/companies/{company}/logo',
        operationId: 'upload_company_logo',
        summary: 'Upload company logo image',
        description: 'Upload and store logo image for a company. Throttled: 10 requests/hour. Supported formats: JPEG, PNG, GIF, WebP, SVG. Max size: 5 MB.',
        tags: ['Companies'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'Company UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Logo image file (multipart/form-data)',
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['logo'],
                    properties: [
                        new OA\Property(
                            property: 'logo',
                            type: 'string',
                            format: 'binary',
                            description: 'Logo image file'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logo uploaded successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Logo uploaded successfully'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'logoUrl',
                                    type: 'string',
                                    format: 'uri',
                                    example: 'http://localhost:8000/storage/company-logos/550e8400-e29b-41d4-a716-446655440000/1731774123_acme-logo.png'
                                ),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Only company admin can upload logo'),
            new OA\Response(response: 404, description: 'Company not found'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['logo' => ['Logo must not exceed 5 MB']]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function uploadLogo(UploadCompanyLogoRequest $request, Company $company, CompanyService $companyService): JsonResponse
    {
        // Authorization: Only PLATFORM_ADMIN or COMPANY_ADMIN of this company can upload
        $this->authorize('update', $company);

        try {
            $logoUrl = $companyService->uploadLogo(
                $company,
                $request->file('logo')
            );

            return response()->json([
                'message' => 'Logo uploaded successfully',
                'data' => [
                    'logoUrl' => $logoUrl,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error uploading logo',
                'errors' => ['logo' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Upload company favicon image. Throttled: 10 requests/hour.
     */
    #[OA\Post(
        path: '/api/companies/{company}/favicon',
        operationId: 'upload_company_favicon',
        summary: 'Upload company favicon image',
        description: 'Upload and store favicon image for a company. Throttled: 10 requests/hour. Supported formats: ICO, PNG, JPEG. Max size: 1 MB.',
        tags: ['Companies'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'Company UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Favicon image file (multipart/form-data)',
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['favicon'],
                    properties: [
                        new OA\Property(
                            property: 'favicon',
                            type: 'string',
                            format: 'binary',
                            description: 'Favicon image file'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Favicon uploaded successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Favicon uploaded successfully'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'faviconUrl',
                                    type: 'string',
                                    format: 'uri',
                                    example: 'http://localhost:8000/storage/favicons/550e8400-e29b-41d4-a716-446655440000/1731774123_favicon.ico'
                                ),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Only company admin can upload favicon'),
            new OA\Response(response: 404, description: 'Company not found'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['favicon' => ['Favicon must not exceed 1 MB']]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function uploadFavicon(UploadCompanyFaviconRequest $request, Company $company, CompanyService $companyService): JsonResponse
    {
        // Authorization: Only PLATFORM_ADMIN or COMPANY_ADMIN of this company can upload
        $this->authorize('update', $company);

        try {
            $faviconUrl = $companyService->uploadFavicon(
                $company,
                $request->file('favicon')
            );

            return response()->json([
                'message' => 'Favicon uploaded successfully',
                'data' => [
                    'faviconUrl' => $faviconUrl,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error uploading favicon',
                'errors' => ['favicon' => [$e->getMessage()]]
            ], 422);
        }
    }

    #[OA\Get(
        path: '/api/companies/me/settings/areas-enabled',
        operationId: 'get_areas_enabled',
        summary: 'Get areas feature status',
        description: 'Returns whether the authenticated COMPANY_ADMIN\'s company has areas feature enabled. The company ID is automatically extracted from the JWT token.',
        security: [['bearerAuth' => []]],
        tags: ['Company Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Areas feature status retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'areas_enabled', type: 'boolean', example: false),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (invalid or missing JWT token)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Invalid company context (user is not COMPANY_ADMIN)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid company context'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Company not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Company not found'),
                    ]
                )
            ),
        ]
    )]
    /**
     * GET /api/companies/me/settings/areas-enabled
     *
     * Obtiene si la empresa del COMPANY_ADMIN tiene áreas habilitadas.
     * Company ID se obtiene del JWT.
     *
     * @return JsonResponse
     */
    public function getAreasEnabled(): JsonResponse
    {
        // Obtener company_id del JWT
        $companyId = \App\Shared\Helpers\JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid company context',
            ], 403);
        }

        $company = Company::find($companyId);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'areas_enabled' => $company->hasAreasEnabled(),
            ],
        ], 200);
    }

    #[OA\Patch(
        path: '/api/companies/me/settings/areas-enabled',
        operationId: 'toggle_areas_enabled',
        summary: 'Enable or disable areas feature',
        description: 'Enables or disables the areas feature for the authenticated COMPANY_ADMIN\'s company. Updates the company.settings JSONB field. Requires manageAreas permission from CompanyPolicy.',
        security: [['bearerAuth' => []]],
        tags: ['Company Settings'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Areas feature toggle request',
            content: new OA\JsonContent(
                type: 'object',
                required: ['enabled'],
                properties: [
                    new OA\Property(
                        property: 'enabled',
                        type: 'boolean',
                        description: 'Enable or disable the areas feature',
                        example: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Areas feature status updated successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Areas enabled successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'areas_enabled', type: 'boolean', example: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (invalid or missing JWT token)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (invalid company context or missing manageAreas permission)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid company context'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Company not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Company not found'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error (enabled must be boolean)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    /**
     * PATCH /api/companies/me/settings/areas-enabled
     *
     * Activa o desactiva las áreas para la empresa del COMPANY_ADMIN.
     * Company ID se obtiene del JWT.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleAreasEnabled(Request $request): JsonResponse
    {
        // Validar input
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        // Obtener company_id del JWT
        $companyId = \App\Shared\Helpers\JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid company context',
            ], 403);
        }

        $company = Company::find($companyId);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        // Autorizar con CompanyPolicy
        $this->authorize('manageAreas', $company);

        // Actualizar settings
        $settings = $company->settings ?? [];
        $settings['areas_enabled'] = $validated['enabled'];
        $company->settings = $settings;
        $company->save();

        return response()->json([
            'success' => true,
            'message' => $validated['enabled']
                ? 'Areas enabled successfully'
                : 'Areas disabled successfully',
            'data' => [
                'areas_enabled' => $company->hasAreasEnabled(),
            ],
        ], 200);
    }

    #[OA\Get(
        path: '/api/companies/{companyId}/settings/areas-enabled',
        operationId: 'get_company_areas_enabled_public',
        description: 'Returns whether a company has the areas feature enabled. Public endpoint (no authentication required). Used by frontend to determine if area selection should be displayed when creating tickets.',
        summary: 'Get company areas feature status (public)',
        tags: ['Company Settings'],
        parameters: [
            new OA\Parameter(
                name: 'companyId',
                description: 'Company UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Areas feature status retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'areas_enabled',
                                    type: 'boolean',
                                    example: false,
                                    description: 'Whether the company has areas feature enabled'
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Company not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Company not found'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * GET /api/companies/{companyId}/settings/areas-enabled
     *
     * Obtiene si una empresa tiene áreas habilitadas (endpoint público).
     * No requiere autenticación - usado por frontend para mostrar/ocultar select de áreas.
     *
     * @param string $companyId
     * @return JsonResponse
     */
    public function getCompanyAreasEnabledPublic(string $companyId): JsonResponse
    {
        $company = Company::find($companyId);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'areas_enabled' => $company->hasAreasEnabled(),
            ],
        ], 200);
    }
}
