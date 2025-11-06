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
use OpenApi\Attributes as OA;

/**
 * Alert Announcement Controller
 *
 * Handles alert announcement creation.
 * Supports draft, publish, and schedule actions.
 * Updates are handled by the generic AnnouncementController.
 */
class AlertAnnouncementController extends Controller
{
    #[OA\Post(
        path: '/api/announcements/alerts',
        operationId: 'create_alert_announcement',
        description: 'Create a new alert announcement for urgent notifications. Only COMPANY_ADMIN role can create alerts. Company ID is automatically inferred from JWT token. Alerts can be created as DRAFT (default), published immediately (action=publish), or scheduled for future publication (action=schedule). Alert-specific metadata includes urgency (HIGH or CRITICAL only), alert_type (security, system, service, compliance), message, action_required flag, optional action_description (required if action_required=true), started_at datetime, optional ended_at datetime, and optional affected_services array. If action=schedule, a PublishAnnouncementJob is dispatched with calculated delay.',
        summary: 'Create alert announcement',
        requestBody: new OA\RequestBody(
            description: 'Alert announcement creation data with security-critical metadata',
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content', 'metadata'],
                properties: [
                    new OA\Property(property: 'title', description: 'Alert title (5-200 characters)', type: 'string', example: 'Security Breach Detected'),
                    new OA\Property(property: 'content', description: 'Alert content/description (minimum 10 characters)', type: 'string', example: 'We have detected unauthorized access attempts. Please change your password immediately.'),
                    new OA\Property(
                        property: 'metadata',
                        description: 'Alert-specific metadata object with security and operational details',
                        properties: [
                            new OA\Property(property: 'urgency', description: 'Urgency level (HIGH or CRITICAL only for alerts)', type: 'string', enum: ['HIGH', 'CRITICAL'], example: 'CRITICAL'),
                            new OA\Property(property: 'alert_type', description: 'Type of alert', type: 'string', enum: ['security', 'system', 'service', 'compliance'], example: 'security'),
                            new OA\Property(property: 'message', description: 'Alert message (10-500 characters)', type: 'string', example: 'Immediate action required: Change your password now'),
                            new OA\Property(property: 'action_required', description: 'Whether action is required from users (boolean, if true action_description becomes required)', type: 'boolean', example: true),
                            new OA\Property(property: 'action_description', description: 'Description of required action (required if action_required=true, max 300 chars)', type: 'string', example: 'Navigate to Settings > Security and update your password', nullable: true),
                            new OA\Property(property: 'started_at', description: 'Alert start datetime (ISO8601, required)', type: 'string', format: 'date-time', example: '2025-11-06T10:00:00Z'),
                            new OA\Property(property: 'ended_at', description: 'Alert end datetime (ISO8601, optional, must be after started_at)', type: 'string', format: 'date-time', example: '2025-11-06T18:00:00Z', nullable: true),
                            new OA\Property(property: 'affected_services', description: 'Array of affected service names (optional)', type: 'array', items: new OA\Items(type: 'string'), example: ['authentication', 'user_management'], nullable: true),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(property: 'action', description: 'Action to perform: draft (default), publish (immediately), or schedule (requires scheduled_for)', type: 'string', enum: ['draft', 'publish', 'schedule'], example: 'publish', nullable: true),
                    new OA\Property(property: 'scheduled_for', description: 'ISO8601 datetime for scheduling publication (required if action=schedule, must be at least 5 minutes in future, max 1 year)', type: 'string', format: 'date-time', example: '2025-11-20T10:00:00Z', nullable: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Alert Announcements'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Alert announcement created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message indicating the action performed', type: 'string', enum: ['Alert created as draft', 'Alert published successfully', 'Alert scheduled for {scheduled_for}']),
                        new OA\Property(property: 'data', description: 'Created alert announcement resource with type=ALERT, status (DRAFT/PUBLISHED/SCHEDULED), full metadata, and timestamps', type: 'object'),
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
                        new OA\Property(property: 'errors', description: 'Object with field names as keys and array of error messages as values', type: 'object', example: ['title' => ['The title must be at least 5 characters.'], 'metadata.urgency' => ['The metadata.urgency must be HIGH or CRITICAL.']]),
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
     * Store a new alert announcement
     *
     * POST /api/announcements/alerts
     *
     * Creates an alert announcement with optional immediate publishing or scheduling.
     * Company ID is inferred from an authenticated user's JWT token.
     * Dispatches PublishAnnouncementJob if action=schedule.
     *
     * @param CreateAlertRequest $request Validated request data
     * @return JsonResponse 201 Created with announcement data
     */
    public function store(CreateAlertRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get company_id from JWT token using JWTHelper
        //  extracts company_id for the COMPANY_ADMIN role from JWT payload
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

        // Determine a success message
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
