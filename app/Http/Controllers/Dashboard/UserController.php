<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use Illuminate\View\View;

/**
 * User Dashboard Controller
 *
 * Handles dashboard view for USER role.
 * Shows user's tickets and support options.
 */
class UserController extends Controller
{
    /**
     * Display the user dashboard.
     *
     * @return View
     */
    public function dashboard(): View
    {
        // Get authenticated user from JWT
        $user = JWTHelper::getAuthenticatedUser();

        // In a real application, you would fetch user-specific ticket statistics
        // For now, we'll pass mock data to demonstrate the dashboard structure

        return view('app.user.dashboard', [
            'user' => $user,
            'stats' => [
                'open_tickets' => 3,
                'in_progress_tickets' => 2,
                'closed_tickets' => 12,
            ]
        ]);
    }
}
