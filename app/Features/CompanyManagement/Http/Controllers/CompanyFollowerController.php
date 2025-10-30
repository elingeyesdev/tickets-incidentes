<?php

namespace App\Features\CompanyManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use App\Features\CompanyManagement\Http\Resources\CompanyFollowInfoResource;
use App\Features\CompanyManagement\Http\Resources\CompanyFollowResource;
use App\Features\CompanyManagement\Http\Requests\FollowCompanyRequest;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\CompanyManagement\Services\CompanyFollowService;

/**
 * CompanyFollowerController
 *
 * Controlador REST para gestionar el seguimiento de empresas por parte de los usuarios.
 *
 * Fase 2: Métodos de LECTURA
 * - followed(): Listar empresas seguidas por el usuario autenticado
 * - isFollowing(): Verificar si el usuario autenticado sigue una empresa específica
 *
 * Fase 3: Métodos de ESCRITURA
 * - follow(): Seguir una empresa
 * - unfollow(): Dejar de seguir una empresa
 *
 * @package App\Features\CompanyManagement\Http\Controllers
 */
class CompanyFollowerController extends Controller
{
    /**
     * Obtiene las empresas seguidas por el usuario autenticado.
     *
     * Lista todas las empresas que el usuario autenticado está siguiendo,
     * ordenadas por fecha de seguimiento (más recientes primero).
     *
     * Eager loading: Incluye la relación 'company' para evitar consultas N+1.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": "uuid",
     *       "company": {
     *         "id": "uuid",
     *         "company_code": "COMP001",
     *         "name": "Acme Corp",
     *         "logo_url": "https://...",
     *         "status": "active"
     *       },
     *       "followed_at": "2025-10-29T12:00:00Z",
     *       "my_tickets_count": 5,
     *       "last_ticket_created_at": "2025-10-28T10:00:00Z",
     *       "has_unread_announcements": true
     *     }
     *   ]
     * }
     */
    public function followed()
    {
        $follows = CompanyFollower::where('user_id', JWTHelper::getUserId())
            ->with(['company'])  // Eager load company para evitar N+1
            ->orderBy('followed_at', 'desc')
            ->get();

        return CompanyFollowInfoResource::collection($follows);
    }

    /**
     * Verifica si el usuario autenticado sigue la empresa.
     *
     * Comprueba si existe un registro de seguimiento activo entre
     * el usuario autenticado y la empresa especificada.
     *
     * @param Company $company Empresa a verificar (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "is_following": true
     * }
     */
    public function isFollowing(Company $company)
    {
        $isFollowing = CompanyFollower::where('user_id', JWTHelper::getUserId())
            ->where('company_id', $company->id)
            ->exists();

        return response()->json([
            'is_following' => $isFollowing,
        ]);
    }

    /**
     * Permite al usuario autenticado seguir una empresa.
     *
     * @param Company $company
     * @param FollowCompanyRequest $request
     * @param CompanyFollowService $followService
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Ahora sigues a Tech Solutions Inc.",
     *   "company": {
     *     "id": "uuid",
     *     "company_code": "CMP-2025-00001",
     *     "name": "Tech Solutions Inc.",
     *     "logo_url": "https://..."
     *   },
     *   "followed_at": "2025-10-29T12:00:00Z"
     * }
     */
    public function follow(Company $company, FollowCompanyRequest $request, CompanyFollowService $followService)
    {
        // Llamar al Service para seguir la empresa
        $follower = $followService->follow(JWTHelper::getAuthenticatedUser(), $company);

        // Preparar datos para el Resource
        $data = [
            'success' => true,
            'message' => "Ahora sigues a {$company->name}.",
            'company' => $company,
            'followed_at' => $follower->followed_at,
        ];

        return (new CompanyFollowResource($data))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Permite al usuario autenticado dejar de seguir una empresa.
     *
     * @param Company $company
     * @param CompanyFollowService $followService
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Dejaste de seguir a Tech Solutions Inc."
     * }
     */
    public function unfollow(Company $company, CompanyFollowService $followService)
    {
        // Llamar al Service para dejar de seguir
        $success = $followService->unfollow(JWTHelper::getAuthenticatedUser(), $company);

        return response()->json([
            'success' => $success,
            'message' => "Dejaste de seguir a {$company->name}.",
        ]);
    }
}
