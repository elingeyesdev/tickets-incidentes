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
     */
    #[OA\Get(
        path: '/api/roles',
        operationId: 'list_roles',
        summary: 'Get all available roles',
        description: 'Returns list of all available roles in the system. Only PLATFORM_ADMIN or COMPANY_ADMIN can view roles',
        security: [['bearerAuth' => []]],
        tags: ['Roles'],
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
                                    new OA\Property(property: 'code', type: 'string', enum: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'], description: 'Role code', example: 'COMPANY_ADMIN'),
                                    new OA\Property(property: 'name', type: 'string', description: 'Role display name', example: 'Company Administrator'),
                                    new OA\Property(property: 'description', type: 'string', nullable: true, description: 'Role description', example: 'Manages company settings and users'),
                                    new OA\Property(property: 'requiresCompany', type: 'boolean', description: 'Whether this role requires a company context', example: true),
                                    new OA\Property(property: 'defaultDashboard', type: 'string', description: 'Default dashboard route for this role', example: '/empresa/dashboard'),
                                    new OA\Property(property: 'isSystemRole', type: 'boolean', description: 'Whether this is a system-protected role', example: true),
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
     * Assign a role to a user. Throttled: 100 requests/hour per authenticated user.
     */
    #[OA\Post(
        path: '/api/users/{userId}/roles',
        operationId: 'assign_role_to_user',
        summary: 'Assign a role to a user',
        description: 'Assign a role to a user with optional company context. Only PLATFORM_ADMIN or COMPANY_ADMIN can assign roles. Returns 200 if role was reactivated, 201 if newly assigned. Throttled: 100 requests/hour per authenticated user. Reactivates revoked roles if applicable.',
        security: [['bearerAuth' => []]],
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, description: 'User UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Role assignment data',
            content: new OA\JsonContent(
                type: 'object',
                required: ['roleCode'],
                properties: [
                    new OA\Property(property: 'roleCode', type: 'string', enum: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'], description: 'Role code to assign', example: 'AGENT'),
                    new OA\Property(property: 'companyId', type: 'string', format: 'uuid', nullable: true, description: 'Company UUID. REQUIRED if roleCode is AGENT or COMPANY_ADMIN. MUST be null if roleCode is USER or PLATFORM_ADMIN.', example: '8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f'),
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
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Rol asignado exitosamente'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '7b5a4c3d-9e8f-1a2b-3c4d-5e6f7a8b9c0d'),
                                new OA\Property(property: 'roleCode', type: 'string', example: 'AGENT'),
                                new OA\Property(property: 'roleName', type: 'string', example: 'Agent'),
                                new OA\Property(property: 'company', type: 'object', nullable: true, properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Acme Corporation'),
                                    new OA\Property(property: 'logoUrl', type: 'string', nullable: true, example: 'https://example.com/logos/acme.png'),
                                ]),
                                new OA\Property(property: 'isActive', type: 'boolean', example: true),
                                new OA\Property(property: 'assignedAt', type: 'string', format: 'date-time', example: '2025-11-01T14:30:00Z'),
                                new OA\Property(property: 'assignedBy', type: 'object', nullable: true, properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '6a4b3c2d-8e7f-9a0b-1c2d-3e4f5a6b7c8d'),
                                    new OA\Property(property: 'userCode', type: 'string', example: 'USR-20250001'),
                                    new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                                ]),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 200,
                description: 'Role reactivated successfully (was previously inactive)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Rol reactivado exitosamente'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'roleCode', type: 'string'),
                                new OA\Property(property: 'roleName', type: 'string'),
                                new OA\Property(property: 'company', type: 'object', nullable: true),
                                new OA\Property(property: 'isActive', type: 'boolean'),
                                new OA\Property(property: 'assignedAt', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'assignedBy', type: 'object', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 404, description: 'User not found'),
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
     * Remove a role from a user (soft delete)
     */
    #[OA\Delete(
        path: '/api/users/roles/{roleId}',
        operationId: 'remove_role_from_user',
        summary: 'Remove a role from a user',
        description: 'Deactivate a role assignment (soft delete). Only PLATFORM_ADMIN or COMPANY_ADMIN can remove roles. COMPANY_ADMIN can only remove roles from their own company',
        security: [['bearerAuth' => []]],
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(name: 'roleId', in: 'path', required: true, description: 'UserRole UUID (not Role UUID) - the ID of the role assignment to remove', schema: new OA\Schema(type: 'string', format: 'uuid'), example: '7b5a4c3d-9e8f-1a2b-3c4d-5e6f7a8b9c0d'),
            new OA\Parameter(name: 'reason', in: 'query', required: false, description: 'Optional reason for removal (max 500 characters)', schema: new OA\Schema(type: 'string', maxLength: 500), example: 'User changed departments'),
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
            new OA\Response(response: 404, description: 'Role assignment not found'),
            new OA\Response(response: 422, description: 'Validation error'),
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
