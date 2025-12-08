<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Http\Requests\UpdateAlertRequest;
use App\Features\ContentManagement\Http\Requests\UpdateAnnouncementRequest;
use App\Features\ContentManagement\Http\Resources\AnnouncementListResource;
use App\Features\ContentManagement\Http\Resources\AnnouncementResource;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Services\AnnouncementService;
use App\Features\ContentManagement\Services\VisibilityService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;


/**
 * Announcement Controller
 *
 * Handles HTTP requests for announcement management.
 * Delegates all business logic to AnnouncementService.
 *
 * Role-based visibility:
 * - PLATFORM_ADMIN: sees all announcements from all companies
 * - COMPANY_ADMIN: sees all announcements (any status) from their own company
 * - AGENT/USER: sees only PUBLISHED announcements from the following companies
 *
 * Feature: Content Management
 * Base URL: /api/announcements
 */
class AnnouncementController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly AnnouncementService $announcementService
    ) {
    }

    #[OA\Get(
        path: '/api/announcements',
        operationId: 'list_announcements',
        description: 'Returns paginated list of announcements with role-based visibility. PLATFORM_ADMIN sees all from all companies. COMPANY_ADMIN sees all states from their company. AGENT/USER see only PUBLISHED from followed companies.',
        summary: 'List announcements with role-based visibility',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Announcements'],
        parameters: array(
            new OA\Parameter(
                name: 'status',
                description: 'Filter by announcement status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: array('draft', 'scheduled', 'published', 'archived'))
            ),
            new OA\Parameter(
                name: 'type',
                description: 'Filter by announcement type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: array('MAINTENANCE', 'INCIDENT', 'NEWS', 'ALERT'))
            ),
            new OA\Parameter(
                name: 'search',
                description: 'Search in title and content (max 100 chars)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sort',
                description: 'Sort field and direction (default: -published_at)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: '-published_at', enum: array('-published_at', '-created_at', 'title'))
            ),
            new OA\Parameter(
                name: 'published_after',
                description: 'Filter announcements published after this date',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'published_before',
                description: 'Filter announcements published before this date',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'company_id',
                description: 'Filter by company (UUID, only for PLATFORM_ADMIN)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number (default: 1)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page (default: 20, max: 100)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1)
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcements list with pagination',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'data', description: 'Array of announcements', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', description: 'Pagination metadata (current_page, per_page, total, last_page)', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (insufficient permissions)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * List announcements with role-based visibility (CAPA 3E).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  VisibilityService  $visibilityService
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(
        Request $request,
        VisibilityService $visibilityService
    ): JsonResponse {
        // 1. Validate input
        $validated = $request->validate([
            'status' => 'nullable|in:draft,scheduled,published,archived',
            'type' => 'nullable|in:MAINTENANCE,INCIDENT,NEWS,ALERT',
            'search' => 'nullable|string|max:100',
            'sort' => 'nullable|string|in:-published_at,-created_at,title',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'published_after' => 'nullable|date',
            'published_before' => 'nullable|date',
            'company_id' => 'nullable|uuid',
        ]);

        // 2. Get authenticated user
        $user = auth()->user();

        // 3. Build base query
        $query = Announcement::query();

        // 4. Apply role-based visibility filters - MIGRADO: Usar rol ACTIVO
        $activeRole = JWTHelper::getActiveRoleCode();
        $activeCompanyId = JWTHelper::getActiveCompanyId();

        if ($activeRole === 'PLATFORM_ADMIN') {
            // PLATFORM_ADMIN sees EVERYTHING
            if (isset($validated['company_id'])) {
                $query->where('company_id', $validated['company_id']);
            }
        } elseif ($activeRole === 'COMPANY_ADMIN') {
            // COMPANY_ADMIN sees only their ACTIVE company
            if (!$activeCompanyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Invalid company context',
                ], 403);
            }
            $query->where('company_id', $activeCompanyId);
        } else {
            // AGENT and USER: only PUBLISHED from followed companies
            $followedCompanyIds = \DB::table('business.user_company_followers')
                ->where('user_id', $user->id)
                ->pluck('company_id')
                ->toArray();

            $query->where('status', PublicationStatus::PUBLISHED->value)
                ->whereIn('company_id', $followedCompanyIds);
        }

        // 5. Apply optional filters
        if (isset($validated['status'])) {
            $query->where('status', strtoupper($validated['status']));
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('content', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($validated['published_after'])) {
            $query->where('published_at', '>=', $validated['published_after']);
        }

        if (isset($validated['published_before'])) {
            $query->where('published_at', '<=', $validated['published_before']);
        }

        // 6. Apply sorting (default: -published_at)
        $sort = $validated['sort'] ?? '-published_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $query->orderBy($column, $direction);

        // 7. Load relationships for full resource
        $query->with(['company', 'author.profile']);

        // 8. Paginate results (max 100 per page)
        $perPage = min($validated['per_page'] ?? 20, 100);
        $announcements = $query->paginate($perPage);

        // 9. Return response with full resource (same as show endpoint)
        return response()->json([
            'success' => true,
            'data' => AnnouncementResource::collection($announcements->items()),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
                'last_page' => $announcements->lastPage(),
            ],
        ], 200);
    }

    #[OA\Get(
        path: '/api/announcements/{announcement}',
        operationId: 'get_announcement',
        description: 'Returns a single announcement by ID with role-based visibility. PLATFORM_ADMIN can view any announcement. COMPANY_ADMIN can view any announcement from their company. AGENT/USER can only view PUBLISHED announcements from followed companies.',
        summary: 'Get announcement by ID',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Announcements'],
        parameters: [
            new OA\Parameter(
                name: 'announcement',
                description: 'The announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'data', description: 'Announcement object with full details', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: false),
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Announcement not found'),
                        new OA\Property(property: 'code', description: 'Error code', type: 'string', example: 'NOT_FOUND'),
                        new OA\Property(property: 'category', description: 'Error category', type: 'string', example: 'resource'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Unauthorized or invalid JWT'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (insufficient permissions - user role or company mismatch)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Get a single announcement by ID with role-based visibility (CAPA 3E).
     *
     * Visibility rules:
     * - PLATFORM_ADMIN: Can view any announcement from any company
     * - COMPANY_ADMIN: Can view any announcement from their own company
     * - AGENT/USER: Can only view PUBLISHED announcements from followed companies
     *
     * Route: GET /api/announcements/{id}
     *
     * @param string $announcement The announcement ID (route parameter)
     * @param VisibilityService $visibilityService Service for role-based checks
     * @return JsonResponse Success response with announcement data
     */
    public function show(string $announcement, VisibilityService $visibilityService): JsonResponse
    {
        // Resolve the model manually to allow middleware to handle ModelNotFoundException
        try {
            $announcement = Announcement::findOrFail($announcement);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        }

        $user = auth()->user();

        // MIGRADO: Usar rol ACTIVO del usuario
        $activeRole = JWTHelper::getActiveRoleCode();
        $activeCompanyId = JWTHelper::getActiveCompanyId();

        // 1. PLATFORM_ADMIN can see any announcement
        if ($activeRole === 'PLATFORM_ADMIN') {
            $announcement->load(['company', 'author.profile']);
            return response()->json([
                'success' => true,
                'data' => new AnnouncementResource($announcement),
            ], 200);
        }

        // 2. COMPANY_ADMIN can see any announcement from their ACTIVE company
        if ($activeRole === 'COMPANY_ADMIN') {
            if (!$activeCompanyId) {
                return response()->json([
                    'message' => 'Unauthorized or invalid JWT',
                ], 401);
            }

            if ($announcement->company_id !== $activeCompanyId) {
                return response()->json([
                    'message' => 'Insufficient permissions',
                ], 403);
            }

            $announcement->load(['company', 'author.profile']);
            return response()->json([
                'success' => true,
                'data' => new AnnouncementResource($announcement),
            ], 200);
        }

        // 3. AGENT/USER can only see PUBLISHED announcements from followed companies
        if ($announcement->status !== PublicationStatus::PUBLISHED) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        // Check if user follows the announcement's company
        $isFollowing = \DB::table('business.user_company_followers')
            ->where('user_id', $user->id)
            ->where('company_id', $announcement->company_id)
            ->exists();

        if (!$isFollowing) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        $announcement->load(['company', 'author.profile']);
        return response()->json([
            'success' => true,
            'data' => new AnnouncementResource($announcement),
        ], 200);
    }

    #[OA\Put(
        path: '/api/announcements/{announcement}',
        operationId: 'update_announcement',
        description: 'Update an existing announcement with partial data. Only DRAFT and SCHEDULED announcements can be edited. Published ALERT announcements (via special exception) can only update ended_at field. Supports type-specific metadata fields: MAINTENANCE (urgency, scheduled_start, scheduled_end, is_emergency, affected_services), INCIDENT (resolution_content, affected_services), NEWS (news_type, target_audience, summary, call_to_action), ALERT (urgency, alert_type, message, action_required, action_description, started_at, ended_at, affected_services).',
        summary: 'Update announcement',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'Announcement update data with type-specific metadata fields (all fields optional)',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', description: 'Announcement title (3-255 chars)', type: 'string'),
                    new OA\Property(property: 'content', description: 'Announcement content (10-5000 chars)', type: 'string'),
                    new OA\Property(property: 'urgency', description: 'Urgency level (LOW/MEDIUM/HIGH/CRITICAL)', type: 'string'),
                    new OA\Property(property: 'scheduled_start', description: 'Scheduled start datetime (MAINTENANCE only)', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'scheduled_end', description: 'Scheduled end datetime (MAINTENANCE only, after start)', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'is_emergency', description: 'Is emergency flag (MAINTENANCE only)', type: 'boolean'),
                    new OA\Property(property: 'affected_services', description: 'List of affected service IDs (array of strings)', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'resolution_content', description: 'Resolution details (INCIDENT only, max 1000 chars)', type: 'string'),
                    new OA\Property(property: 'metadata', description: 'Complex metadata object for type-specific fields (NEWS/ALERT)', type: 'object'),
                ],
                type: 'object'
            )
        ),
        tags: ['Announcements'],
        parameters: [
            new OA\Parameter(
                name: 'announcement',
                description: 'The announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message', type: 'string', example: 'Announcement updated successfully'),
                        new OA\Property(property: 'data', description: 'Updated announcement object with all fields', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request (validation error or state constraint violation)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Cannot edit published announcement'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Unauthorized or invalid JWT'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (user not COMPANY_ADMIN or company mismatch, or cannot edit published ALERT announcement)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Update an existing announcement.
     *
     * Handles partial updates - only updates fields that are present in the request.
     * Metadata fields are intelligently merged based on an announcement type:
     * - MAINTENANCE: urgency, scheduled_start, scheduled_end, is_emergency, affected_services
     * - INCIDENT: resolution_content, affected_services
     * - NEWS: news_type, target_audience, summary, call_to_action
     * - ALERT: urgency (HIGH/CRITICAL only), alert_type, message, action_required, action_description, started_at, ended_at, affected_services
     *
     * Route: PUT /api/v1/announcements/{id}
     *
     * @param UpdateAnnouncementRequest $request The validated request with partial data
     * @param Announcement $announcement The announcement to update (route model binding)
     * @return JsonResponse Success response with updated announcement
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's active company (from JWT)
        try {
            $userCompanyId = JWTHelper::getActiveCompanyId();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unauthorized or invalid JWT',
            ], 401);
        }

        if ($announcement->company_id !== $userCompanyId) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        try {
            $validated = $request->validated();

            // Build update data array
            $data = [];

            // Direct field updates (if present)
            if (isset($validated['title'])) {
                $data['title'] = $validated['title'];
            }

            if (isset($validated['content'])) {
                $data['content'] = $validated['content'];
            }

            // Handle 'body' field for NEWS announcements (stored as 'content')
            if (isset($validated['body'])) {
                $data['content'] = $validated['body'];
            }

            // Metadata field updates (merge with existing metadata)
            $metadataUpdates = [];

            // MAINTENANCE-specific metadata
            if (isset($validated['urgency'])) {
                $metadataUpdates['urgency'] = $validated['urgency'];
            }

            if (isset($validated['scheduled_start'])) {
                $metadataUpdates['scheduled_start'] = $validated['scheduled_start'];
            }

            if (isset($validated['scheduled_end'])) {
                $metadataUpdates['scheduled_end'] = $validated['scheduled_end'];
            }

            if (isset($validated['is_emergency'])) {
                $metadataUpdates['is_emergency'] = $validated['is_emergency'];
            }

            if (isset($validated['affected_services'])) {
                $metadataUpdates['affected_services'] = $validated['affected_services'];
            }

            // INCIDENT-specific metadata
            if (isset($validated['resolution_content'])) {
                $metadataUpdates['resolution_content'] = $validated['resolution_content'];
            }

            // NEWS-specific metadata (from UpdateNewsRequest via validated metadata)
            if (isset($validated['metadata'])) {
                $newsMetadata = $validated['metadata'];
                $existingMetadata = is_array($announcement->metadata) ? $announcement->metadata : [];

                // Intelligent merge for NEWS metadata
                if (isset($newsMetadata['news_type'])) {
                    $metadataUpdates['news_type'] = $newsMetadata['news_type'];
                }

                if (isset($newsMetadata['target_audience'])) {
                    $metadataUpdates['target_audience'] = $newsMetadata['target_audience'];
                }

                if (isset($newsMetadata['summary'])) {
                    $metadataUpdates['summary'] = $newsMetadata['summary'];
                }

                // Handle call_to_action: can be added, updated, or removed (null)
                if (array_key_exists('call_to_action', $newsMetadata)) {
                    $metadataUpdates['call_to_action'] = $newsMetadata['call_to_action'];
                }
            }

            // ALERT-specific metadata (from UpdateAlertRequest via validated metadata)
            if (isset($validated['metadata'])) {
                $alertMetadata = $validated['metadata'];

                // Intelligent merge for ALERT metadata
                if (isset($alertMetadata['urgency'])) {
                    $metadataUpdates['urgency'] = $alertMetadata['urgency'];
                }

                if (isset($alertMetadata['alert_type'])) {
                    $metadataUpdates['alert_type'] = $alertMetadata['alert_type'];
                }

                if (isset($alertMetadata['message'])) {
                    $metadataUpdates['message'] = $alertMetadata['message'];
                }

                if (isset($alertMetadata['action_required'])) {
                    $metadataUpdates['action_required'] = $alertMetadata['action_required'];
                }

                if (isset($alertMetadata['action_description'])) {
                    $metadataUpdates['action_description'] = $alertMetadata['action_description'];
                }

                if (isset($alertMetadata['started_at'])) {
                    $metadataUpdates['started_at'] = $alertMetadata['started_at'];
                }

                if (isset($alertMetadata['ended_at'])) {
                    $metadataUpdates['ended_at'] = $alertMetadata['ended_at'];
                }

                if (isset($alertMetadata['affected_services'])) {
                    $metadataUpdates['affected_services'] = $alertMetadata['affected_services'];
                }
            }

            // Special handling for PUBLISHED ALERT announcements
            // PUBLISHED alerts can ONLY update ended_at to mark them as complete
            if ($announcement->type === AnnouncementType::ALERT
                && $announcement->status === PublicationStatus::PUBLISHED) {

                $isAlertEndingOnly = count($metadataUpdates) === 1 && isset($metadataUpdates['ended_at']);

                if (!empty($metadataUpdates) && !$isAlertEndingOnly) {
                    return response()->json([
                        'message' => 'Cannot edit published announcement',
                    ], 403);
                }
            }

            // Merge metadata updates with existing metadata
            if (!empty($metadataUpdates)) {
                // Ensure metadata is an array before merging
                $existingMetadata = is_array($announcement->metadata) ? $announcement->metadata : [];
                $data['metadata'] = array_merge(
                    $existingMetadata,
                    $metadataUpdates
                );
            }

            // Delegate update to service
            $updatedAnnouncement = $this->announcementService->update($announcement, $data);

            // Load relationships for resource
            $updatedAnnouncement->load(['company', 'author.profile']);

            return response()->json([
                'success' => true,
                'message' => 'Announcement updated successfully',
                'data' => new AnnouncementResource($updatedAnnouncement),
            ], 200);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            // Return 403 for permission-based errors, 400 for state errors
            if (str_contains($message, 'Cannot edit')) {
                return response()->json([
                    'message' => $message,
                ], 403);
            }
            return response()->json([
                'message' => $message,
            ], 400);
        }
    }

    #[OA\Delete(
        path: '/api/announcements/{announcement}',
        operationId: 'delete_announcement',
        description: 'Delete an announcement permanently. Only DRAFT or ARCHIVED announcements can be deleted. Published and SCHEDULED announcements cannot be deleted.',
        summary: 'Delete announcement',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Announcements'],
        parameters: [
            new OA\Parameter(
                name: 'announcement',
                description: 'The announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message', type: 'string', example: 'Announcement deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request (cannot delete published or scheduled announcement)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Cannot delete published announcement'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Unauthorized or invalid JWT'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (insufficient permissions - user not COMPANY_ADMIN or company mismatch)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Delete an announcement permanently.
     *
     * Only DRAFT or ARCHIVED announcements can be deleted.
     * The service validates deletability before performing the operation.
     *
     * Route: DELETE /api/v1/announcements/{id}
     *
     * @param Announcement $announcement The announcement to delete (route model binding)
     * @return JsonResponse Success response
     */
    public function destroy(Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's active company (from JWT)
        try {
            $userCompanyId = JWTHelper::getActiveCompanyId();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unauthorized or invalid JWT',
            ], 401);
        }

        if ($announcement->company_id !== $userCompanyId) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        // Delegate deletion to service (validates deletability internally)
        try {
            $this->announcementService->delete($announcement);
        } catch (\RuntimeException $e) {
            // Convert service exceptions to HTTP responses
            $message = $e->getMessage();

            if (str_contains($message, 'published')) {
                return response()->json([
                    'message' => 'Cannot delete published announcement',
                ], 400);
            }
            if (str_contains($message, 'scheduled')) {
                return response()->json([
                    'message' => 'Cannot delete scheduled announcement',
                ], 400);
            }

            return response()->json([
                'message' => $message,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully',
        ], 200);
    }
}
