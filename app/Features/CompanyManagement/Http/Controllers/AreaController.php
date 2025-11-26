<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Http\Controllers;

use App\Features\CompanyManagement\Http\Requests\StoreAreaRequest;
use App\Features\CompanyManagement\Http\Requests\UpdateAreaRequest;
use App\Features\CompanyManagement\Http\Resources\AreaResource;
use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Services\AreaService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * AreaController - Gestión de áreas/departamentos
 *
 * Endpoints para CRUD de áreas personalizadas por empresa.
 * Solo COMPANY_ADMIN puede crear/actualizar/eliminar.
 * Todos los usuarios autenticados pueden listar áreas.
 */
class AreaController extends Controller
{
    public function __construct(
        private readonly AreaService $areaService
    ) {
    }

    #[OA\Get(
        path: '/api/areas',
        operationId: 'list_areas',
        description: 'Returns paginated list of areas for a specific company with optional filtering by is_active status. Includes count of active tickets (status: open, pending, resolved) for each area. Requires JWT authentication and valid company_id parameter.',
        summary: 'List areas by company',
        security: [['bearerAuth' => []]],
        tags: ['Areas'],
        parameters: [
            new OA\Parameter(
                name: 'company_id',
                description: 'UUID of the company (required)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
            new OA\Parameter(
                name: 'is_active',
                description: 'Filter by active status (optional, accepts: true, false, 1, 0)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['true', 'false', '1', '0'], example: 'true')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Number of items per page (default: 15, max: 100)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number for pagination (default: 1)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Areas list retrieved successfully with pagination',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a'),
                                    new OA\Property(property: 'company_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Soporte Técnico'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Equipo de soporte técnico especializado', nullable: true),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                    new OA\Property(property: 'active_tickets_count', type: 'integer', example: 5),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'to', type: 'integer', example: 15),
                                new OA\Property(property: 'last_page', type: 'integer', example: 3),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 45),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'links',
                            properties: [
                                new OA\Property(property: 'first', type: 'string', format: 'uri', example: 'http://localhost:8000/api/areas?page=1'),
                                new OA\Property(property: 'last', type: 'string', format: 'uri', example: 'http://localhost:8000/api/areas?page=3'),
                                new OA\Property(property: 'prev', type: 'string', format: 'uri', example: null, nullable: true),
                                new OA\Property(property: 'next', type: 'string', format: 'uri', example: 'http://localhost:8000/api/areas?page=2'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The company id field is required.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'company_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The company id field is required.')
                                ),
                                new OA\Property(
                                    property: 'is_active',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The is_active field must be one of: true, false, 1, 0.')
                                ),
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
     * GET /api/areas
     *
     * Lista áreas paginadas de una empresa con filtrado opcional por is_active.
     * Incluye conteo de tickets activos por área.
     *
     * Query params:
     * - company_id: UUID (requerido)
     * - is_active: boolean (opcional)
     * - per_page: integer (opcional, default: 15, max: 100)
     * - page: integer (opcional, default: 1)
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Validar parámetros
        $validated = $request->validate([
            'company_id' => 'required|uuid',
            'is_active' => 'nullable|in:true,false,1,0',
            'per_page' => 'nullable|integer|between:1,100',
            'page' => 'nullable|integer|min:1',
        ]);

        $companyId = $request->query('company_id');
        $isActive = $request->query('is_active');
        $perPage = $request->query('per_page', 15);

        // Convertir string "true"/"false"/"1"/"0" a boolean si es necesario
        if ($isActive !== null) {
            $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $areas = $this->areaService->list($companyId, $isActive, (int) $perPage);

        return response()->json([
            'success' => true,
            'data' => AreaResource::collection($areas),
            'meta' => [
                'current_page' => $areas->currentPage(),
                'from' => $areas->firstItem(),
                'to' => $areas->lastItem(),
                'last_page' => $areas->lastPage(),
                'per_page' => $areas->perPage(),
                'total' => $areas->total(),
            ],
            'links' => [
                'first' => $areas->url(1),
                'last' => $areas->url($areas->lastPage()),
                'prev' => $areas->previousPageUrl(),
                'next' => $areas->nextPageUrl(),
            ],
        ], 200);
    }

    #[OA\Post(
        path: '/api/areas',
        operationId: 'create_area',
        description: 'Creates a new area for the authenticated COMPANY_ADMIN\'s company. The company_id is automatically extracted from the JWT token. Only COMPANY_ADMIN role can create areas. Area name must be unique within the company.',
        summary: 'Create a new area',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        description: 'Area name (2-100 characters, must be unique within company)',
                        type: 'string',
                        minLength: 2,
                        maxLength: 100,
                        example: 'Soporte Técnico'
                    ),
                    new OA\Property(
                        property: 'description',
                        description: 'Area description (optional, max 500 characters)',
                        type: 'string',
                        maxLength: 500,
                        example: 'Equipo de soporte técnico especializado en resolver problemas técnicos',
                        nullable: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Areas'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Area created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Area created successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'name', type: 'string', example: 'Soporte Técnico'),
                                new OA\Property(property: 'description', type: 'string', example: 'Equipo de soporte técnico especializado en resolver problemas técnicos', nullable: true),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (user does not have COMPANY_ADMIN role or invalid company context)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid company context'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The name field is required.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'name',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The name field is required.')
                                ),
                                new OA\Property(
                                    property: 'description',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The description field must not exceed 500 characters.')
                                ),
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
     * POST /api/areas
     *
     * Crea una nueva área para la empresa del COMPANY_ADMIN autenticado.
     * El company_id se obtiene automáticamente del JWT token.
     *
     * @param StoreAreaRequest $request
     * @return JsonResponse
     */
    public function store(StoreAreaRequest $request): JsonResponse
    {
        // NO necesita authorize aquí - ya está protegido por middleware role:COMPANY_ADMIN

        // Obtener company_id del JWT token
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid company context',
            ], 403);
        }

        // Crear área
        $area = $this->areaService->create(
            $request->validated(),
            $companyId
        );

        return response()->json([
            'success' => true,
            'message' => 'Area created successfully',
            'data' => new AreaResource($area),
        ], 201);
    }

    #[OA\Put(
        path: '/api/areas/{id}',
        operationId: 'update_area',
        description: 'Updates an existing area. Only COMPANY_ADMIN role of the same company can update areas. Supports partial updates (send only fields to update). The area must belong to the authenticated user\'s company.',
        summary: 'Update an area',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'name',
                        description: 'Area name (2-100 characters, optional)',
                        type: 'string',
                        minLength: 2,
                        maxLength: 100,
                        example: 'Soporte Técnico Mejorado'
                    ),
                    new OA\Property(
                        property: 'description',
                        description: 'Area description (optional, max 500 characters)',
                        type: 'string',
                        maxLength: 500,
                        example: 'Equipo de soporte técnico especializado en resolver problemas complejos',
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'is_active',
                        description: 'Area active status (optional, boolean)',
                        type: 'boolean',
                        example: false
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Areas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Area UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Area updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Area updated successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'name', type: 'string', example: 'Soporte Técnico Mejorado'),
                                new OA\Property(property: 'description', type: 'string', example: 'Equipo de soporte técnico especializado en resolver problemas complejos', nullable: true),
                                new OA\Property(property: 'is_active', type: 'boolean', example: false),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (user does not have COMPANY_ADMIN role or area belongs to different company)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'You do not have permission to update this area'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Area not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Area not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The name must be at least 2 characters.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'name',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The name must be at least 2 characters.')
                                ),
                                new OA\Property(
                                    property: 'description',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The description field must not exceed 500 characters.')
                                ),
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
     * PUT /api/areas/{id}
     *
     * Actualiza un área existente. Solo COMPANY_ADMIN de la misma empresa.
     *
     * @param UpdateAreaRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateAreaRequest $request, string $id): JsonResponse
    {
        // Buscar área
        $area = Area::find($id);

        if (!$area) {
            return response()->json([
                'success' => false,
                'message' => 'Area not found',
            ], 404);
        }

        // Verificar que el área pertenece a la empresa del COMPANY_ADMIN (JWT)
        $companyId = \App\Shared\Helpers\JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
        if ($area->company_id !== $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this area',
            ], 403);
        }

        // Actualizar área
        $updatedArea = $this->areaService->update($area, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Area updated successfully',
            'data' => new AreaResource($updatedArea),
        ], 200);
    }

    #[OA\Delete(
        path: '/api/areas/{id}',
        operationId: 'delete_area',
        description: 'Permanently deletes an area. Only COMPANY_ADMIN role of the same company can delete areas. Cannot delete an area that has active tickets (status: open, pending, resolved). The area must belong to the authenticated user\'s company.',
        summary: 'Delete an area',
        security: [['bearerAuth' => []]],
        tags: ['Areas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Area UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Area deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Area deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Cannot delete area with active tickets',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Cannot delete area with active tickets'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (user does not have COMPANY_ADMIN role or area belongs to different company)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'You do not have permission to delete this area'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Area not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Area not found'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * DELETE /api/areas/{id}
     *
     * Elimina un área. Solo COMPANY_ADMIN de la misma empresa.
     * No se puede eliminar si tiene tickets activos.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        // Buscar área
        $area = Area::find($id);

        if (!$area) {
            return response()->json([
                'success' => false,
                'message' => 'Area not found',
            ], 404);
        }

        // Verificar que el área pertenece a la empresa del COMPANY_ADMIN (JWT)
        $companyId = \App\Shared\Helpers\JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
        if ($area->company_id !== $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this area',
            ], 403);
        }

        try {
            $this->areaService->delete($area);

            return response()->json([
                'success' => true,
                'message' => 'Area deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete area with active tickets',
            ], 400);
        }
    }
}
