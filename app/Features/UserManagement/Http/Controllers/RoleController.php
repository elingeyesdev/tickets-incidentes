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

        $result = $this->roleService->assignRoleToUser(
            userId: $userId,
            roleCode: $validated['roleCode'],
            companyId: $validated['companyId'] ?? null,
            assignedByUserId: $currentUser->id
        );

        // IMPORTANT: Eager load the role relationships after assignment
        $role = $result['role']->load(['role', 'company', 'assignedByUser']);

        $statusCode = $result['wasReactivated'] ? 200 : 201;
        $message = $result['wasReactivated']
            ? 'Role reactivated successfully.'
            : 'Role assigned successfully.';

        return response()->json([
            'message' => $message,
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

            // Get the admin's company
            $adminCompanyId = $currentUser->companies()->first()?->id;

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
            'message' => 'Role removed successfully.',
        ], 200);
    }
}
