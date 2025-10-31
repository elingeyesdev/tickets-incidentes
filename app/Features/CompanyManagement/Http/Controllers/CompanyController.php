<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use App\Features\CompanyManagement\Http\Requests\CreateCompanyRequest;
use App\Features\CompanyManagement\Http\Requests\ListCompaniesRequest;
use App\Features\CompanyManagement\Http\Requests\UpdateCompanyRequest;
use App\Features\CompanyManagement\Http\Resources\CompanyExploreResource;
use App\Features\CompanyManagement\Http\Resources\CompanyMinimalResource;
use App\Features\CompanyManagement\Http\Resources\CompanyResource;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Services\RoleService;
use App\Shared\Helpers\JWTHelper;
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
    #[OA\Get(
        path: '/api/companies/minimal',
        operationId: 'list_companies_minimal',
        summary: 'Lista mínima de empresas para selectores',
        description: 'Retorna un listado paginado de empresas activas con información mínima (id, código, nombre, logo). Endpoint público sin autenticación.',
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Filtrar empresas por nombre (búsqueda case-insensitive)',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Acme')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Número de items por página',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 50)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de empresas minimalistas con paginación',
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
                                    new OA\Property(property: 'company_code', type: 'string', example: 'CMP-2025-00001'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Acme Corporation'),
                                    new OA\Property(property: 'logo_url', type: 'string', nullable: true, example: 'https://example.com/logo.png'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 150),
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 3),
                                new OA\Property(property: 'per_page', type: 'integer', example: 50),
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
        ]
    )]
    /**
     * Lista mínima de empresas para selectores.
     */
    public function minimal(ListCompaniesRequest $request): JsonResponse
    {
        $query = Company::query()
            ->select(['id', 'company_code', 'name', 'logo_url'])
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
        summary: 'Explorar empresas con filtros',
        description: 'Retorna listado paginado de empresas con información extendida para exploración. Incluye indicadores de seguimiento del usuario autenticado. Requiere autenticación JWT.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Buscar empresas por nombre',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Tech')
            ),
            new OA\Parameter(
                name: 'country',
                in: 'query',
                description: 'Filtrar por país',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Chile')
            ),
            new OA\Parameter(
                name: 'followed_by_me',
                in: 'query',
                description: 'Mostrar solo empresas seguidas por el usuario',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: false)
            ),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                description: 'Campo para ordenar resultados',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'name')
            ),
            new OA\Parameter(
                name: 'sort_direction',
                in: 'query',
                description: 'Dirección del ordenamiento',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'asc')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Número de items por página',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 20)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de empresas con información extendida',
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
                description: 'No autenticado (token JWT inválido o ausente)',
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

        $query = Company::query();

        // Status filter (default: 'active' for explore context, but can be overridden)
        // Use case-insensitive ILIKE for status comparison
        if ($request->filled('status')) {
            $query->where('status', 'ILIKE', $request->status);
        } else {
            $query->where('status', 'active');  // Default: only active companies in explore
        }

        // Aplicar filtros
        // Note: Campo industry no existe en la BD, omitir por ahora
        // if ($request->filled('industry')) {
        //     $query->where('industry_type', $request->industry);
        // }

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
        $query->orderBy($sortBy, $sortDirection);

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
        summary: 'Listar todas las empresas (admin)',
        description: 'Retorna listado completo de empresas con toda la información y campos calculados. Requiere rol HELPDESK_ADMIN o COMPANY_ADMIN. Los COMPANY_ADMIN solo ven su propia empresa.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Buscar empresas por nombre',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Tech')
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filtrar por estado de la empresa',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['active', 'suspended', 'inactive'], example: 'active')
            ),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                description: 'Campo para ordenar resultados',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'created_at')
            ),
            new OA\Parameter(
                name: 'sort_direction',
                in: 'query',
                description: 'Dirección del ordenamiento',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'desc')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Número de items por página',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 20)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado completo de empresas con información administrativa',
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
                description: 'No autenticado (token JWT inválido o ausente)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Sin permisos (requiere rol de administrador)',
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
            ->with(['admin.profile']);

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
        summary: 'Ver detalle completo de una empresa',
        description: 'Retorna toda la información de una empresa específica incluyendo campos calculados, admin info y estado de seguimiento del usuario. Requiere autenticación y permisos de acceso.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'company',
                in: 'path',
                description: 'ID o UUID de la empresa',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle completo de la empresa',
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
                description: 'No autenticado (token JWT inválido o ausente)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Sin permisos para ver esta empresa',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Forbidden.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Empresa no encontrada',
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
        $company->load(['admin.profile']);

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
        summary: 'Crear nueva empresa',
        description: 'Crea una nueva empresa directamente sin proceso de solicitud. Solo disponible para usuarios con rol PLATFORM_ADMIN. Asigna automáticamente el rol COMPANY_ADMIN al usuario designado como administrador.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Datos de la nueva empresa',
            content: new OA\JsonContent(
                required: ['name', 'legal_name', 'support_email', 'admin_user_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: 'Nombre comercial de la empresa', example: 'Acme Corporation'),
                    new OA\Property(property: 'legal_name', type: 'string', description: 'Razón social legal', example: 'Acme Corp S.A.'),
                    new OA\Property(property: 'support_email', type: 'string', format: 'email', description: 'Email de soporte', example: 'support@acme.com'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, description: 'Teléfono de contacto', example: '+56912345678'),
                    new OA\Property(property: 'website', type: 'string', nullable: true, description: 'Sitio web', example: 'https://acme.com'),
                    new OA\Property(property: 'admin_user_id', type: 'string', format: 'uuid', description: 'ID del usuario que será administrador de la empresa'),
                    new OA\Property(property: 'contact_address', type: 'string', nullable: true, description: 'Dirección física'),
                    new OA\Property(property: 'contact_city', type: 'string', nullable: true, description: 'Ciudad'),
                    new OA\Property(property: 'contact_state', type: 'string', nullable: true, description: 'Estado/Región'),
                    new OA\Property(property: 'contact_country', type: 'string', nullable: true, description: 'País'),
                    new OA\Property(property: 'contact_postal_code', type: 'string', nullable: true, description: 'Código postal'),
                    new OA\Property(property: 'tax_id', type: 'string', nullable: true, description: 'RUT/NIT/Tax ID'),
                    new OA\Property(property: 'legal_representative', type: 'string', nullable: true, description: 'Representante legal'),
                    new OA\Property(property: 'business_hours', type: 'object', nullable: true, description: 'Horario de atención (JSONB)'),
                    new OA\Property(property: 'timezone', type: 'string', nullable: true, description: 'Zona horaria', example: 'America/Santiago'),
                    new OA\Property(property: 'settings', type: 'object', nullable: true, description: 'Configuraciones adicionales (JSONB)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Empresa creada exitosamente',
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
                description: 'Sin permisos (requiere rol PLATFORM_ADMIN)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Forbidden.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
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
        $company->load(['admin.profile']);

        return (new CompanyResource($company))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/api/companies/{company}',
        operationId: 'update_company',
        summary: 'Actualizar empresa',
        description: 'Actualiza la información de una empresa existente. Requiere ser PLATFORM_ADMIN o COMPANY_ADMIN propietario de la empresa. Todos los campos son opcionales.',
        security: [['bearerAuth' => []]],
        tags: ['Companies'],
        parameters: [
            new OA\Parameter(
                name: 'company',
                in: 'path',
                description: 'ID o UUID de la empresa a actualizar',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Datos a actualizar (todos los campos son opcionales)',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, description: 'Nombre comercial'),
                    new OA\Property(property: 'legal_name', type: 'string', nullable: true, description: 'Razón social'),
                    new OA\Property(property: 'support_email', type: 'string', format: 'email', nullable: true, description: 'Email de soporte'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, description: 'Teléfono'),
                    new OA\Property(property: 'website', type: 'string', nullable: true, description: 'Sitio web'),
                    new OA\Property(property: 'contact_address', type: 'string', nullable: true, description: 'Dirección'),
                    new OA\Property(property: 'contact_city', type: 'string', nullable: true, description: 'Ciudad'),
                    new OA\Property(property: 'contact_state', type: 'string', nullable: true, description: 'Estado/Región'),
                    new OA\Property(property: 'contact_country', type: 'string', nullable: true, description: 'País'),
                    new OA\Property(property: 'contact_postal_code', type: 'string', nullable: true, description: 'Código postal'),
                    new OA\Property(property: 'tax_id', type: 'string', nullable: true, description: 'RUT/NIT/Tax ID'),
                    new OA\Property(property: 'legal_representative', type: 'string', nullable: true, description: 'Representante legal'),
                    new OA\Property(property: 'business_hours', type: 'object', nullable: true, description: 'Horario de atención'),
                    new OA\Property(property: 'timezone', type: 'string', nullable: true, description: 'Zona horaria'),
                    new OA\Property(property: 'logo_url', type: 'string', nullable: true, description: 'URL del logo'),
                    new OA\Property(property: 'favicon_url', type: 'string', nullable: true, description: 'URL del favicon'),
                    new OA\Property(property: 'primary_color', type: 'string', nullable: true, description: 'Color primario (hex)', example: '#FF5733'),
                    new OA\Property(property: 'secondary_color', type: 'string', nullable: true, description: 'Color secundario (hex)', example: '#33FF57'),
                    new OA\Property(property: 'settings', type: 'object', nullable: true, description: 'Configuraciones adicionales'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Empresa actualizada exitosamente',
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
                description: 'Sin permisos para actualizar esta empresa',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Forbidden.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Empresa no encontrada',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Company not found.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
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

        // Eager load relaciones (ONLY admin.profile and userRoles, avoid followers due to withTimestamps issue)
        $updated->load(['admin.profile', 'userRoles.role']);

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
}
