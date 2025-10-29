<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Http\Controllers;

use App\Features\UserManagement\Http\Requests\AssignRoleRequest;
use App\Features\UserManagement\Http\Resources\RoleResource;
use App\Features\UserManagement\Http\Resources\UserRoleResource;
use App\Features\UserManagement\Models\UserRole;
use App\Features\UserManagement\Services\RoleService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService
    ) {}

    /**
     * Get all available roles
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/roles',
        summary: 'Get all available roles',
        description: 'Returns list of all available roles in the system. Only PLATFORM_ADMIN or COMPANY_ADMIN can view roles',
        security: [['bearerAuth' => []]],
        tags: ['User Management - Roles'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Roles retrieved successfully',
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
                                    new OA\Property(property: 'roleCode', type: 'string', enum: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN']),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'description', type: 'string'),
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
        ]
    )]
    public function index(): JsonResponse
    {
        // Authorization: PLATFORM_ADMIN or COMPANY_ADMIN
        $currentUser = JWTHelper::getAuthenticatedUser();

        if (!$currentUser->hasRole('PLATFORM_ADMIN') && !$currentUser->hasRole('COMPANY_ADMIN')) {
            return response()->json([
                'message' => 'Unauthorized. Only PLATFORM_ADMIN or COMPANY_ADMIN can view roles.',
            ], 403);
        }

        $roles = $this->roleService->getAllRoles();

        return response()->json([
            'data' => RoleResource::collection($roles),
        ]);
    }

    /**
     * Assign a role to a user
     *
     * @param AssignRoleRequest $request
     * @param string $userId
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/users/{userId}/roles',
        summary: 'Assign a role to a user',
        description: 'Assign a role to a user with optional company context. Only PLATFORM_ADMIN or COMPANY_ADMIN can assign roles. Returns 200 if role was reactivated, 201 if newly assigned',
        security: [['bearerAuth' => []]],
        tags: ['User Management - Roles'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'User UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['roleCode'],
                properties: [
                    new OA\Property(property: 'roleCode', type: 'string', enum: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN']),
                    new OA\Property(property: 'companyId', type: 'string', format: 'uuid', nullable: true, description: 'Required for COMPANY_ADMIN and AGENT roles'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Role assigned successfully (newly created)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 200,
                description: 'Role reactivated successfully (was previously inactive)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 404, description: 'User or role not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function assign(AssignRoleRequest $request, string $userId): JsonResponse
    {
        // Authorization: PLATFORM_ADMIN or COMPANY_ADMIN
        $currentUser = JWTHelper::getAuthenticatedUser();

        if (!$currentUser->hasRole('PLATFORM_ADMIN') && !$currentUser->hasRole('COMPANY_ADMIN')) {
            return response()->json([
                'message' => 'Unauthorized. Only PLATFORM_ADMIN or COMPANY_ADMIN can assign roles.',
            ], 403);
        }

        $validated = $request->validated();

        try {
            $result = $this->roleService->assignRoleToUser(
                userId: $userId,
                roleCode: $validated['roleCode'],
                companyId: $validated['companyId'] ?? null,
                assignedBy: $currentUser->id
            );
        } catch (\Throwable $e) {
            \Log::error('Error assigning role: ' . $e->getMessage(), ['exception' => $e]);
            throw $e; // Re-throw to allow ApiExceptionHandler to catch it
        }

        // IMPORTANT: Eager load the role relationships after assignment
        $role = $result['role']->load(['role', 'company', 'assignedByUser']);

        $statusCode = $result['wasReactivated'] ? 200 : 201;

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => new UserRoleResource($role),
        ], $statusCode);
    }

    /**
     * Remove a role from a user
     *
     * @param Request $request
     * @param string $roleId
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/users/roles/{roleId}',
        summary: 'Remove a role from a user',
        description: 'Deactivate a role assignment (soft delete). Only PLATFORM_ADMIN or COMPANY_ADMIN can remove roles. COMPANY_ADMIN can only remove roles from their own company',
        security: [['bearerAuth' => []]],
        tags: ['User Management - Roles'],
        parameters: [
            new OA\Parameter(name: 'roleId', in: 'path', required: true, description: 'UserRole UUID (not Role UUID)', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'reason', in: 'query', description: 'Optional reason for removal', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Role removed successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 404, description: 'Role not found'),
        ]
    )]
    public function remove(Request $request, string $roleId): JsonResponse
    {
        // Authorization: PLATFORM_ADMIN or COMPANY_ADMIN
        $currentUser = JWTHelper::getAuthenticatedUser();

        if (!$currentUser->hasRole('PLATFORM_ADMIN') && !$currentUser->hasRole('COMPANY_ADMIN')) {
            return response()->json([
                'message' => 'Unauthorized. Only PLATFORM_ADMIN or COMPANY_ADMIN can remove roles.',
            ], 403);
        }

        // COMPANY_ADMIN scope check: Can only remove roles from their company
        if ($currentUser->hasRole('COMPANY_ADMIN') && !$currentUser->hasRole('PLATFORM_ADMIN')) {
            $userRole = UserRole::with('company')->findOrFail($roleId);

            // Get the admin's company via userRoles relationship (where COMPANY_ADMIN role is assigned)
            $adminCompanyId = $currentUser->userRoles()
                ->where('role_code', 'COMPANY_ADMIN')
                ->where('is_active', true)
                ->first()?->company_id;

            if (!$adminCompanyId || $userRole->company_id !== $adminCompanyId) {
                return response()->json([
                    'message' => 'Unauthorized. COMPANY_ADMIN can only remove roles from their own company.',
                ], 403);
            }
        }

        $reason = $request->input('reason');

        $this->roleService->removeRoleById(
            roleId: $roleId,
            reason: $reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Rol removido exitosamente',
        ], 200);
    }
}
