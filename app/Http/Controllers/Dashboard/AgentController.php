<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use Illuminate\View\View;

/**
 * Agent Dashboard Controller
 *
 * Handles dashboard view for AGENT role.
 * Shows agent-specific ticket queue and performance metrics.
 */
class AgentController extends Controller
{
    /**
     * Display the agent dashboard.
     *
     * @return View
     */
    public function dashboard(): View
    {
        // Get authenticated user from JWT
        $user = JWTHelper::getAuthenticatedUser();

        // Get company ID from JWT if available
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');

        // In a real application, you would fetch agent-specific statistics
        // For now, we'll pass mock data to demonstrate the dashboard structure

        return view('app.agent.dashboard', [
            'user' => $user,
            'companyId' => $companyId,
            'stats' => [
                'assigned_tickets' => 15,
                'resolved_today' => 8,
                'avg_response_time' => '1.5 hours',
                'satisfaction_rate' => 95,
            ]
        ]);
    }
}
