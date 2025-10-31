<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Features\CompanyManagement\Http\Requests\StoreCompanyRequestRequest;
use App\Features\CompanyManagement\Http\Resources\CompanyRequestResource;

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
     * Lista de solicitudes de empresas (solo PLATFORM_ADMIN)
     *
     * GET /api/v1/company-requests
     *
     * Query params:
     * - status (optional): Filtrar por status (pending|approved|rejected)
     * - per_page (optional): Items por página (default: 15)
     *
     * Eager loading:
     * - reviewer.profile (usuario que revisó la solicitud)
     * - createdCompany (empresa creada desde la solicitud)
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
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
     * Crear solicitud de empresa (PÚBLICO, con rate limit)
     *
     * POST /api/v1/company-requests
     *
     * Body (JSON):
     * - company_name (required): Nombre de la empresa
     * - legal_name (optional): Nombre legal
     * - admin_email (required): Email del administrador
     * - business_description (required): Descripción del negocio
     * - website (optional): Sitio web
     * - industry_type (required): Tipo de industria
     * - estimated_users (required): Usuarios estimados
     * - contact_address (optional): Dirección de contacto
     * - contact_city (optional): Ciudad
     * - contact_country (required): País
     * - contact_postal_code (optional): Código postal
     * - tax_id (optional): Identificador fiscal
     *
     * Validación: StoreCompanyRequestRequest
     * Service call: CompanyRequestService::submit()
     *
     * @param StoreCompanyRequestRequest $request
     * @param CompanyRequestService $requestService
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCompanyRequestRequest $request, CompanyRequestService $requestService)
    {
        $companyRequest = $requestService->submit($request->validated());

        return (new CompanyRequestResource($companyRequest))
            ->response()
            ->setStatusCode(201);
    }
}
