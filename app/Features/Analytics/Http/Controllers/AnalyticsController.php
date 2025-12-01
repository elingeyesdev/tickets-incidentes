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
}
