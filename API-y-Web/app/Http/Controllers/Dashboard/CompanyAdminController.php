<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use Illuminate\View\View;

/**
 * Company Admin Dashboard Controller
 *
 * Handles dashboard view for COMPANY_ADMIN role.
 * Shows company-level statistics and team management tools.
 */
class CompanyAdminController extends Controller
{
    /**
     * Display the company admin dashboard.
     *
     * @return View
     */
    public function dashboard(): View
    {
        // Get authenticated user from JWT
        $user = JWTHelper::getAuthenticatedUser();

        // Get company ID from active role in JWT
        $companyId = JWTHelper::getActiveCompanyId();

        // In a real application, you would fetch company-specific statistics
        // For now, we'll pass mock data to demonstrate the dashboard structure

        return view('app.company-admin.dashboard', [
            'user' => $user,
            'companyId' => $companyId,
            'stats' => [
                'total_agents' => 12,
                'online_agents' => 8,
                'open_tickets' => 45,
                'resolved_today' => 23,
                'avg_response_time' => '2.5 hours',
            ]
        ]);
    }
}
