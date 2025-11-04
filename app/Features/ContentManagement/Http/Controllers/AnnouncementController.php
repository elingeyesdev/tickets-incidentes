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
use OpenApi\Annotations as OA;


/**
 * Announcement Controller
 *
 * Handles HTTP requests for announcement management.
 * Delegates all business logic to AnnouncementService.
 *
 * Role-based visibility:
 * - PLATFORM_ADMIN: sees all announcements from all companies
 * - COMPANY_ADMIN: sees all announcements (any status) from their own company
 * - AGENT/USER: sees only PUBLISHED announcements from followed companies
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
        path: '/announcements',
        operationId: 'list_announcements',
        summary: 'List announcements with role-based visibility',
        description: 'Returns a paginated list of announcements. PLATFORM_ADMIN sees all announcements from all companies. COMPANY_ADMIN sees all announcements (any status) from their own company. AGENT/USER see only PUBLISHED announcements from followed companies.',
        tags: ['Announcements'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter by announcement status (case-insensitive)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['draft', 'scheduled', 'published', 'archived'])
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                description: 'Filter by announcement type',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['MAINTENANCE', 'INCIDENT', 'NEWS', 'ALERT'])
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Search in title and content (case-insensitive)',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 100)
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                description: 'Sort field and direction (prefix with - for descending)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['-published_at', '-created_at', 'title'])
            ),
            new OA\Parameter(
                name: 'published_after',
                in: 'query',
                description: 'Filter announcements published after this date (YYYY-MM-DD)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'published_before',
                in: 'query',
                description: 'Filter announcements published before this date (YYYY-MM-DD)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'company_id',
                in: 'query',
                description: 'Filter by company (only PLATFORM_ADMIN)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number for pagination',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Items per page (max 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of announcements with pagination',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AnnouncementList')
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'per_page', type: 'integer', example: 20),
                                new OA\Property(property: 'total', type: 'integer', example: 150),
                                new OA\Property(property: 'last_page', type: 'integer', example: 8),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ]
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

        // 4. Apply role-based visibility filters
        if ($visibilityService->isPlatformAdmin($user)) {
            // PLATFORM_ADMIN sees EVERYTHING
            if (isset($validated['company_id'])) {
                $query->where('company_id', $validated['company_id']);
            }
        } elseif ($user->hasRole('COMPANY_ADMIN')) {
            // COMPANY_ADMIN sees only their company
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Invalid company context',
                ], 403);
            }
            $query->where('company_id', $companyId);
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

        // 7. Paginate results (max 100 per page)
        $perPage = min($validated['per_page'] ?? 20, 100);
        $announcements = $query->paginate($perPage);

        // 8. Return response
        return response()->json([
            'success' => true,
            'data' => AnnouncementListResource::collection($announcements->items()),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
                'last_page' => $announcements->lastPage(),
            ],
        ], 200);
    }

    #[OA\Get(
        path: '/announcements/{announcement}',
        operationId: 'get_announcement',
        summary: 'Get a single announcement by ID',
        description: 'Returns detailed information about a specific announcement. Visibility depends on user role and company context. PLATFORM_ADMIN can view any announcement. COMPANY_ADMIN can view any announcement from their own company. AGENT/USER can only view PUBLISHED announcements from followed companies.',
        tags: ['Announcements'],
        parameters: [
            new OA\Parameter(
                name: 'announcement',
                in: 'path',
                description: 'Announcement ID (UUID)',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement details',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/Announcement'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (insufficient permissions or does not follow company)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Insufficient permissions'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement not found'),
                        new OA\Property(property: 'code', type: 'string', example: 'NOT_FOUND'),
                        new OA\Property(property: 'category', type: 'string', example: 'resource'),
                    ]
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

        // 1. PLATFORM_ADMIN can see any announcement
        if ($visibilityService->isPlatformAdmin($user)) {
            $announcement->load(['company', 'author.profile']);
            return response()->json([
                'success' => true,
                'data' => new AnnouncementResource($announcement),
            ], 200);
        }

        // 2. COMPANY_ADMIN can see any announcement from their company
        if ($user->hasRole('COMPANY_ADMIN')) {
            try {
                $userCompanyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
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
        path: '/announcements/{announcement}',
        operationId: 'update_announcement',
        summary: 'Update an announcement',
        description: 'Updates an existing announcement with partial data. Only DRAFT or SCHEDULED announcements can be edited (except PUBLISHED ALERT which can only update ended_at). Metadata fields are intelligently merged based on announcement type. Only COMPANY_ADMIN can update announcements from their own company.',
        tags: ['Announcements'],
        parameters: [
            new OA\Parameter(
                name: 'announcement',
                in: 'path',
                description: 'Announcement ID (UUID)',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Partial announcement data (all fields optional)',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'title', type: 'string', description: 'Announcement title', minLength: 3, maxLength: 255),
                    new OA\Property(property: 'content', type: 'string', description: 'Announcement content/body'),
                    new OA\Property(property: 'urgency', type: 'string', enum: ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'], description: 'Urgency level (type-specific)'),
                    new OA\Property(property: 'scheduled_start', type: 'string', format: 'date-time', description: 'Maintenance scheduled start'),
                    new OA\Property(property: 'scheduled_end', type: 'string', format: 'date-time', description: 'Maintenance scheduled end'),
                    new OA\Property(property: 'is_emergency', type: 'boolean', description: 'Is emergency maintenance'),
                    new OA\Property(property: 'affected_services', type: 'array', items: new OA\Items(type: 'string'), description: 'List of affected services'),
                    new OA\Property(property: 'resolution_content', type: 'string', maxLength: 1000, description: 'Incident resolution details'),
                    new OA\Property(property: 'metadata', type: 'object', description: 'Type-specific metadata for NEWS/ALERT'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement updated successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement updated successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/Announcement'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized or invalid JWT'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (insufficient permissions or cannot edit published announcement)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cannot edit published announcement'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement not found'),
                    ]
                )
            ),
        ]
    )]
    /**
     * Update an existing announcement.
     *
     * Handles partial updates - only updates fields that are present in the request.
     * Metadata fields are intelligently merged based on announcement type:
     * - MAINTENANCE: urgency, scheduled_start, scheduled_end, is_emergency, affected_services
     * - INCIDENT: resolution_content, affected_services
     * - NEWS: news_type, target_audience, summary, call_to_action
     * - ALERT: urgency (HIGH/CRITICAL only), alert_type, message, action_required, action_description, started_at, ended_at, affected_services
     *
     * Route: PUT /api/v1/announcements/{id}
     *
     * @param UpdateAnnouncementRequest|UpdateAlertRequest $request The validated request with partial data
     * @param Announcement $announcement The announcement to update (route model binding)
     * @return JsonResponse Success response with updated announcement
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's company (from JWT)
        try {
            $userCompanyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
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
        path: '/announcements/{announcement}',
        operationId: 'delete_announcement',
        summary: 'Delete an announcement',
        description: 'Permanently deletes an announcement. Only DRAFT or ARCHIVED announcements can be deleted. PUBLISHED and SCHEDULED announcements must be archived/unscheduled first. Only COMPANY_ADMIN can delete announcements from their own company.',
        tags: ['Announcements'],
        parameters: [
            new OA\Parameter(
                name: 'announcement',
                in: 'path',
                description: 'Announcement ID (UUID)',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement deleted successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement deleted successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request (cannot delete published or scheduled announcement)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cannot delete published announcement'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized or invalid JWT'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (insufficient permissions)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Insufficient permissions'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement not found'),
                    ]
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
        // Validate that announcement belongs to user's company (from JWT)
        try {
            $userCompanyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
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
