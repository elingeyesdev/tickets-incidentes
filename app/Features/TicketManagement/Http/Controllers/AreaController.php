<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Http\Requests\StoreAreaRequest;
use App\Features\TicketManagement\Http\Requests\UpdateAreaRequest;
use App\Features\TicketManagement\Http\Resources\AreaResource;
use App\Features\TicketManagement\Models\Area;
use App\Features\TicketManagement\Services\AreaService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * AreaController - Gestión de áreas/departamentos
 *
 * Endpoints para CRUD de áreas personalizadas por empresa.
 * Solo COMPANY_ADMIN puede crear/actualizar/eliminar.
 * Todos los usuarios autenticados pueden listar áreas.
 */
class AreaController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AreaService $areaService
    ) {
    }

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
        // Autorización mediante Policy
        $this->authorize('create', Area::class);

        // Obtener company_id del rol activo (debe ser COMPANY_ADMIN)
        $companyId = JWTHelper::getActiveCompanyId();

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

        // Autorización mediante Policy
        try {
            $this->authorize('update', $area);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
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

        $this->authorize('delete', $area);

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
