<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Http\Requests\UpdateAlertRequest;
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
     * Get a single announcement by ID.
     *
     * Route: GET /api/v1/announcements/{id}
     *
     * @param Announcement $announcement The announcement to retrieve (route model binding)
     * @return JsonResponse Success response with announcement data
     */
    public function show(Announcement $announcement): JsonResponse
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

        // Load relationships for resource
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
