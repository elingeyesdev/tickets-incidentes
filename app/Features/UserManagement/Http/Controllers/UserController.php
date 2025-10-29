<?php

namespace App\Features\UserManagement\Http\Controllers;

use App\Features\UserManagement\Http\Resources\UserResource;
use App\Features\UserManagement\Http\Requests\UpdateStatusRequest;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Services\UserService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * User Controller
 *
 * RESTful controller for user management operations.
 * Replaces GraphQL queries with REST endpoints while maintaining identical logic.
 *
 * Endpoints:
 * - GET /api/users/me - Get authenticated user info
 * - GET /api/users - List users with filters and pagination
 * - GET /api/users/{id} - Get specific user by ID
 * - PUT /api/users/{id}/status - Update user status (suspend/activate)
 * - DELETE /api/users/{id} - Delete user (soft delete)
 */
class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Get authenticated user information
     *
     * GET /api/users/me
     *
     * Returns complete user info with profile, roles, and statistics.
     * Authorization: Any authenticated user
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        // Eager load all required relationships
        $user->load([
            'profile',
            'userRoles' => fn($q) => $q->where('is_active', true)
                ->with(['role', 'company'])
        ]);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * List users with filters and pagination
     *
     * GET /api/users?search=...&status=...&role=...&page=1&per_page=20
     *
     * Filters:
     * - search: email, user_code, profile.first_name, profile.last_name
     * - status: active, suspended, deleted
     * - emailVerified: true/false
     * - role: USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN
     * - companyId: UUID of company (filter users with role in that company)
     * - recentActivity: true (active in last 7 days)
     * - createdAfter: datetime
     * - createdBefore: datetime
     *
     * Ordering:
     * - orderBy: created_at, updated_at, email, status, last_login_at, last_activity_at
     * - order: asc, desc (default: desc)
     *
     * Authorization: PLATFORM_ADMIN or COMPANY_ADMIN
     * - PLATFORM_ADMIN: sees all users
     * - COMPANY_ADMIN: sees only users from their company
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Authorization check
        $isPlatformAdmin = $currentUser->hasRole('PLATFORM_ADMIN');
        $isCompanyAdmin = $currentUser->hasRole('COMPANY_ADMIN');

        if (!$isPlatformAdmin && !$isCompanyAdmin) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'message' => 'You do not have permission to list users',
            ], 403);
        }

        // Build query with eager loading
        $query = User::with([
            'profile',
            'userRoles' => fn($q) => $q->where('is_active', true)
                ->with(['role', 'company'])
        ]);

        // Apply filters
        $query = $this->applyFilters($query, $request, $currentUser);

        // Apply ordering
        $query = $this->applyOrdering($query, $request);

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 50); // Max 50
        $page = (int) $request->input('page', 1);

        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users->items()),
            'pagination' => [
                'total' => $users->total(),
                'perPage' => $users->perPage(),
                'currentPage' => $users->currentPage(),
                'lastPage' => $users->lastPage(),
                'hasMorePages' => $users->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get specific user by ID
     *
     * GET /api/users/{id}
     *
     * Returns complete user information.
     *
     * Authorization:
     * - PLATFORM_ADMIN: can view any user
     * - COMPANY_ADMIN: can view users from their company only
     * - Others: forbidden
     *
     * @param string $id User UUID
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Authorization check
        $isPlatformAdmin = $currentUser->hasRole('PLATFORM_ADMIN');
        $isCompanyAdmin = $currentUser->hasRole('COMPANY_ADMIN');

        if (!$isPlatformAdmin && !$isCompanyAdmin) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'message' => 'You do not have permission to view user details',
            ], 403);
        }

        // Find user with eager loading
        $user = User::with([
            'profile',
            'userRoles' => fn($q) => $q->where('is_active', true)
                ->with(['role', 'company'])
        ])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'User not found',
            ], 404);
        }

        // Company admin scope: can only view users from their companies
        if ($isCompanyAdmin && !$isPlatformAdmin) {
            // Get current user's company IDs
            $currentUserCompanyIds = $currentUser->userRoles()
                ->where('is_active', true)
                ->whereNotNull('company_id')
                ->pluck('company_id')
                ->toArray();

            // Check if target user belongs to any of the same companies
            $targetUserCompanyIds = $user->userRoles()
                ->where('is_active', true)
                ->whereNotNull('company_id')
                ->pluck('company_id')
                ->toArray();

            $hasSharedCompany = !empty(array_intersect($currentUserCompanyIds, $targetUserCompanyIds));

            if (!$hasSharedCompany) {
                return response()->json([
                    'success' => false,
                    'code' => 'INSUFFICIENT_PERMISSIONS',
                    'message' => 'You can only view users from your company',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update user status (suspend or activate)
     *
     * PUT /api/users/{id}/status
     *
     * Body:
     * {
     *   "status": "active|suspended",
     *   "reason": "..." (required if suspending)
     * }
     *
     * Authorization: PLATFORM_ADMIN only
     *
     * @param string $id User UUID
     * @param UpdateStatusRequest $request
     * @return JsonResponse
     */
    public function updateStatus(string $id, UpdateStatusRequest $request): JsonResponse
    {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Authorization: Only PLATFORM_ADMIN
        if (!$currentUser->hasRole('PLATFORM_ADMIN')) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'message' => 'Only platform administrators can update user status',
            ], 403);
        }

        // Find user
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'User not found',
            ], 404);
        }

        $status = $request->validated('status');
        $reason = $request->validated('reason');

        try {
            // Update status based on action
            if ($status === 'suspended') {
                $user = $this->userService->suspendUser($id, $reason);
            } else {
                $user = $this->userService->activateUser($id);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'userId' => $user->id,
                    'status' => $user->status->value,
                    'updatedAt' => $user->updated_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'code' => 'STATUS_UPDATE_FAILED',
                'message' => 'Failed to update user status: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete user (soft delete)
     *
     * DELETE /api/users/{id}?reason=...
     *
     * Query params:
     * - reason: Optional deletion reason
     *
     * Effects:
     * - Sets status to 'deleted'
     * - Sets deleted_at timestamp
     * - Maintains records for audit
     *
     * Authorization: PLATFORM_ADMIN only
     *
     * Note: User cannot delete themselves
     *
     * @param string $id User UUID
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Authorization: Only PLATFORM_ADMIN
        if (!$currentUser->hasRole('PLATFORM_ADMIN')) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'message' => 'Only platform administrators can delete users',
            ], 403);
        }

        // Prevent self-deletion
        if ($currentUser->id === $id) {
            return response()->json([
                'success' => false,
                'code' => 'CANNOT_DELETE_SELF',
                'message' => 'You cannot delete your own account',
            ], 400);
        }

        // Find user
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'User not found',
            ], 404);
        }

        try {
            // Delete user (soft delete)
            $this->userService->deleteUser($id);

            // TODO: Log reason in audit table when implemented
            $reason = $request->query('reason');

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'code' => 'DELETE_FAILED',
                'message' => 'Failed to delete user: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Apply filters to the query
     *
     * Supports all filters from the migration blueprint:
     * - search (email, user_code, profile name)
     * - status (active, suspended, deleted)
     * - emailVerified (true/false)
     * - role (USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
     * - companyId (UUID)
     * - recentActivity (last 7 days)
     * - createdAfter, createdBefore (datetime)
     *
     * Company Admin Scope: Automatically filters to users from their company
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @param User $currentUser
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters($query, Request $request, User $currentUser)
    {
        // Company admin scope: only see users from their company
        $isPlatformAdmin = $currentUser->hasRole('PLATFORM_ADMIN');
        $isCompanyAdmin = $currentUser->hasRole('COMPANY_ADMIN');

        if ($isCompanyAdmin && !$isPlatformAdmin) {
            // Get company IDs for current user
            $companyIds = $currentUser->userRoles()
                ->where('is_active', true)
                ->whereNotNull('company_id')
                ->pluck('company_id')
                ->toArray();

            if (!empty($companyIds)) {
                $query->whereHas('userRoles', function ($q) use ($companyIds) {
                    $q->where('is_active', true)
                      ->whereIn('company_id', $companyIds);
                });
            }
        }

        // Search filter (email, user_code, profile name)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'ILIKE', "%{$search}%")
                  ->orWhere('user_code', 'ILIKE', "%{$search}%")
                  ->orWhereHas('profile', function ($q) use ($search) {
                      $q->where('first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('last_name', 'ILIKE', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Email verified filter
        if ($request->filled('emailVerified')) {
            $emailVerified = filter_var($request->input('emailVerified'), FILTER_VALIDATE_BOOLEAN);
            $query->where('email_verified', $emailVerified);
        }

        // Role filter
        if ($request->filled('role')) {
            $roleCode = strtoupper($request->input('role'));
            $query->whereHas('userRoles', function ($q) use ($roleCode) {
                $q->where('is_active', true)
                  ->where('role_code', $roleCode);
            });
        }

        // Company filter
        if ($request->filled('companyId')) {
            $companyId = $request->input('companyId');
            $query->whereHas('userRoles', function ($q) use ($companyId) {
                $q->where('is_active', true)
                  ->where('company_id', $companyId);
            });
        }

        // Recent activity filter (last 7 days)
        if ($request->filled('recentActivity')) {
            $recentActivity = filter_var($request->input('recentActivity'), FILTER_VALIDATE_BOOLEAN);
            if ($recentActivity) {
                $query->where('last_activity_at', '>=', now()->subDays(7));
            }
        }

        // Created after filter
        if ($request->filled('createdAfter')) {
            $query->where('created_at', '>=', $request->input('createdAfter'));
        }

        // Created before filter
        if ($request->filled('createdBefore')) {
            $query->where('created_at', '<=', $request->input('createdBefore'));
        }

        return $query;
    }

    /**
     * Apply ordering to the query
     *
     * Allowed fields: created_at, updated_at, email, status, last_login_at, last_activity_at
     * Default: created_at DESC
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyOrdering($query, Request $request)
    {
        $allowedOrderBy = [
            'created_at',
            'updated_at',
            'email',
            'status',
            'last_login_at',
            'last_activity_at',
        ];

        $orderBy = $request->input('orderBy', 'created_at');
        $order = strtolower($request->input('order', 'desc'));

        // Validate orderBy field
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'created_at';
        }

        // Validate order direction
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        return $query->orderBy($orderBy, $order);
    }
}
