<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Http\Requests\StoreCategoryRequest;
use App\Features\TicketManagement\Http\Requests\UpdateCategoryRequest;
use App\Features\TicketManagement\Http\Resources\CategoryResource;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Services\CategoryService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * CategoryController - Gestión de categorías de tickets
 *
 * Endpoints para CRUD de categorías personalizadas por empresa.
 * Solo COMPANY_ADMIN puede crear/actualizar/eliminar.
 * Todos los usuarios autenticados pueden listar categorías.
 */
class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CategoryService $categoryService
    ) {
    }

    #[OA\Get(
        path: '/api/tickets/categories',
        operationId: 'list_ticket_categories',
        description: 'Returns a paginated list of ticket categories for a specific company. All authenticated users can list categories. Categories are returned sorted by creation date (newest first). Optionally filter by active status. Each category includes the count of active tickets (open, pending, resolved) assigned to it. Pagination is included with links to navigate between pages.',
        summary: 'List ticket categories for a company (paginated)',
        security: [['bearerAuth' => []]],
        tags: ['Ticket Categories'],
        parameters: [
            new OA\Parameter(
                name: 'company_id',
                description: 'UUID of the company to list categories for (required)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
            new OA\Parameter(
                name: 'is_active',
                description: 'Filter by active status. Accepts "true", "false", "1", or "0". Omit to return all categories.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['true', 'false', '1', '0'], example: 'true')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Number of categories per page (default: 15, min: 1, max: 100)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15, example: 15)
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number (default: 1)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1, example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categories list retrieved successfully',
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
                                    new OA\Property(property: 'description', type: 'string', example: 'Problemas técnicos con el sistema', nullable: true),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00.000000Z'),
                                    new OA\Property(property: 'active_tickets_count', type: 'integer', example: 5),
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
                                new OA\Property(property: 'last_page', type: 'integer', example: 2),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 30),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'links',
                            properties: [
                                new OA\Property(property: 'first', type: 'string', example: 'http://localhost:8000/api/tickets/categories?page=1'),
                                new OA\Property(property: 'last', type: 'string', example: 'http://localhost:8000/api/tickets/categories?page=2'),
                                new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'next', type: 'string', example: 'http://localhost:8000/api/tickets/categories?page=2'),
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
                description: 'Validation error (missing or invalid company_id, per_page, or page)',
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
                                    items: new OA\Items(type: 'string', example: 'The selected is active is invalid.')
                                ),
                                new OA\Property(
                                    property: 'per_page',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The per page must be between 1 and 100.')
                                ),
                                new OA\Property(
                                    property: 'page',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The page must be at least 1.')
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
     * GET /api/tickets/categories
     *
     * Lista categorías paginadas de una empresa con filtrado opcional por is_active.
     * Incluye conteo de tickets activos por categoría.
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

        $categories = $this->categoryService->list($companyId, $isActive, (int) $perPage);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
            'links' => [
                'first' => $categories->url(1),
                'last' => $categories->url($categories->lastPage()),
                'prev' => $categories->previousPageUrl(),
                'next' => $categories->nextPageUrl(),
            ],
        ], 200);
    }

    #[OA\Post(
        path: '/api/tickets/categories',
        operationId: 'create_ticket_category',
        description: 'Creates a new ticket category for the company associated with the authenticated COMPANY_ADMIN. The company_id is automatically obtained from the JWT token - do not include it in the request body. Category names must be unique within the company. New categories are created with is_active=true by default.',
        summary: 'Create a new ticket category',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        description: 'Category name (3-100 characters, must be unique within the company)',
                        type: 'string',
                        maxLength: 100,
                        minLength: 3,
                        example: 'Soporte Técnico'
                    ),
                    new OA\Property(
                        property: 'description',
                        description: 'Optional description of the category (max 500 characters)',
                        type: 'string',
                        maxLength: 500,
                        example: 'Problemas técnicos con el sistema',
                        nullable: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Ticket Categories'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Category created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Category created successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'name', type: 'string', example: 'Soporte Técnico'),
                                new OA\Property(property: 'description', type: 'string', example: 'Problemas técnicos con el sistema', nullable: true),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00.000000Z'),
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
                        new OA\Property(property: 'message', type: 'string', example: 'The category name is required'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'name',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'A category with this name already exists in your company')
                                ),
                                new OA\Property(
                                    property: 'description',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The description must not exceed 500 characters')
                                ),
                                new OA\Property(
                                    property: 'company_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The company id field is prohibited.')
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
     * POST /api/tickets/categories
     *
     * Crea una nueva categoría para la empresa del COMPANY_ADMIN autenticado.
     * El company_id se obtiene automáticamente del JWT token.
     *
     * @param StoreCategoryRequest $request
     * @return JsonResponse
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        // Autorización mediante Policy
        $this->authorize('create', Category::class);

        // Obtener company_id del JWT token
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid company context',
            ], 403);
        }

        // Crear categoría
        $category = $this->categoryService->create(
            $request->validated(),
            $companyId
        );

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    #[OA\Put(
        path: '/api/tickets/categories/{id}',
        operationId: 'update_ticket_category',
        description: 'Updates an existing ticket category. Only COMPANY_ADMIN of the same company can update categories. Partial updates are supported - send only the fields you want to update. Category names must remain unique within the company. The company_id cannot be changed (immutable).',
        summary: 'Update a ticket category',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'name',
                        description: 'New category name (3-100 characters, must be unique within the company)',
                        type: 'string',
                        maxLength: 100,
                        minLength: 3,
                        example: 'Soporte Técnico Avanzado'
                    ),
                    new OA\Property(
                        property: 'description',
                        description: 'New description of the category (max 500 characters)',
                        type: 'string',
                        maxLength: 500,
                        example: 'Problemas técnicos complejos que requieren escalación',
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'is_active',
                        description: 'Whether the category is active and can be used for new tickets',
                        type: 'boolean',
                        example: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Ticket Categories'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'UUID of the category to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Category updated successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'name', type: 'string', example: 'Soporte Técnico Avanzado'),
                                new OA\Property(property: 'description', type: 'string', example: 'Problemas técnicos complejos que requieren escalación', nullable: true),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00.000000Z'),
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
                description: 'Forbidden (user does not have COMPANY_ADMIN role or category belongs to different company)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Category not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Category not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The category name must be at least 3 characters'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'name',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'A category with this name already exists in your company')
                                ),
                                new OA\Property(
                                    property: 'description',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The description must not exceed 500 characters')
                                ),
                                new OA\Property(
                                    property: 'is_active',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The is_active field must be true or false')
                                ),
                                new OA\Property(
                                    property: 'company_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The company id field is prohibited.')
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
     * PUT /api/tickets/categories/{id}
     *
     * Actualiza una categoría existente.
     * Solo el COMPANY_ADMIN de la misma empresa puede actualizar.
     *
     * @param UpdateCategoryRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        // Buscar categoría
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        // Autorización mediante Policy
        try {
            $this->authorize('update', $category);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Actualizar categoría
        $category = $this->categoryService->update($category, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category),
        ], 200);
    }

    #[OA\Delete(
        path: '/api/tickets/categories/{id}',
        operationId: 'delete_ticket_category',
        description: 'Permanently deletes a ticket category. Only COMPANY_ADMIN of the same company can delete categories. Categories with active tickets (open, pending, or resolved status) cannot be deleted. This is a hard delete operation (not soft delete).',
        summary: 'Delete a ticket category',
        security: [['bearerAuth' => []]],
        tags: ['Ticket Categories'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'UUID of the category to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Category deleted successfully'),
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
                description: 'Forbidden (user does not have COMPANY_ADMIN role or category belongs to different company)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Category not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Category not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Cannot delete category (has active tickets)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Cannot delete category with 5 active tickets'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * DELETE /api/tickets/categories/{id}
     *
     * Elimina una categoría si no tiene tickets activos.
     * Solo el COMPANY_ADMIN de la misma empresa puede eliminar.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        // Buscar categoría
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }


        $this->authorize('delete', $category);

        try {

            $this->categoryService->delete($category);

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
