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
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        if ($announcement->company_id !== $userCompanyId) {
            abort(403, 'No autorizado para publicar este anuncio');
        }

        $announcement = $this->announcementService->publish($announcement);
        $announcement->load(['company', 'author.profile']);

        return response()->json([
            'success' => true,
            'message' => 'Anuncio publicado exitosamente',
            'data' => new AnnouncementResource($announcement),
        ], 200);
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
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        if ($announcement->company_id !== $userCompanyId) {
            abort(403, 'No autorizado para programar este anuncio');
        }

        $validated = $request->validated();
        $scheduledFor = Carbon::parse($validated['scheduled_for']);

        $announcement = $this->announcementService->schedule($announcement, $scheduledFor);
        $announcement->load(['company', 'author.profile']);

        // Format the date for the success message
        $formattedDate = $scheduledFor->format('d/m/Y H:i');

        return response()->json([
            'success' => true,
            'message' => "Anuncio programado para publicación el {$formattedDate}",
            'data' => new AnnouncementResource($announcement),
        ], 200);
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
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        if ($announcement->company_id !== $userCompanyId) {
            abort(403, 'No autorizado para desprogramar este anuncio');
        }

        // Verify announcement is actually scheduled
        if ($announcement->status !== PublicationStatus::SCHEDULED) {
            abort(400, 'El anuncio no está programado');
        }

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
            'message' => 'Anuncio desprogramado y regresado a borrador',
            'data' => new AnnouncementResource($announcement),
        ], 200);
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
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        if ($announcement->company_id !== $userCompanyId) {
            abort(403, 'No autorizado para archivar este anuncio');
        }

        $announcement = $this->announcementService->archive($announcement);
        $announcement->load(['company', 'author.profile']);

        return response()->json([
            'success' => true,
            'message' => 'Anuncio archivado exitosamente',
            'data' => new AnnouncementResource($announcement),
        ], 200);
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
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        if ($announcement->company_id !== $userCompanyId) {
            abort(403, 'No autorizado para restaurar este anuncio');
        }

        $announcement = $this->announcementService->restore($announcement);
        $announcement->load(['company', 'author.profile']);

        return response()->json([
            'success' => true,
            'message' => 'Anuncio restaurado a borrador',
            'data' => new AnnouncementResource($announcement),
        ], 200);
    }
}
