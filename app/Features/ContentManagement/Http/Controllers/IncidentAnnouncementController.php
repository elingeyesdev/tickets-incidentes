<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Http\Requests\ResolveIncidentRequest;
use App\Features\ContentManagement\Http\Requests\StoreIncidentRequest;
use App\Features\ContentManagement\Http\Resources\AnnouncementResource;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Services\AnnouncementService;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Incident Announcement Controller
 *
 * Handles incident announcement creation and lifecycle management.
 * Supports draft, publish, and schedule actions.
 * Tracks incident resolution with resolution content and timestamps.
 */
class IncidentAnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $announcementService
    ) {
    }

    /**
     * Store a new incident announcement
     *
     * POST /api/v1/announcements/incidents
     *
     * Creates an incident announcement with optional immediate publishing or scheduling.
     * Company ID is inferred from authenticated user's JWT token.
     *
     * @param StoreIncidentRequest $request Validated request data
     * @return JsonResponse 201 Created with announcement data
     */
    public function store(StoreIncidentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get company_id from JWT token using JWTHelper
        // JWTHelper extracts company_id for COMPANY_ADMIN role from JWT payload
        try {
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            if (!$companyId) {
                abort(403, 'User has no assigned company');
            }
        } catch (\Exception $e) {
            abort(401, 'User not authenticated or invalid JWT');
        }

        $authorId = auth()->id();

        // Build announcement data
        $data = [
            'company_id' => $companyId,
            'author_id' => $authorId,
            'type' => AnnouncementType::INCIDENT,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'metadata' => [
                'urgency' => $validated['urgency'],
                'is_resolved' => $validated['is_resolved'],
                'started_at' => $validated['started_at'] ?? now()->toIso8601String(),
                'ended_at' => $validated['ended_at'] ?? null,
                'resolved_at' => $validated['resolved_at'] ?? null,
                'resolution_content' => $validated['resolution_content'] ?? null,
                'affected_services' => $validated['affected_services'] ?? [],
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
            $message = 'Incident published successfully';
        } elseif ($action === 'schedule') {
            // Create as draft first, then schedule
            $scheduledFor = Carbon::parse($validated['scheduled_for']);
            $announcement = $this->announcementService->create($data);
            $announcement = $this->announcementService->schedule($announcement, $scheduledFor);
            $message = 'Incident scheduled successfully';
        } else {
            // Just create as draft
            $announcement = $this->announcementService->create($data);
            $message = 'Incident created as draft';
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
     * Resolve incident
     *
     * POST /api/v1/announcements/incidents/:id/resolve
     *
     * Marks an incident as resolved with resolution content and timestamps.
     * Can only be called once per incident announcement.
     * Optionally updates the title and ended_at timestamp.
     *
     * @param Announcement $announcement The announcement to resolve
     * @param ResolveIncidentRequest $request Validated request data
     * @return JsonResponse 200 OK with updated announcement
     */
    public function resolve(Announcement $announcement, ResolveIncidentRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
        if ($announcement->type !== AnnouncementType::INCIDENT) {
            return response()->json([
                'message' => 'Announcement is not an incident type',
            ], 400);
        }

        // Verify not already resolved
        if (isset($announcement->metadata['is_resolved']) && $announcement->metadata['is_resolved'] === true) {
            return response()->json([
                'message' => 'Incident is already resolved',
            ], 400);
        }

        // Prepare update data
        $metadata = $announcement->metadata;
        $metadata['is_resolved'] = true;
        $metadata['resolution_content'] = $validated['resolution_content'];
        $metadata['resolved_at'] = isset($validated['resolved_at'])
            ? Carbon::parse($validated['resolved_at'])->toIso8601String()
            : now()->toIso8601String();

        // Update ended_at if provided
        if (isset($validated['ended_at'])) {
            $metadata['ended_at'] = Carbon::parse($validated['ended_at'])->toIso8601String();
        }

        // Prepare full update data
        $updateData = ['metadata' => $metadata];

        // Update title if provided
        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }

        // Update announcement (bypassing service restrictions for metadata updates)
        $announcement->update($updateData);

        // Refresh to get latest data
        $announcement = $announcement->fresh();

        // Load relationships for resource
        $announcement->load(['company', 'author.profile']);

        return response()->json([
            'success' => true,
            'message' => 'Incident resolved successfully',
            'data' => new AnnouncementResource($announcement),
        ], 200);
    }
}
