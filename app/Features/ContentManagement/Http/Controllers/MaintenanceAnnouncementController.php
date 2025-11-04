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
use OpenApi\Attributes as OA;

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

    #[OA\Post(
        path: '/announcements/maintenance',
        operationId: 'create_maintenance_announcement',
        description: 'Creates a new maintenance announcement. Company ID is inferred from authenticated user\'s JWT token. Can be created as draft, published immediately, or scheduled for future publication.',
        summary: 'Create maintenance announcement',
        tags: ['Announcements - Maintenance'],
        requestBody: new OA\RequestBody(
            description: 'Maintenance announcement data',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', description: 'Announcement title (3-255 characters)', type: 'string', example: 'Server Maintenance'),
                    new OA\Property(property: 'content', description: 'Announcement content (10-5000 characters)', type: 'string', example: 'We will be performing scheduled maintenance on our servers...'),
                    new OA\Property(property: 'urgency', description: 'Maintenance urgency level', type: 'string', example: 'HIGH', enum: ['LOW', 'MEDIUM', 'HIGH']),
                    new OA\Property(property: 'scheduled_start', description: 'Scheduled start datetime (ISO 8601, must be after now)', type: 'string', format: 'date-time', example: '2025-11-05T10:00:00Z'),
                    new OA\Property(property: 'scheduled_end', description: 'Scheduled end datetime (ISO 8601, must be after start)', type: 'string', format: 'date-time', example: '2025-11-05T12:00:00Z'),
                    new OA\Property(property: 'is_emergency', description: 'Whether this is an emergency maintenance', type: 'boolean', example: false),
                    new OA\Property(property: 'affected_services', description: 'Array of affected service names (optional, max 20)', type: 'array', example: ['API', 'Dashboard']),
                    new OA\Property(property: 'action', description: 'Action to perform: draft (default), publish, or schedule', type: 'string', example: 'publish'),
                    new OA\Property(property: 'scheduled_for', description: 'When to publish (required if action=schedule, ISO 8601)', type: 'string', format: 'date-time', example: '2025-11-05T09:00:00Z'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Maintenance announcement created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message', type: 'string', example: 'Mantenimiento publicado exitosamente'),
                        new OA\Property(property: 'data', description: 'Created announcement resource', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation error (invalid dates, values, or missing required fields)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'The scheduled_end must be after scheduled_start.'),
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
                description: 'Forbidden (user has no company assigned or insufficient permissions)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Store a new maintenance announcement
     *
     * POST /api/announcements/maintenance
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

    #[OA\Post(
        path: '/announcements/maintenance/{announcement}/start',
        operationId: 'mark_maintenance_start',
        description: 'Records the actual start time of a maintenance window. Can only be called once per maintenance announcement. User must be the COMPANY_ADMIN who owns the announcement.',
        summary: 'Mark maintenance as started',
        tags: ['Announcements - Maintenance'],
        parameters: [
            new OA\Parameter(
                name: 'announcement',
                description: 'Announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Maintenance start recorded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message', type: 'string', example: 'Maintenance start recorded'),
                        new OA\Property(property: 'data', description: 'Updated announcement resource', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request (not a maintenance type, already started, or wrong announcement type)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Maintenance start already marked'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Unauthorized or invalid JWT'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (announcement belongs to different company)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Announcement not found'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Mark maintenance start time
     *
     * POST /api/announcements/maintenance/{announcement}/start
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

    #[OA\Post(
        path: '/announcements/maintenance/{announcement}/complete',
        operationId: 'mark_maintenance_complete',
        description: 'Records the actual end time of a maintenance window. Requires maintenance to have been started first. Validates that end time is after start time. User must be the COMPANY_ADMIN who owns the announcement.',
        summary: 'Mark maintenance as completed',
        tags: ['Announcements - Maintenance'],
        parameters: [
            new OA\Parameter(
                name: 'announcement',
                description: 'Announcement ID (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Maintenance completion recorded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'message', description: 'Success message', type: 'string', example: 'Maintenance completed'),
                        new OA\Property(property: 'data', description: 'Updated announcement resource', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request (not a maintenance type, not started, already completed, or end time before start time)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Mark start first'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Unauthorized or invalid JWT'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (announcement belongs to different company)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Insufficient permissions'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Announcement not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Announcement not found'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Mark maintenance complete
     *
     * POST /api/announcements/maintenance/{announcement}/complete
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
