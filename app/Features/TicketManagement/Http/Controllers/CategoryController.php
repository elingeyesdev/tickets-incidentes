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

    /**
     * GET /api/tickets/categories
     *
     * Lista categorías de una empresa con filtrado opcional por is_active.
     * Incluye conteo de tickets activos por categoría.
     *
     * Query params:
     * - company_id: UUID (requerido)
     * - is_active: boolean (opcional)
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Validar que company_id esté presente
        $request->validate([
            'company_id' => 'required|uuid',
            'is_active' => 'nullable|in:true,false,1,0',
        ]);

        $companyId = $request->query('company_id');
        $isActive = $request->query('is_active');

        // Convertir string "true"/"false"/"1"/"0" a boolean si es necesario
        if ($isActive !== null) {
            $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $categories = $this->categoryService->list($companyId, $isActive);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ], 200);
    }

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

        // Autorización mediante Policy
        $this->authorize('delete', $category);

        try {
            // Intentar eliminar (lanza excepción si hay tickets activos)
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
