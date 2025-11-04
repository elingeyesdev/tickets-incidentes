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

/**
 * Announcement Controller
 *
 * Handles HTTP requests for announcement management.
 * Delegates all business logic to AnnouncementService.
 *
 * CAPA 3A: Update and Delete operations.
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

    /**
     * Get announcement schemas for all types (CAPA 3E).
     *
     * Only COMPANY_ADMIN and PLATFORM_ADMIN can access schemas.
     * Returns metadata schema structure for each announcement type.
     *
     * Route: GET /api/announcements/schemas
     *
     * @return JsonResponse Schema data for all announcement types
     */
    public function getSchemas(): JsonResponse
    {
        // Only allow COMPANY_ADMIN and PLATFORM_ADMIN
        try {
            $hasAccess = JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')
                || JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN');

            if (!$hasAccess) {
                return response()->json([
                    'message' => 'Insufficient permissions',
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        $schemas = [
            'MAINTENANCE' => [
                'required' => [
                    'urgency',
                    'scheduled_start',
                    'scheduled_end',
                    'is_emergency',
                ],
                'optional' => [
                    'actual_start',
                    'actual_end',
                    'affected_services',
                ],
                'fields' => [
                    'urgency' => [
                        'type' => 'enum',
                        'values' => ['LOW', 'MEDIUM', 'HIGH'],
                    ],
                    'scheduled_start' => [
                        'type' => 'datetime',
                    ],
                    'scheduled_end' => [
                        'type' => 'datetime',
                    ],
                    'is_emergency' => [
                        'type' => 'boolean',
                    ],
                    'actual_start' => [
                        'type' => 'datetime',
                        'nullable' => true,
                    ],
                    'actual_end' => [
                        'type' => 'datetime',
                        'nullable' => true,
                    ],
                    'affected_services' => [
                        'type' => 'array',
                    ],
                ],
            ],
            'INCIDENT' => [
                'required' => [
                    'urgency',
                    'is_resolved',
                    'started_at',
                ],
                'optional' => [
                    'ended_at',
                    'resolution_content',
                    'affected_services',
                ],
                'fields' => [
                    'urgency' => [
                        'type' => 'enum',
                        'values' => ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'],
                    ],
                    'is_resolved' => [
                        'type' => 'boolean',
                    ],
                    'started_at' => [
                        'type' => 'datetime',
                    ],
                    'ended_at' => [
                        'type' => 'datetime',
                        'nullable' => true,
                    ],
                    'resolution_content' => [
                        'type' => 'string',
                    ],
                    'affected_services' => [
                        'type' => 'array',
                    ],
                ],
            ],
            'NEWS' => [
                'required' => [
                    'news_type',
                    'target_audience',
                    'summary',
                ],
                'optional' => [
                    'call_to_action',
                ],
                'fields' => [
                    'news_type' => [
                        'type' => 'enum',
                        'values' => ['feature_release', 'policy_update', 'general_update'],
                    ],
                    'target_audience' => [
                        'type' => 'array',
                        'values' => ['users', 'agents', 'admins'],
                    ],
                    'summary' => [
                        'type' => 'string',
                    ],
                    'call_to_action' => [
                        'type' => 'object',
                        'nullable' => true,
                    ],
                ],
            ],
            'ALERT' => [
                'required' => [
                    'urgency',
                    'alert_type',
                    'message',
                    'action_required',
                    'started_at',
                ],
                'optional' => [
                    'action_description',
                    'ended_at',
                    'affected_services',
                ],
                'fields' => [
                    'urgency' => [
                        'type' => 'enum',
                        'values' => ['HIGH', 'CRITICAL'],
                    ],
                    'alert_type' => [
                        'type' => 'enum',
                        'values' => ['security', 'system', 'service', 'compliance'],
                    ],
                    'message' => [
                        'type' => 'string',
                    ],
                    'action_required' => [
                        'type' => 'boolean',
                    ],
                    'action_description' => [
                        'type' => 'string',
                        'nullable' => true,
                    ],
                    'started_at' => [
                        'type' => 'datetime',
                    ],
                    'ended_at' => [
                        'type' => 'datetime',
                        'nullable' => true,
                    ],
                    'affected_services' => [
                        'type' => 'array',
                        'nullable' => true,
                    ],
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $schemas,
        ], 200);
    }

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
     * @param Announcement $announcement The announcement to retrieve (route model binding)
     * @param VisibilityService $visibilityService Service for role-based checks
     * @return JsonResponse Success response with announcement data
     */
    public function show(Announcement $announcement, VisibilityService $visibilityService): JsonResponse
    {
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
