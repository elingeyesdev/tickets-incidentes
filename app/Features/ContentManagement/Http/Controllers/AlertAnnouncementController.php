<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Http\Requests\CreateAlertRequest;
use App\Features\ContentManagement\Http\Resources\AnnouncementResource;
use App\Features\ContentManagement\Jobs\PublishAnnouncementJob;
use App\Features\ContentManagement\Models\Announcement;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Alert Announcement Controller
 *
 * Handles alert announcement creation.
 * Supports draft, publish, and schedule actions.
 * Updates are handled by the generic AnnouncementController.
 */
class AlertAnnouncementController extends Controller
{
    /**
     * Store a new alert announcement
     *
     * POST /api/announcements/alerts
     *
     * Creates an alert announcement with optional immediate publishing or scheduling.
     * Company ID is inferred from authenticated user's JWT token.
     *
     * @param CreateAlertRequest $request Validated request data
     * @return JsonResponse 201 Created with announcement data
     */
    public function store(CreateAlertRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get company_id from JWT token using JWTHelper
        // JWTHelper extracts company_id for COMPANY_ADMIN role from JWT payload
        try {
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            if (!$companyId) {
                return response()->json([
                    'message' => 'Insufficient permissions',
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        $authorId = auth()->id();

        // Prepare metadata - INCLUDE ALL FIELDS
        $metadata = [
            'urgency' => $validated['metadata']['urgency'],  // HIGH or CRITICAL
            'alert_type' => $validated['metadata']['alert_type'],  // security, system, service, compliance
            'message' => $validated['metadata']['message'],
            'action_required' => $validated['metadata']['action_required'],
            'action_description' => $validated['metadata']['action_description'] ?? null,  // nullable
            'started_at' => $validated['metadata']['started_at'],
            'ended_at' => $validated['metadata']['ended_at'] ?? null,  // nullable
        ];

        // Include affected_services if present
        if (isset($validated['metadata']['affected_services'])) {
            $metadata['affected_services'] = $validated['metadata']['affected_services'];
        }

        // Determine action (default: draft)
        $action = $validated['action'] ?? 'draft';
        $status = PublicationStatus::DRAFT;
        $publishedAt = null;

        // Handle action
        if ($action === 'publish') {
            $status = PublicationStatus::PUBLISHED;
            $publishedAt = now();
        } elseif ($action === 'schedule') {
            $status = PublicationStatus::SCHEDULED;
            // Add scheduled_for to metadata
            $metadata['scheduled_for'] = $validated['scheduled_for'];
        }

        // Build announcement data
        $data = [
            'company_id' => $companyId,
            'author_id' => $authorId,
            'type' => AnnouncementType::ALERT,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'metadata' => $metadata,
            'status' => $status,
            'published_at' => $publishedAt,
        ];

        // Create announcement
        $announcement = Announcement::create($data);

        // If scheduled, dispatch job
        if ($action === 'schedule') {
            $scheduledFor = Carbon::parse($validated['scheduled_for']);
            $delaySeconds = now()->diffInSeconds($scheduledFor);
            PublishAnnouncementJob::dispatch($announcement)->delay($delaySeconds);
        }

        // Load relationships for resource
        $announcement->load(['company', 'author.profile']);

        // Determine success message
        $message = match ($action) {
            'publish' => 'Alert published successfully',
            'schedule' => "Alert scheduled for {$validated['scheduled_for']}",
            default => 'Alert created as draft',
        };

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new AnnouncementResource($announcement),
        ], 201);
    }
}
