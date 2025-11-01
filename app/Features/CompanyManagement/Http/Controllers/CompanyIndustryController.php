<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use App\Features\CompanyManagement\Http\Resources\CompanyIndustryResource;
use App\Features\CompanyManagement\Services\CompanyIndustryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * CompanyIndustryController
 *
 * Controlador REST para gestión del catálogo de industrias de empresas.
 * Arquitectura: Feature-First PURE
 * Feature: CompanyManagement
 * Version: V8.0
 *
 * Métodos implementados:
 * - index() - GET /api/company-industries (PÚBLICO - para selectores de formularios)
 *
 * Propósito:
 * - Proveer endpoints REST para listar industrias disponibles
 * - Soportar selectores de formularios en frontend (CompanyRequest form)
 * - Opcionalmente incluir conteos de empresas activas por industria
 *
 * @package App\Features\CompanyManagement\Http\Controllers
 */
class CompanyIndustryController extends Controller
{
    /**
     * Constructor con inyección de dependencias.
     *
     * @param CompanyIndustryService $companyIndustryService Servicio de industrias
     */
    public function __construct(
        protected CompanyIndustryService $companyIndustryService
    ) {}

    /**
     * Listar todas las industrias disponibles.
     *
     * Endpoint público (sin autenticación) para obtener el catálogo completo
     * de industrias. Útil para poblar selectores en formularios de solicitud
     * de empresas.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/company-industries',
        operationId: 'list_company_industries',
        summary: 'Listar todas las industrias disponibles',
        description: 'Obtiene el catálogo completo de industrias para selección en formularios. Endpoint público sin autenticación requerida. Opcionalmente incluye conteos de empresas activas por industria.',
        tags: ['Company Industries'],
        parameters: [
            new OA\Parameter(
                name: 'with_counts',
                in: 'query',
                description: 'Incluir conteo de empresas activas por industria (default: false)',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: false)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de industrias obtenida exitosamente',
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
                                    new OA\Property(property: 'code', type: 'string', example: 'technology', description: 'Código único de la industria'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Tecnología', description: 'Nombre de la industria'),
                                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Empresas de tecnología y software', description: 'Descripción de la industria'),
                                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-10-31T12:00:00Z'),
                                    new OA\Property(property: 'activeCompaniesCount', type: 'integer', example: 45, description: 'Conteo de empresas activas (solo si with_counts=true)'),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        // Determinar si incluir conteos de empresas activas
        $withCounts = $request->boolean('with_counts', false);

        // Obtener industrias desde el Service
        $industries = $withCounts
            ? $this->companyIndustryService->getActiveIndustries()
            : $this->companyIndustryService->index();

        // Transformar con CompanyIndustryResource y retornar
        return response()->json([
            'data' => CompanyIndustryResource::collection($industries),
        ]);
    }
}
