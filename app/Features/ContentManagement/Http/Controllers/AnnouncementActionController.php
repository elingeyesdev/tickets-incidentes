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
 * All business logic is delegated to AnnouncementService and SchedulingService.
 * Controller only orchestrates the request/response flow.
 */
class AnnouncementActionController
{
    public function __construct(
        private readonly AnnouncementService $announcementService,
        private readonly SchedulingService $schedulingService
    ) {}

    /**
     * Publish an announcement immediately
     *
     * POST /api/v1/announcements/:id/publish
     *
     * Changes announcement status to PUBLISHED and sets published_at to current time.
     *
     * @param Announcement $announcement The announcement to publish
     * @return JsonResponse
     */
    public function publish(Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's company
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

    /**
     * Schedule an announcement for future publication
     *
     * POST /api/v1/announcements/:id/schedule
     *
     * Changes announcement status to SCHEDULED and enqueues a job to publish
     * at the specified future date/time.
     *
     * @param ScheduleAnnouncementRequest $request Validated request with scheduled_for
     * @param Announcement $announcement The announcement to schedule
     * @return JsonResponse
     */
    public function schedule(ScheduleAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's company
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

    /**
     * Unschedule a scheduled announcement
     *
     * POST /api/v1/announcements/:id/unschedule
     *
     * Changes announcement status back to DRAFT, removes scheduled_for from metadata,
     * and cancels the queued publication job.
     *
     * @param Announcement $announcement The announcement to unschedule
     * @return JsonResponse
     */
    public function unschedule(Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's company
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

    /**
     * Archive a published announcement
     *
     * POST /api/v1/announcements/:id/archive
     *
     * Changes announcement status to ARCHIVED. Only PUBLISHED announcements
     * can be archived (validated by service).
     *
     * @param Announcement $announcement The announcement to archive
     * @return JsonResponse
     */
    public function archive(Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's company
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

    /**
     * Restore an archived announcement to draft status
     *
     * POST /api/v1/announcements/:id/restore
     *
     * Changes announcement status back to DRAFT and clears published_at.
     * Only ARCHIVED announcements can be restored (validated by service).
     *
     * @param Announcement $announcement The announcement to restore
     * @return JsonResponse
     */
    public function restore(Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's company
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
