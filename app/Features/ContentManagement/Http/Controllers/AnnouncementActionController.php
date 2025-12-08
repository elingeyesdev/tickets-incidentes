<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Http\Requests\ScheduleAnnouncementRequest;
use App\Features\ContentManagement\Http\Resources\AnnouncementResource;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Services\AnnouncementService;
use App\Features\ContentManagement\Services\SchedulingService;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Announcement Action Controller
 *
 * Handles state transitions for announcements:
 * - Publishing (draft → published)
 * - Scheduling (draft → scheduled)
 * - Unscheduling (scheduled → draft)
 * - Archiving (published → archived)
 * - Restoring (archived → draft)
 *
 * All business logic is delegated to the AnnouncementService and SchedulingService.
 * Controller only orchestrates the request/response flow.
 */
class AnnouncementActionController
{
    public function __construct(
        private readonly AnnouncementService $announcementService,
        private readonly SchedulingService $schedulingService
    ) {}

    #[OA\Post(
        path: '/api/announcements/{id}/publish',
        operationId: 'publish_announcement',
        description: 'Publish an announcement immediately, changing its status to PUBLISHED and setting published_at to current timestamp. Can publish announcements in DRAFT or SCHEDULED status. Cannot publish announcements that are already PUBLISHED or ARCHIVED. User must be the COMPANY_ADMIN who owns the announcement. If announcement was previously SCHEDULED, any queued publication jobs are automatically cancelled by the service.',
        summary: 'Publish announcement immediately',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Announcement Actions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement published successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message', type: 'string', example: 'Announcement published successfully'),
                        new OA\Property(property: 'data', description: 'Published announcement resource with status=PUBLISHED and current published_at timestamp', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid state transition',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', enum: ['Announcement is already published', 'Cannot publish archived announcement']),
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
                description: 'Forbidden - user lacks COMPANY_ADMIN role or does not own announcement',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Announcement not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Publish an announcement immediately
     *
     * POST /api/announcements/{id}/publish
     *
     * Changes announcement status to PUBLISHED and sets published_at to the current time.
     * Can transition from DRAFT or SCHEDULED status.
     *
     * @param Announcement $announcement The announcement to publish
     * @return JsonResponse 200 OK with a published announcement
     */
    public function publish(Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's active company
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
            $announcement = $this->announcementService->publish($announcement);
            $announcement->load(['company', 'author.profile']);

            return response()->json([
                'success' => true,
                'message' => 'Announcement published successfully',
                'data' => new AnnouncementResource($announcement),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/announcements/{id}/schedule',
        operationId: 'schedule_announcement',
        description: 'Schedule an announcement for future publication. Changes status to SCHEDULED and enqueues PublishAnnouncementJob with calculated delay. The scheduled_for datetime must be 5 minutes to 1 year in the future. Can only schedule announcements in DRAFT status. If rescheduling a previously SCHEDULED announcement, the old job is cancelled and new job is enqueued. Automatic job cancellation occurs if announcement is published before scheduled time.',
        summary: 'Schedule announcement for future publication',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'Schedule data with future publication datetime',
            required: true,
            content: new OA\JsonContent(
                required: ['scheduled_for'],
                properties: [
                    new OA\Property(property: 'scheduled_for', description: 'ISO8601 datetime for publication (required, must be 5 min - 1 year in future)', type: 'string', format: 'date-time', example: '2025-11-20T15:30:00Z'),
                ],
                type: 'object'
            )
        ),
        tags: ['Announcement Actions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement scheduled successfully, job enqueued',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message with formatted publication date', type: 'string', example: 'Announcement scheduled for publication on 20/11/2025 15:30'),
                        new OA\Property(property: 'data', description: 'Scheduled announcement resource with status=SCHEDULED and metadata containing scheduled_for', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - validation or invalid state transition',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message describing validation failure', type: 'string'),
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
                description: 'Forbidden - user lacks COMPANY_ADMIN role or does not own announcement',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Announcement not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Schedule an announcement for future publication
     *
     * POST /api/announcements/{id}/schedule
     *
     * Changes announcement status to SCHEDULED and enqueues a PublishAnnouncementJob
     * to be executed at the scheduled datetime. Calculates delay in seconds from now.
     *
     * @param ScheduleAnnouncementRequest $request Validated request with scheduled_for
     * @param Announcement $announcement The announcement to schedule
     * @return JsonResponse 200 OK with a scheduled announcement
     */
    public function schedule(ScheduleAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to the user's active company
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
            $scheduledFor = Carbon::parse($validated['scheduled_for']);

            $announcement = $this->announcementService->schedule($announcement, $scheduledFor);
            $announcement->load(['company', 'author.profile']);

            // Format the date for the success message
            $formattedDate = $scheduledFor->format('d/m/Y H:i');

            return response()->json([
                'success' => true,
                'message' => "Announcement scheduled for publication on {$formattedDate}",
                'data' => new AnnouncementResource($announcement),
            ], 200);
        } catch (\RuntimeException | \InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/announcements/{id}/unschedule',
        operationId: 'unschedule_announcement',
        description: 'Unschedule a SCHEDULED announcement, returning it to DRAFT status. Removes scheduled_for from metadata and cancels any queued PublishAnnouncementJob in Redis. Cannot unschedule announcements that are not SCHEDULED (DRAFT, PUBLISHED, ARCHIVED return 400 errors). Also prevents unscheduling of PUBLISHED announcements (separate validation).',
        summary: 'Unschedule announcement',
        tags: ['Announcement Actions'],
        security: [
            ['bearerAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement unscheduled successfully, job cancelled',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message', type: 'string', example: 'Announcement unscheduled and returned to draft'),
                        new OA\Property(property: 'data', description: 'Unscheduled announcement resource with status=DRAFT, published_at=null, and scheduled_for removed from metadata', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid state transition',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', enum: ['Announcement is not scheduled', 'Cannot unschedule published announcement']),
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
                description: 'Forbidden - user lacks COMPANY_ADMIN role or does not own announcement',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Announcement not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Unschedule a scheduled announcement
     *
     * POST /api/announcements/{id}/unschedule
     *
     * Changes announcement status back to DRAFT, removes scheduled_for from metadata,
     * and cancels the queued publication job via SchedulingService.
     *
     * @param Announcement $announcement The announcement to unschedule
     * @return JsonResponse 200 OK with unscheduled announcement
     */
    public function unschedule(Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's active company
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

        // Verify announcement is not published
        if ($announcement->status === PublicationStatus::PUBLISHED) {
            return response()->json([
                'message' => 'Cannot unschedule published announcement',
            ], 400);
        }

        // Verify announcement is actually scheduled
        if ($announcement->status !== PublicationStatus::SCHEDULED) {
            return response()->json([
                'message' => 'Announcement is not scheduled',
            ], 400);
        }

        try {
            // Update status to DRAFT
            $announcement->status = PublicationStatus::DRAFT;

            // Remove scheduled_for from metadata
            $metadata = $announcement->metadata ?? [];
            unset($metadata['scheduled_for']);
            $announcement->metadata = $metadata;

            // Save changes
            $announcement->save();

            // Cancel the scheduled job
            $this->schedulingService->cancelJob($announcement->id);

            // Refresh and load relationships for resource
            $announcement = $announcement->fresh();
            $announcement->load(['company', 'author.profile']);

            return response()->json([
                'success' => true,
                'message' => 'Announcement unscheduled and returned to draft',
                'data' => new AnnouncementResource($announcement),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/announcements/{id}/archive',
        operationId: 'archive_announcement',
        description: 'Archive a PUBLISHED announcement, changing its status to ARCHIVED. Only PUBLISHED announcements can be archived (service validation). Preserves published_at timestamp. Archived announcements can be restored to DRAFT status later. Cannot archive DRAFT or SCHEDULED announcements.',
        summary: 'Archive published announcement',
        tags: ['Announcement Actions'],
        security: [
            ['bearerAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement archived successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message', type: 'string', example: 'Announcement archived successfully'),
                        new OA\Property(property: 'data', description: 'Archived announcement resource with status=ARCHIVED and published_at preserved', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid state transition',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message explaining why announcement cannot be archived', type: 'string'),
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
                description: 'Forbidden - user lacks COMPANY_ADMIN role or does not own announcement',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Announcement not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Archive a published announcement
     *
     * POST /api/announcements/{id}/archive
     *
     * Changes announcement status to ARCHIVED via AnnouncementService.
     * Only PUBLISHED announcements can be archived (service validates).
     *
     * @param Announcement $announcement The announcement to archive
     * @return JsonResponse 200 OK with an archived announcement
     */
    public function archive(Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to the user's active company
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
            $announcement = $this->announcementService->archive($announcement);
            $announcement->load(['company', 'author.profile']);

            return response()->json([
                'success' => true,
                'message' => 'Announcement archived successfully',
                'data' => new AnnouncementResource($announcement),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/announcements/{id}/restore',
        operationId: 'restore_announcement',
        description: 'Restore an ARCHIVED announcement to DRAFT status, clearing published_at timestamp. Only ARCHIVED announcements can be restored (service validation). Preserves original content and metadata (urgency, scheduled dates, etc.). Restored announcements become editable again and can be re-published. Cannot restore DRAFT or PUBLISHED announcements.',
        summary: 'Restore archived announcement to draft',
        tags: ['Announcement Actions'],
        security: [
            ['bearerAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement restored successfully to draft status',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message', type: 'string', example: 'Announcement restored to draft'),
                        new OA\Property(property: 'data', description: 'Restored announcement resource with status=DRAFT, published_at=null, and all metadata preserved', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid state transition',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message explaining why announcement cannot be restored', type: 'string'),
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
                description: 'Forbidden - user lacks COMPANY_ADMIN role or does not own announcement',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Announcement not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Restore an archived announcement to draft status
     *
     * POST /api/announcements/{id}/restore
     *
     * Changes announcement status back to DRAFT and clears published_at via AnnouncementService.
     * Only ARCHIVED announcements can be restored (service validates).
     *
     * @param Announcement $announcement The announcement to restore
     * @return JsonResponse 200 OK with a restored announcement
     */
    public function restore(Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to the user's active company
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
            $announcement = $this->announcementService->restore($announcement);
            $announcement->load(['company', 'author.profile']);

            return response()->json([
                'success' => true,
                'message' => 'Announcement restored to draft',
                'data' => new AnnouncementResource($announcement),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
