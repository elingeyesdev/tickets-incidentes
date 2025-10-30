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
use Illuminate\Routing\Controller;

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
    /**
     * Lista mínima de empresas para selectores (público).
     *
     * Endpoint: GET /api/v1/companies/minimal
     * Contexto: PÚBLICO (sin autenticación)
     * Propósito: Selectores, referencias rápidas, campos anidados
     *
     * Campos retornados: id, company_code, name, logo_url
     * Eager loading: NINGUNO (campos directos)
     * Filtros: search (opcional)
     * Ordenamiento: Por nombre (ASC)
     * Paginación: 50 por página (configurable)
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function minimal(ListCompaniesRequest $request)
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

        return CompanyMinimalResource::collection($companies);
    }

    /**
     * Lista extendida de empresas para explorar (autenticado).
     *
     * Endpoint: GET /api/v1/companies/explore
     * Contexto: AUTH (requiere autenticación)
     * Propósito: Explorar empresas públicas, cards de empresas
     *
     * Campos retornados: id, company_code, name, logo_url, description, industry,
     *                    city, country, primary_color, status, followers_count, is_followed_by_me
     * Eager loading: ->withCount('followers')
     * N+1 Prevention: Pre-load followed IDs, agregar is_followed_by_me en memoria
     * Filtros: industry, country, search, followed_by_me
     * Ordenamiento: Configurable (default: name ASC)
     * Paginación: 20 por página (configurable)
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function explore(ListCompaniesRequest $request)
    {
        $userId = JWTHelper::getUserId();

        // Pre-cargar IDs de empresas seguidas para evitar N+1
        $followedCompanyIds = CompanyFollower::where('user_id', $userId)
            ->pluck('company_id')
            ->toArray();

        $query = Company::query()
            ->select([
                'id', 'company_code', 'name', 'logo_url',
                'business_description', 'industry_type',
                'contact_city', 'contact_country', 'primary_color', 'status',
            ])
            ->where('status', 'active')
            ->withCount('followers');  // Agregar followers_count

        // Aplicar filtros
        if ($request->filled('industry')) {
            $query->where('industry_type', $request->industry);
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
        $query->orderBy($sortBy, $sortDirection);

        $companies = $query->paginate($request->input('per_page', 20));

        // Agregar campo is_followed_by_me en memoria (evita N+1)
        $companies->getCollection()->transform(function ($company) use ($followedCompanyIds) {
            $company->is_followed_by_me = in_array($company->id, $followedCompanyIds);

            return $company;
        });

        return CompanyExploreResource::collection($companies);
    }

    /**
     * Lista completa de empresas para administración (admin).
     *
     * Endpoint: GET /api/v1/companies
     * Contexto: ADMIN (requiere rol HELPDESK_ADMIN o COMPANY_ADMIN)
     * Propósito: Administración completa de empresas
     *
     * Campos retornados: TODOS los campos del modelo + campos calculados + admin info
     * Eager loading: ->with(['admin.profile']), ->withCount([...])
     * Filtros: status, search
     * Restricciones: COMPANY_ADMIN solo ve su empresa
     * Ordenamiento: Configurable (default: created_at DESC)
     * Paginación: 20 por página (configurable)
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(ListCompaniesRequest $request)
    {
        $query = Company::query()
            ->with(['admin.profile'])
            ->withCount([
                'followers',
                'userRoles as active_agents_count' => function ($q) {
                    $q->where('role_code', 'AGENT')->where('is_active', true);
                },
                'userRoles as total_users_count' => function ($q) {
                    $q->where('is_active', true)->distinct('user_id');
                },
            ]);

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

        return CompanyResource::collection($companies);
    }

    /**
     * Mostrar detalle completo de una empresa específica (autenticado + permisos).
     *
     * Endpoint: GET /api/v1/companies/{company}
     * Contexto: AUTH (requiere autenticación + permisos)
     * Propósito: Ver detalle completo de empresa
     *
     * Campos retornados: TODOS los campos del modelo + campos calculados + admin info
     * Eager loading: ->load(['admin.profile']), ->loadCount([...])
     * N+1 Prevention: Cargar relaciones después del Route Model Binding
     * Permisos: Manejado por Policy en rutas
     *
     * @return CompanyResource
     */
    public function show(Company $company)
    {
        // Cargar relaciones necesarias
        $company->load(['admin.profile'])
            ->loadCount([
                'followers',
                'userRoles as active_agents_count' => function ($q) {
                    $q->where('role_code', 'AGENT')->where('is_active', true);
                },
                'userRoles as total_users_count' => function ($q) {
                    $q->where('is_active', true)->distinct('user_id');
                },
            ]);

        // Agregar is_followed_by_me si está autenticado
        if (JWTHelper::isAuthenticated()) {
            $company->is_followed_by_me = CompanyFollower::where('user_id', JWTHelper::getUserId())
                ->where('company_id', $company->id)
                ->exists();
        }

        return new CompanyResource($company);
    }

    /**
     * Crea una nueva empresa directamente (solo PLATFORM_ADMIN).
     *
     * Endpoint: POST /api/v1/companies
     * Contexto: PLATFORM_ADMIN (requiere rol PLATFORM_ADMIN)
     * Propósito: Crear empresas sin proceso de solicitud
     *
     * @param CreateCompanyRequest $request
     * @param CompanyService $companyService
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 201 {
     *   "data": {
     *     "id": "uuid",
     *     "company_code": "CMP-2025-00001",
     *     "name": "New Company",
     *     "status": "active",
     *     ...
     *   }
     * }
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

    /**
     * Actualiza una empresa existente.
     *
     * Endpoint: PUT/PATCH /api/v1/companies/{company}
     * Contexto: PLATFORM_ADMIN o COMPANY_ADMIN owner
     * Propósito: Actualizar información de la empresa
     *
     * @param UpdateCompanyRequest $request
     * @param Company $company
     * @param CompanyService $companyService
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "data": {
     *     "id": "uuid",
     *     "company_code": "CMP-2025-00001",
     *     "name": "Updated Company",
     *     ...
     *   }
     * }
     */
    public function update(UpdateCompanyRequest $request, Company $company, CompanyService $companyService)
    {
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

        // Cargar relaciones para el Resource
        $updated->load(['admin.profile'])
            ->loadCount([
                'followers',
                'userRoles as active_agents_count' => fn ($q) => $q->where('role_code', 'AGENT')->where('is_active', true),
                'userRoles as total_users_count' => fn ($q) => $q->where('is_active', true)->distinct('user_id'),
            ]);

        return new CompanyResource($updated);
    }
}
