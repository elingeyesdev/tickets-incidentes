<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use Illuminate\View\View;

/**
 * Platform Admin Dashboard Controller
 *
 * Handles dashboard view for PLATFORM_ADMIN role.
 * Shows system-wide statistics and management tools.
 */
class PlatformAdminController extends Controller
{
    /**
     * Display the platform admin dashboard.
     *
     * @return View
     */
    public function dashboard(): View
    {
        // Get authenticated user from JWT
        $user = JWTHelper::getAuthenticatedUser();

        // In a real application, you would fetch actual statistics from the database
        // For now, we'll pass mock data to demonstrate the dashboard structure

        return view('app.platform-admin.dashboard', [
            'user' => $user,
            'stats' => [
                'total_users' => 1250,
                'total_companies' => 45,
                'total_tickets' => 3890,
                'pending_requests' => 8,
            ]
        ]);
    }
}
