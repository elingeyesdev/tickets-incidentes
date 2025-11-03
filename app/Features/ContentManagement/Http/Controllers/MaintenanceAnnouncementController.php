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
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        if ($announcement->company_id !== $userCompanyId) {
            abort(403, 'No autorizado para marcar inicio en este anuncio');
        }

        // Verify announcement type
        if ($announcement->type !== AnnouncementType::MAINTENANCE) {
            abort(400, 'El anuncio no es de tipo mantenimiento');
        }

        // Verify not already started
        if (isset($announcement->metadata['actual_start']) && $announcement->metadata['actual_start'] !== null) {
            abort(400, 'Inicio ya registrado');
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
            'message' => 'Inicio de mantenimiento registrado',
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
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        if ($announcement->company_id !== $userCompanyId) {
            abort(403, 'No autorizado para marcar finalización en este anuncio');
        }

        // Verify announcement type
        if ($announcement->type !== AnnouncementType::MAINTENANCE) {
            abort(400, 'El anuncio no es de tipo mantenimiento');
        }

        // Verify maintenance has been started
        if (!isset($announcement->metadata['actual_start']) || $announcement->metadata['actual_start'] === null) {
            abort(400, 'Marca inicio primero');
        }

        // Verify not already completed
        if (isset($announcement->metadata['actual_end']) && $announcement->metadata['actual_end'] !== null) {
            abort(400, 'Mantenimiento ya completado');
        }

        // Validate end time is after start time
        $actualStart = Carbon::parse($announcement->metadata['actual_start']);
        $actualEnd = now();

        if ($actualEnd->lte($actualStart)) {
            abort(422, 'La fecha de finalización debe ser posterior a la fecha de inicio');
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
            'message' => 'Mantenimiento completado',
            'data' => new AnnouncementResource($announcement),
        ], 200);
    }
}
