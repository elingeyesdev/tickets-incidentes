<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Http\Requests\CreateNewsRequest;
use App\Features\ContentManagement\Http\Resources\AnnouncementResource;
use App\Features\ContentManagement\Jobs\PublishAnnouncementJob;
use App\Features\ContentManagement\Models\Announcement;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * News Announcement Controller
 *
 * Handles news announcement creation.
 * Supports draft, publish, and schedule actions.
 * Updates are handled by the generic AnnouncementController.
 */
class NewsAnnouncementController extends Controller
{
    /**
     * Store a new news announcement
     *
     * POST /api/announcements/news
     *
     * Creates a news announcement with optional immediate publishing or scheduling.
     * Company ID is inferred from authenticated user's JWT token.
     *
     * @param CreateNewsRequest $request Validated request data
     * @return JsonResponse 201 Created with announcement data
     */
    public function store(CreateNewsRequest $request): JsonResponse
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

        // Prepare metadata (all fields from request)
        $metadata = [
            'news_type' => $validated['metadata']['news_type'],
            'target_audience' => $validated['metadata']['target_audience'],
            'summary' => $validated['metadata']['summary'],
            'call_to_action' => $validated['metadata']['call_to_action'] ?? null,
        ];

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
            'type' => AnnouncementType::NEWS,
            'title' => $validated['title'],
            'content' => $validated['body'], // CRITICAL: 'body' in request → 'content' in DB
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
            'publish' => 'News published successfully',
            'schedule' => "News scheduled for {$validated['scheduled_for']}",
            default => 'News created as draft',
        };

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new AnnouncementResource($announcement),
        ], 201);
    }
}
