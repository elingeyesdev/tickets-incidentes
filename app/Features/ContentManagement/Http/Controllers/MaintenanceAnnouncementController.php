<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Http\Requests\StoreMaintenanceRequest;
use App\Features\ContentManagement\Http\Resources\AnnouncementResource;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Services\AnnouncementService;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Maintenance Announcement Controller
 *
 * Handles maintenance announcement creation and lifecycle management.
 * Supports draft, publish, and schedule actions.
 * Tracks actual maintenance start and end times.
 */
class MaintenanceAnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $announcementService
    ) {
    }

    /**
     * Store a new maintenance announcement
     *
     * POST /api/v1/announcements/maintenance
     *
     * Creates a maintenance announcement with optional immediate publishing or scheduling.
     * Company ID is inferred from authenticated user's JWT token.
     *
     * @param StoreMaintenanceRequest $request Validated request data
     * @return JsonResponse 201 Created with announcement data
     */
    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get company_id from JWT token using JWTHelper
        // JWTHelper extracts company_id for COMPANY_ADMIN role from JWT payload
        try {
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            if (!$companyId) {
                abort(403, 'Usuario no tiene compañía asignada');
            }
        } catch (\Exception $e) {
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        $authorId = auth()->id();

        // Build announcement data
        $data = [
            'company_id' => $companyId,
            'author_id' => $authorId,
            'type' => AnnouncementType::MAINTENANCE,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'metadata' => [
                'urgency' => $validated['urgency'],
                'scheduled_start' => $validated['scheduled_start'],
                'scheduled_end' => $validated['scheduled_end'],
                'is_emergency' => $validated['is_emergency'],
                'affected_services' => $validated['affected_services'] ?? [],
                'actual_start' => null,
                'actual_end' => null,
            ],
            'status' => PublicationStatus::DRAFT,
        ];

        // Handle action
        $action = $validated['action'] ?? 'draft';
        $announcement = null;

        if ($action === 'publish') {
            // Create as draft first, then publish
            $announcement = $this->announcementService->create($data);
            $announcement = $this->announcementService->publish($announcement);
            $message = 'Mantenimiento publicado exitosamente';
        } elseif ($action === 'schedule') {
            // Create as draft first, then schedule
            $scheduledFor = Carbon::parse($validated['scheduled_for']);
            $announcement = $this->announcementService->create($data);
            $announcement = $this->announcementService->schedule($announcement, $scheduledFor);
            $message = 'Mantenimiento programado exitosamente';
        } else {
            // Just create as draft
            $announcement = $this->announcementService->create($data);
            $message = 'Mantenimiento creado como borrador';
        }

        // Load relationships for resource
        $announcement->load(['company', 'author.profile']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new AnnouncementResource($announcement),
        ], 201);
    }

    /**
     * Mark maintenance start time
     *
     * POST /api/v1/announcements/maintenance/:id/start
     *
     * Records the actual start time of a maintenance window.
     * Can only be called once per maintenance announcement.
     *
     * @param Announcement $announcement The announcement to update
     * @return JsonResponse 200 OK with updated announcement
     */
    public function markStart(Announcement $announcement): JsonResponse
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

        // Verify announcement type
        if ($announcement->type !== AnnouncementType::MAINTENANCE) {
            return response()->json([
                'message' => 'Announcement is not a maintenance type',
            ], 400);
        }

        // Verify not already started
        if (isset($announcement->metadata['actual_start']) && $announcement->metadata['actual_start'] !== null) {
            return response()->json([
                'message' => 'Maintenance start already marked',
            ], 400);
        }

        // Update metadata with actual start time
        $metadata = $announcement->metadata;
        $metadata['actual_start'] = now()->toIso8601String();
        $announcement->metadata = $metadata;
        $announcement->save();

        // Load relationships for resource
        $announcement->load(['company', 'author.profile']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance start recorded',
            'data' => new AnnouncementResource($announcement),
        ], 200);
    }

    /**
     * Mark maintenance complete
     *
     * POST /api/v1/announcements/maintenance/:id/complete
     *
     * Records the actual end time of a maintenance window.
     * Requires maintenance to have been started first.
     * Validates that end time is after start time.
     *
     * @param Announcement $announcement The announcement to update
     * @return JsonResponse 200 OK with updated announcement
     */
    public function markComplete(Announcement $announcement): JsonResponse
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

        // Verify announcement type
        if ($announcement->type !== AnnouncementType::MAINTENANCE) {
            return response()->json([
                'message' => 'Announcement is not a maintenance type',
            ], 400);
        }

        // Verify maintenance has been started
        if (!isset($announcement->metadata['actual_start']) || $announcement->metadata['actual_start'] === null) {
            return response()->json([
                'message' => 'Mark start first',
            ], 400);
        }

        // Verify not already completed
        if (isset($announcement->metadata['actual_end']) && $announcement->metadata['actual_end'] !== null) {
            return response()->json([
                'message' => 'Maintenance already completed',
            ], 400);
        }

        // Validate end time is after start time
        $actualStart = Carbon::parse($announcement->metadata['actual_start']);
        $actualEnd = now();

        if ($actualEnd->lte($actualStart)) {
            return response()->json([
                'message' => 'The end date must be after the start date.',
            ], 400);
        }

        // Update metadata with actual end time
        $metadata = $announcement->metadata;
        $metadata['actual_end'] = $actualEnd->toIso8601String();
        $announcement->metadata = $metadata;
        $announcement->save();

        // Load relationships for resource
        $announcement->load(['company', 'author.profile']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance completed',
            'data' => new AnnouncementResource($announcement),
        ], 200);
    }
}
