<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;
use App\Shared\Helpers\JWTHelper;


/**
 * Announcement Schema Controller
 *
 * Handles schema/metadata structure requests for announcements.
 * Returns the metadata schema structure for each announcement type.
 * Only COMPANY_ADMIN and PLATFORM_ADMIN have access to this endpoint.
 *
 * Feature: Content Management/
 * Base URL: /api/announcements/schemas
 */
class AnnouncementSchemaController extends Controller
{
    #[OA\Get(
        path: '/api/announcements/schemas',
        operationId: 'get_announcement_schemas',
        description: 'Returns the metadata schema structure for each announcement type. Only COMPANY_ADMIN and PLATFORM_ADMIN can access this endpoint. Used by frontend to dynamically build forms.',
        summary: 'Get announcement type schemas',
        tags: ['Announcements'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Schemas for all announcement types (MAINTENANCE, INCIDENT, NEWS, ALERT)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', description: 'Success indicator', type: 'boolean', example: true),
                        new OA\Property(property: 'data', description: 'Schema definitions keyed by announcement type', type: 'object'),
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
                description: 'Forbidden (insufficient permissions - requires COMPANY_ADMIN or PLATFORM_ADMIN)',
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
     * Get announcement schemas for all types.
     *
     * Only COMPANY_ADMIN and PLATFORM_ADMIN can access schemas.
     * Returns metadata schema structure for each announcement type.
     *
     * Route: GET /api/announcements/schemas
     *
     * @return JsonResponse Schema data for all announcement types
     */
    public function __invoke(): JsonResponse
    {
        try {
            $hasAccess = JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')
                || JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN');

            if (!$hasAccess) {
                return response()->json([
                    'message' => 'Insufficient permissions',
                ], 403);
            }
        } catch (\Exception) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        $schemas = [
            'MAINTENANCE' => [
                'required' => [
                    'urgency',
                    'scheduled_start',
                    'scheduled_end',
                    'is_emergency',
                ],
                'optional' => [
                    'actual_start',
                    'actual_end',
                    'affected_services',
                ],
                'fields' => [
                    'urgency' => ['type' => 'enum', 'values' => ['LOW', 'MEDIUM', 'HIGH']],
                    'scheduled_start' => ['type' => 'datetime'],
                    'scheduled_end' => ['type' => 'datetime'],
                    'is_emergency' => ['type' => 'boolean'],
                    'actual_start' => ['type' => 'datetime', 'nullable' => true],
                    'actual_end' => ['type' => 'datetime', 'nullable' => true],
                    'affected_services' => ['type' => 'array'],
                ],
            ],
            'INCIDENT' => [
                'required' => [
                    'urgency',
                    'is_resolved',
                    'started_at',
                ],
                'optional' => [
                    'ended_at',
                    'resolution_content',
                    'affected_services',
                ],
                'fields' => [
                    'urgency' => ['type' => 'enum', 'values' => ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']],
                    'is_resolved' => ['type' => 'boolean'],
                    'started_at' => ['type' => 'datetime'],
                    'ended_at' => ['type' => 'datetime', 'nullable' => true],
                    'resolution_content' => ['type' => 'string'],
                    'affected_services' => ['type' => 'array'],
                ],
            ],
            'NEWS' => [
                'required' => [
                    'news_type',
                    'target_audience',
                    'summary',
                ],
                'optional' => [
                    'call_to_action',
                ],
                'fields' => [
                    'news_type' => ['type' => 'enum', 'values' => ['feature_release', 'policy_update', 'general_update']],
                    'target_audience' => ['type' => 'array', 'values' => ['users', 'agents', 'admins']],
                    'summary' => ['type' => 'string'],
                    'call_to_action' => ['type' => 'object', 'nullable' => true],
                ],
            ],
            'ALERT' => [
                'required' => [
                    'urgency',
                    'alert_type',
                    'message',
                    'action_required',
                    'started_at',
                ],
                'optional' => [
                    'action_description',
                    'ended_at',
                    'affected_services',
                ],
                'fields' => [
                    'urgency' => ['type' => 'enum', 'values' => ['HIGH', 'CRITICAL']],
                    'alert_type' => ['type' => 'enum', 'values' => ['security', 'system', 'service', 'compliance']],
                    'message' => ['type' => 'string'],
                    'action_required' => ['type' => 'boolean'],
                    'action_description' => ['type' => 'string', 'nullable' => true],
                    'started_at' => ['type' => 'datetime'],
                    'ended_at' => ['type' => 'datetime', 'nullable' => true],
                    'affected_services' => ['type' => 'array', 'nullable' => true],
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $schemas,
        ], 200);
    }
}
