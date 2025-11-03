<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Http\Requests\UpdateAnnouncementRequest;
use App\Features\ContentManagement\Http\Resources\AnnouncementResource;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Services\AnnouncementService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
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
     * Update an existing announcement.
     *
     * Handles partial updates - only updates fields that are present in the request.
     * Metadata fields (urgency, scheduled_start, scheduled_end, is_emergency, affected_services)
     * are merged with existing metadata to preserve other metadata values.
     *
     * Route: PUT /api/v1/announcements/{id}
     *
     * @param UpdateAnnouncementRequest $request Validated request with partial data
     * @param Announcement $announcement The announcement to update (route model binding)
     * @return JsonResponse Success response with updated announcement
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        // Validate that announcement belongs to user's company (from JWT)
        try {
            $userCompanyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
        } catch (\Exception $e) {
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        if ($announcement->company_id !== $userCompanyId) {
            abort(403, 'No autorizado para actualizar este anuncio');
        }

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

        // Metadata field updates (merge with existing metadata)
        $metadataUpdates = [];

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

        // Merge metadata updates with existing metadata
        if (!empty($metadataUpdates)) {
            $data['metadata'] = array_merge(
                $announcement->metadata ?? [],
                $metadataUpdates
            );
        }

        // Delegate update to service
        $updatedAnnouncement = $this->announcementService->update($announcement, $data);

        // Load relationships for resource
        $updatedAnnouncement->load(['company', 'author.profile']);

        return response()->json([
            'success' => true,
            'message' => 'Anuncio actualizado exitosamente',
            'data' => new AnnouncementResource($updatedAnnouncement),
        ], 200);
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

            \Log::warning('AnnouncementController::destroy DEBUG', [
                'announcement_id' => $announcement->id,
                'announcement_company_id' => $announcement->company_id,
                'userCompanyId' => $userCompanyId,
                'announcement_company_id_type' => gettype($announcement->company_id),
                'userCompanyId_type' => gettype($userCompanyId),
                'comparison' => $announcement->company_id !== $userCompanyId,
            ]);
        } catch (\Exception $e) {
            \Log::error('AnnouncementController::destroy error', ['error' => $e->getMessage()]);
            abort(401, 'Usuario no autenticado o JWT inválido');
        }

        if ($announcement->company_id !== $userCompanyId) {
            abort(403, 'No autorizado para eliminar este anuncio');
        }

        // Delegate deletion to service (validates deletability internally)
        try {
            $this->announcementService->delete($announcement);
        } catch (\RuntimeException $e) {
            // Convert service exceptions to HTTP responses
            $message = $e->getMessage();

            if (str_contains($message, 'published')) {
                abort(403, 'Cannot delete published announcement. Archive it first.');
            }
            if (str_contains($message, 'scheduled')) {
                abort(403, 'Cannot delete scheduled announcement. Unschedule it first.');
            }

            abort(403, $message);
        }

        return response()->json([
            'success' => true,
            'message' => 'Anuncio eliminado permanentemente',
        ], 200);
    }
}
