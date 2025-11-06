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
use OpenApi\Attributes as OA;

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

    #[OA\Post(
        path: '/api/v1/announcements/incidents',
        operationId: 'create_incident_announcement',
        description: 'Create a new incident announcement. Only COMPANY_ADMIN users can create incidents. Company ID is automatically inferred from JWT token. Incidents are created in DRAFT status by default, but can be immediately published or scheduled using the action parameter. Metadata includes urgency level, affected services, incident timestamps (started_at, ended_at), resolution tracking (is_resolved, resolved_at, resolution_content).',
        summary: 'Create a new incident announcement',
        requestBody: new OA\RequestBody(
            description: 'Incident announcement data to create',
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content', 'urgency', 'is_resolved'],
                properties: [
                    new OA\Property(property: 'title', description: 'Incident title', type: 'string'),
                    new OA\Property(property: 'content', description: 'Incident description and details', type: 'string'),
                    new OA\Property(property: 'urgency', description: 'Urgency level of the incident', type: 'string', enum: ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
                    new OA\Property(property: 'is_resolved', description: 'Whether the incident is initially marked as resolved', type: 'boolean', example: false),
                    new OA\Property(property: 'started_at', description: 'When the incident started (ISO 8601). Defaults to current time if not provided', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'ended_at', description: 'When the incident ended (ISO 8601, optional)', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'resolved_at', description: 'When the incident was resolved (ISO 8601, optional)', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'resolution_content', description: 'Details about how the incident was resolved (optional)', type: 'string', nullable: true),
                    new OA\Property(property: 'affected_services', description: 'List of affected services (optional)', type: 'array', items: new OA\Items(type: 'string'), example: ['API', 'Dashboard']),
                    new OA\Property(property: 'action', description: 'Action to perform: draft (default), publish (immediately), or schedule (for scheduled_for parameter)', type: 'string', enum: ['draft', 'publish', 'schedule'], example: 'draft'),
                    new OA\Property(property: 'scheduled_for', description: 'When to publish the incident (ISO 8601, required if action=schedule)', type: 'string', format: 'date-time', nullable: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Incident Announcements'],
        security: [
            ['bearerAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Incident announcement created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', enum: ['Incident created as draft', 'Incident published successfully', 'Incident scheduled successfully'], example: 'Incident created as draft'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'company_id', description: 'Set from JWT token', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'author_id', description: 'Set to authenticated user ID', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'type', type: 'string', enum: ['INCIDENT'], example: 'INCIDENT'),
                                new OA\Property(property: 'title', type: 'string'),
                                new OA\Property(property: 'content', type: 'string'),
                                new OA\Property(property: 'status', description: 'DRAFT by default, PUBLISHED if action=publish, SCHEDULED if action=schedule', type: 'string', enum: ['DRAFT', 'PUBLISHED', 'SCHEDULED']),
                                new OA\Property(
                                    property: 'metadata',
                                    properties: [
                                        new OA\Property(property: 'urgency', type: 'string', enum: ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
                                        new OA\Property(property: 'is_resolved', type: 'boolean'),
                                        new OA\Property(property: 'started_at', type: 'string', format: 'date-time'),
                                        new OA\Property(property: 'ended_at', type: 'string', format: 'date-time', nullable: true),
                                        new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', nullable: true),
                                        new OA\Property(property: 'resolution_content', type: 'string', nullable: true),
                                        new OA\Property(property: 'affected_services', type: 'array', items: new OA\Items(type: 'string')),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Missing JWT token or JWT invalid (cannot extract company ID)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'User not authenticated or invalid JWT'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User has no assigned company in JWT token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'User has no assigned company'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Unprocessable Entity - Validation failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['title' => ['The title field is required.'], 'urgency' => ['The urgency field is required.']]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error - Database or service error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'An unexpected error occurred'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/announcements/incidents/{id}/resolve',
        operationId: 'resolve_incident_announcement',
        description: 'Mark an incident announcement as resolved with resolution details and timestamps. Only COMPANY_ADMIN users can resolve incidents from their company. Can only be called once per incident - subsequent calls will return 400 Bad Request if already resolved. Updates the metadata with is_resolved=true, resolution_content, and resolved_at timestamp. Can optionally update the title and ended_at timestamp. Announcement type must be INCIDENT.',
        summary: 'Resolve an incident announcement',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'Incident resolution data',
            required: true,
            content: new OA\JsonContent(
                required: ['resolution_content'],
                properties: [
                    new OA\Property(property: 'resolution_content', description: 'Details about how the incident was resolved', type: 'string'),
                    new OA\Property(property: 'resolved_at', description: 'When the incident was resolved (ISO 8601). Defaults to current time if not provided', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'ended_at', description: 'When the incident ended (ISO 8601, optional)', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'title', description: 'Updated incident title (optional)', type: 'string', nullable: true),
                ],
                type: 'object'
            )
        ),
        tags: ['Incident Announcements'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Incident announcement unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Incident resolved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Incident resolved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'author_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'type', type: 'string', enum: ['INCIDENT'], example: 'INCIDENT'),
                                new OA\Property(property: 'title', description: 'Updated title if provided', type: 'string'),
                                new OA\Property(property: 'content', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', enum: ['DRAFT', 'PUBLISHED', 'SCHEDULED']),
                                new OA\Property(
                                    property: 'metadata',
                                    properties: [
                                        new OA\Property(property: 'urgency', type: 'string', enum: ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
                                        new OA\Property(property: 'is_resolved', description: 'Set to true by resolve operation', type: 'boolean', example: true),
                                        new OA\Property(property: 'started_at', type: 'string', format: 'date-time'),
                                        new OA\Property(property: 'ended_at', description: 'Updated if provided in request', type: 'string', format: 'date-time', nullable: true),
                                        new OA\Property(property: 'resolved_at', description: 'Set by resolve operation (defaults to now if not provided)', type: 'string', format: 'date-time'),
                                        new OA\Property(property: 'resolution_content', description: 'Set from request body', type: 'string'),
                                        new OA\Property(property: 'affected_services', type: 'array', items: new OA\Items(type: 'string')),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Incident already resolved, not an incident type, or invalid data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', enum: ['Incident is already resolved', 'Announcement is not an incident type'], example: 'Incident is already resolved'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Missing JWT token or JWT invalid',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized or invalid JWT'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User does not belong to the incident\'s company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Incident announcement does not exist',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement not found'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Unprocessable Entity - Validation failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['resolution_content' => ['The resolution_content field is required.']]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error - Database or service error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'An unexpected error occurred'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
