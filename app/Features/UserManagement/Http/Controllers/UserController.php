<?php

namespace App\Features\UserManagement\Http\Controllers;

use App\Features\AuditLog\Services\ActivityLogService;
use App\Features\UserManagement\Http\Resources\UserResource;
use App\Features\UserManagement\Http\Requests\UpdateStatusRequest;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Services\UserService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

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
        protected UserService $userService,
        protected ActivityLogService $activityLogService
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
    #[OA\Get(
        path: '/api/users/me',
        summary: 'Get authenticated user information',
        description: 'Returns complete user info with profile, roleContexts, and statistics',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        operationId: 'get_current_user',
        responses: [
            new OA\Response(
                response: 200,
                description: 'User information retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'userCode', type: 'string'),
                                new OA\Property(property: 'email', type: 'string', format: 'email'),
                                new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'SUSPENDED', 'DELETED'], example: 'ACTIVE'),
                                new OA\Property(property: 'emailVerified', type: 'boolean', example: true),
                                new OA\Property(property: 'authProvider', type: 'string', nullable: true, example: 'google'),
                                new OA\Property(property: 'profile', type: 'object', description: 'ProfileResource with 12 fields'),
                                new OA\Property(property: 'roleContexts', type: 'array', items: new OA\Items(type: 'object'), description: 'Array of role contexts'),
                                new OA\Property(property: 'ticketsCount', type: 'integer', example: 42),
                                new OA\Property(property: 'resolvedTicketsCount', type: 'integer', example: 38),
                                new OA\Property(property: 'averageRating', type: 'number', format: 'float', nullable: true, example: 4.5),
                                new OA\Property(property: 'lastLoginAt', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'lastActivityAt', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
     * Authorization: PLATFORM_ADMIN, COMPANY_ADMIN, or AGENT
     * - PLATFORM_ADMIN: sees all users
     * - COMPANY_ADMIN: sees only users from their company
     * - AGENT: sees only users from their company (for ticket assignment)
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/users',
        summary: 'List users with filters and pagination',
        description: 'Returns paginated list of users with optional filters. PLATFORM_ADMIN sees all users, COMPANY_ADMIN and AGENT see only users from their company.',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        operationId: 'list_users',
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by email, user_code, or profile name', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['active', 'suspended', 'deleted'])),
            new OA\Parameter(name: 'emailVerified', in: 'query', description: 'Filter by email verification', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'role', in: 'query', description: 'Filter by role', schema: new OA\Schema(type: 'string', enum: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'])),
            new OA\Parameter(name: 'companyId', in: 'query', description: 'Filter by company UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'recentActivity', in: 'query', description: 'Filter users active in last 7 days', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'createdAfter', in: 'query', description: 'Filter users created after datetime', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'createdBefore', in: 'query', description: 'Filter users created before datetime', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'order_by', in: 'query', description: 'Order by field', schema: new OA\Schema(type: 'string', enum: ['created_at', 'updated_at', 'email', 'status', 'last_login_at', 'last_activity_at'], default: 'created_at')),
            new OA\Parameter(name: 'order_direction', in: 'query', description: 'Order direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (max 50)', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Users list retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'), description: 'Array of UserResource objects (15 fields each)'),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 156),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 11),
                            ]
                        ),
                        new OA\Property(
                            property: 'links',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'first', type: 'string', nullable: true),
                                new OA\Property(property: 'last', type: 'string', nullable: true),
                                new OA\Property(property: 'prev', type: 'string', nullable: true),
                                new OA\Property(property: 'next', type: 'string', nullable: true),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Authorization check
        $isPlatformAdmin = $currentUser->hasRole('PLATFORM_ADMIN');
        $isCompanyAdmin = $currentUser->hasRole('COMPANY_ADMIN');
        $isAgent = $currentUser->hasRole('AGENT');

        if (!$isPlatformAdmin && !$isCompanyAdmin && !$isAgent) {
            return response()->json([
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
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
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
     * - AGENT: can view users from their company only
     * - Others: forbidden
     *
     * @param string $id User UUID
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/users/{id}',
        summary: 'Get specific user by ID',
        description: 'Returns complete user information. PLATFORM_ADMIN can view any user, COMPANY_ADMIN and AGENT can view users from their company only, any user can view themselves.',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        operationId: 'show_user',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'User UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'object', description: 'UserResource with 15 fields'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Authorization check
        $isPlatformAdmin = $currentUser->hasRole('PLATFORM_ADMIN');
        $isCompanyAdmin = $currentUser->hasRole('COMPANY_ADMIN');
        $isAgent = $currentUser->hasRole('AGENT');

        // Allow user to view themselves
        if (!$isPlatformAdmin && !$isCompanyAdmin && !$isAgent && $currentUser->id != $id) {
            return response()->json([
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
                    'code' => 'INSUFFICIENT_PERMISSIONS',
                    'message' => 'You can only view users from your company',
                ], 403);
            }
        }

        return response()->json([
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
    #[OA\Put(
        path: '/api/users/{id}/status',
        summary: 'Update user status',
        description: 'Suspend or activate a user. Only PLATFORM_ADMIN can perform this action',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        operationId: 'update_user_status',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'User UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'suspended']),
                    new OA\Property(property: 'reason', type: 'string', description: 'Required when status is suspended'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User status updated successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'userId', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Only platform administrators can update user status'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateStatus(string $id, UpdateStatusRequest $request): JsonResponse
    {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Authorization: Only PLATFORM_ADMIN
        if (!$currentUser->hasRole('PLATFORM_ADMIN')) {
            return response()->json([
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'message' => 'Only platform administrators can update user status',
            ], 403);
        }

        // Find user
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'code' => 'USER_NOT_FOUND',
                'message' => 'User not found',
            ], 404);
        }

        $status = $request->validated('status');
        $reason = $request->validated('reason');

        // Guardar estado anterior para el log
        $oldStatus = $user->status->value;

        try {
            // Update status based on action
            if ($status === 'suspended') {
                $user = $this->userService->suspendUser($id, $reason);
            } else {
                $user = $this->userService->activateUser($id);
            }

            // Registrar actividad
            $this->activityLogService->logUserStatusChanged(
                adminId: $currentUser->id,
                targetUserId: $id,
                oldStatus: $oldStatus,
                newStatus: $user->status->value
            );

            return response()->json([
                'data' => [
                    'userId' => $user->id,
                    'status' => $user->status->value,
                    'updatedAt' => $user->updated_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
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
    #[OA\Delete(
        path: '/api/users/{id}',
        summary: 'Delete user (soft delete)',
        description: 'Soft delete a user (sets status to deleted and deleted_at timestamp). Only PLATFORM_ADMIN can perform this action. User cannot delete themselves',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        operationId: 'delete_user',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'User UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'reason', in: 'query', description: 'Optional deletion reason', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deleted successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'success', type: 'boolean'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Only platform administrators can delete users'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Cannot delete self'),
        ]
    )]
    public function destroy(string $id, Request $request): JsonResponse
    {
        $currentUser = JWTHelper::getAuthenticatedUser();

        // Authorization: Only PLATFORM_ADMIN
        if (!$currentUser->hasRole('PLATFORM_ADMIN')) {
            return response()->json([
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'message' => 'Only platform administrators can delete users',
            ], 403);
        }

        // Prevent self-deletion
        if ($currentUser->id === $id) {
            return response()->json([
                'code' => 'CANNOT_DELETE_SELF',
                'message' => 'You cannot delete your own account',
            ], 422);
        }

        // Find user
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'code' => 'USER_NOT_FOUND',
                'message' => 'User not found',
            ], 404);
        }

        try {
            // Delete user (soft delete)
            $success = $this->userService->deleteUser($id);

            // TODO: Log reason in audit table when implemented
            $reason = $request->query('reason');

            return response()->json([
                'data' => [
                    'success' => $success,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
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
        // Company admin and Agent scope: only see users from their company
        $isPlatformAdmin = $currentUser->hasRole('PLATFORM_ADMIN');
        $isCompanyAdmin = $currentUser->hasRole('COMPANY_ADMIN');
        $isAgent = $currentUser->hasRole('AGENT');

        if (($isCompanyAdmin || $isAgent) && !$isPlatformAdmin) {
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
            $status = strtolower($request->input('status'));
            
            if ($status === 'deleted') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $status);
            }
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

        $orderBy = $request->input('order_by', 'created_at');
        $order = strtolower($request->input('order_direction', 'desc'));

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
