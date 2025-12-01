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
        $user = JWTHelper::getAuthenticatedUser();
        
        // Ensure user is COMPANY_ADMIN and get their company ID
        $companyId = $user->activeRoles()
            ->where('role_code', 'COMPANY_ADMIN')
            ->value('company_id');

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
        
        // Ensure user is AGENT and get their company ID
        $companyId = $user->activeRoles()
            ->where('role_code', 'AGENT')
            ->value('company_id');

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
        // Ensure user is PLATFORM_ADMIN
        $user = JWTHelper::getAuthenticatedUser();
        if (!$user->hasRole('PLATFORM_ADMIN')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = $this->analyticsService->getPlatformDashboardStats();

        return response()->json($stats);
    }
}
