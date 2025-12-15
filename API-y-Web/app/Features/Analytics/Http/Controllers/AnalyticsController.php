<?php

namespace App\Features\Analytics\Http\Controllers;

use App\Features\Analytics\Services\AnalyticsService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService
    ) {}

    #[OA\Get(
        path: '/api/analytics/company-dashboard',
        operationId: 'get_company_dashboard_stats',
        summary: 'Get company dashboard statistics',
        description: 'Returns comprehensive statistics for the company admin dashboard. Requires COMPANY_ADMIN role.',
        security: [['bearerAuth' => []]],
        tags: ['Analytics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard statistics',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'kpi', type: 'object'),
                        new OA\Property(property: 'ticket_status', type: 'object'),
                        new OA\Property(property: 'recent_tickets', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'team_members', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'performance', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
            )
        ]
    )]
    public function dashboard(): JsonResponse
    {
        // Verificar que el rol activo sea COMPANY_ADMIN
        $activeRole = JWTHelper::getActiveRoleCode();
        if ($activeRole !== 'COMPANY_ADMIN') {
            return response()->json(['message' => 'Active role must be COMPANY_ADMIN.'], 403);
        }

        // Obtener company_id del rol activo
        $companyId = JWTHelper::getActiveCompanyId();
        if (!$companyId) {
            return response()->json(['message' => 'User is not a company admin or has no company assigned.'], 403);
        }

        $stats = $this->analyticsService->getCompanyDashboardStats($companyId);

        return response()->json($stats);
    }

    #[OA\Get(
        path: '/api/analytics/user-dashboard',
        operationId: 'get_user_dashboard_stats',
        summary: 'Get user dashboard statistics',
        description: 'Returns statistics for the user dashboard (Customer/Employee).',
        security: [['bearerAuth' => []]],
        tags: ['Analytics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard statistics',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'kpi', type: 'object'),
                        new OA\Property(property: 'ticket_status', type: 'object'),
                        new OA\Property(property: 'recent_tickets', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'recent_articles', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            )
        ]
    )]
    public function userDashboard(): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();
        $stats = $this->analyticsService->getUserDashboardStats($user->id);

        return response()->json($stats);
    }

    #[OA\Get(
        path: '/api/analytics/agent-dashboard',
        operationId: 'get_agent_dashboard_stats',
        summary: 'Get agent dashboard statistics',
        description: 'Returns statistics for the agent dashboard (Assigned tickets, queue, etc).',
        security: [['bearerAuth' => []]],
        tags: ['Analytics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard statistics',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'kpi', type: 'object'),
                        new OA\Property(property: 'ticket_status', type: 'object'),
                        new OA\Property(property: 'assigned_tickets', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'unassigned_tickets', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'recent_articles', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            )
        ]
    )]
    public function agentDashboard(): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();
        
        // Verificar que el rol activo sea AGENT
        $activeRole = JWTHelper::getActiveRoleCode();
        if ($activeRole !== 'AGENT') {
            return response()->json(['message' => 'Active role must be AGENT.'], 403);
        }

        // Obtener company_id del rol activo
        $companyId = JWTHelper::getActiveCompanyId();
        if (!$companyId) {
            return response()->json(['message' => 'User is not an agent or has no company assigned.'], 403);
        }

        $stats = $this->analyticsService->getAgentDashboardStats($user->id, $companyId);

        return response()->json($stats);
    }

    #[OA\Get(
        path: '/api/analytics/platform-dashboard',
        operationId: 'get_platform_dashboard_stats',
        summary: 'Get platform dashboard statistics',
        description: 'Returns statistics for the platform admin dashboard (Global stats).',
        security: [['bearerAuth' => []]],
        tags: ['Analytics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard statistics',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'kpi', type: 'object'),
                        new OA\Property(property: 'companies_growth', type: 'object'),
                        new OA\Property(property: 'ticket_volume', type: 'object'),
                        new OA\Property(property: 'pending_requests', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'top_companies', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            )
        ]
    )]
    public function platformDashboard(): JsonResponse
    {
        // Verificar que el rol activo sea PLATFORM_ADMIN
        $activeRole = JWTHelper::getActiveRoleCode();
        if ($activeRole !== 'PLATFORM_ADMIN') {
             return response()->json(['message' => 'Active role must be PLATFORM_ADMIN.'], 403);
        }

        $stats = $this->analyticsService->getPlatformDashboardStats();

        return response()->json($stats);
    }

    #[OA\Get(
        path: '/api/admin/companies/{companyId}/stats',
        operationId: 'get_company_full_stats',
        summary: 'Get comprehensive company statistics',
        description: 'Returns all statistics for a specific company including users, tickets, announcements, articles, areas, and categories. Requires PLATFORM_ADMIN role.',
        security: [['bearerAuth' => []]],
        tags: ['Analytics'],
        parameters: [
            new OA\Parameter(
                name: 'companyId',
                description: 'Company UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Company statistics retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'users', type: 'object', description: 'User statistics'),
                                new OA\Property(property: 'tickets', type: 'object', description: 'Ticket statistics by status and priority'),
                                new OA\Property(property: 'announcements', type: 'object', description: 'Announcement statistics by type and status'),
                                new OA\Property(property: 'articles', type: 'object', description: 'Help center article statistics'),
                                new OA\Property(property: 'areas', type: 'object', description: 'Area statistics'),
                                new OA\Property(property: 'categories', type: 'object', description: 'Category statistics'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - requires PLATFORM_ADMIN role',
            ),
            new OA\Response(
                response: 404,
                description: 'Company not found',
            )
        ]
    )]
    public function companyStats(string $companyId): JsonResponse
    {
        // Verificar que el rol activo sea PLATFORM_ADMIN
        $activeRole = JWTHelper::getActiveRoleCode();
        if ($activeRole !== 'PLATFORM_ADMIN') {
            return response()->json(['message' => 'Active role must be PLATFORM_ADMIN.'], 403);
        }

        // Verify company exists
        $company = \App\Features\CompanyManagement\Models\Company::find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }

        $stats = $this->analyticsService->getCompanyFullStats($companyId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
