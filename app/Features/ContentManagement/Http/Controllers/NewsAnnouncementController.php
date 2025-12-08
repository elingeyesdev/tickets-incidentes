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
use OpenApi\Attributes as OA;

/**
 * News Announcement Controller
 *
 * Handles news announcement creation.
 * Supports draft, publish, and schedule actions.
 * Updates are handled by the generic AnnouncementController.
 */
class NewsAnnouncementController extends Controller
{
    #[OA\Post(
        path: '/api/announcements/news',
        operationId: 'create_news_announcement',
        description: 'Create a new news announcement. Only COMPANY_ADMIN role can create news announcements. Company ID is automatically inferred from JWT token. News can be created as DRAFT (default), published immediately (action=publish), or scheduled for future publication (action=schedule). The request body field "body" is stored as "content" in the database. Metadata includes news_type (feature_release, policy_update, general_update), target_audience array (users, agents, admins), summary text, and optional call_to_action with text and https URL. If action=schedule, a PublishAnnouncementJob is dispatched with calculated delay.',
        summary: 'Create news announcement',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'News announcement creation data',
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'body', 'metadata'],
                properties: [
                    new OA\Property(property: 'title', description: 'Announcement title (5-200 characters)', type: 'string', example: 'New Feature Release: Dark Mode'),
                    new OA\Property(property: 'body', description: 'Announcement content/body text (minimum 10 characters, stored as "content" in database)', type: 'string', example: 'We are excited to announce the launch of dark mode across all our applications.'),
                    new OA\Property(
                        property: 'metadata',
                        description: 'News-specific metadata object containing news type, target audience, summary, and optional call to action',
                        properties: [
                            new OA\Property(property: 'news_type', description: 'Type of news announcement', type: 'string', enum: ['feature_release', 'policy_update', 'general_update'], example: 'feature_release'),
                            new OA\Property(property: 'target_audience', description: 'Array of target audiences (1-5 items from: users, agents, admins)', type: 'array', items: new OA\Items(type: 'string', enum: ['users', 'agents', 'admins']), example: ['users', 'agents']),
                            new OA\Property(property: 'summary', description: 'Summary of the news (10-500 characters)', type: 'string', example: 'Dark mode is now available for all users'),
                            new OA\Property(
                                property: 'call_to_action',
                                description: 'Optional call to action with text and https URL',
                                properties: [
                                    new OA\Property(property: 'text', description: 'CTA button text (required if call_to_action is provided)', type: 'string', example: 'Read more'),
                                    new OA\Property(property: 'url', description: 'CTA URL (must be valid HTTPS URL, required if call_to_action is provided)', type: 'string', format: 'uri', example: 'https://example.com/feature'),
                                ],
                                type: 'object',
                                nullable: true
                            ),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(property: 'action', description: 'Action to perform: draft (default), publish (immediately), or schedule (requires scheduled_for)', type: 'string', enum: ['draft', 'publish', 'schedule'], example: 'publish', nullable: true),
                    new OA\Property(property: 'scheduled_for', description: 'ISO8601 datetime for scheduling publication (required if action=schedule, must be at least 5 minutes in future, max 1 year)', type: 'string', format: 'date-time', example: '2025-11-20T10:00:00Z', nullable: true),
                ],
                type: 'object'
            )
        ),
        tags: ['News Announcements'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'News announcement created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message indicating the action performed', type: 'string', enum: ['News created as draft', 'News published successfully', 'News scheduled for {scheduled_for}']),
                        new OA\Property(property: 'data', description: 'Created announcement resource with full details including type=NEWS, status (DRAFT/PUBLISHED/SCHEDULED), metadata, and timestamps', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - validation or logic errors',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'The title must be at least 5 characters.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - user lacks COMPANY_ADMIN role or valid company in JWT',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Unprocessable Entity - validation errors in request data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Validation error message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(property: 'errors', description: 'Object with field names as keys and array of error messages as values', type: 'object', example: ['title' => ['The title must be at least 5 characters.'], 'metadata.news_type' => ['The metadata.news_type must be one of: feature_release, policy_update, general_update.']]),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error - unexpected server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'An unexpected error occurred'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Store a new news announcement
     *
     * POST /api/announcements/news
     *
     * Creates a news announcement with optional immediate publishing or scheduling.
     * Company ID is inferred from an authenticated user's JWT token.
     * Dispatches PublishAnnouncementJob if action=schedule.
     *
     * @param CreateNewsRequest $request Validated request data
     * @return JsonResponse 201 Created with announcement data
     */
    public function store(CreateNewsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get company_id from active role in JWT token
        // Uses getActiveCompanyId() to get the company from the ACTIVE role
        try {
            $companyId = JWTHelper::getActiveCompanyId();

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

        // Determine a success message
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
