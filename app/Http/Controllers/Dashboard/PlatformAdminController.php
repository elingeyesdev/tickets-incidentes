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
 *
 * NOTE: Data is loaded via AJAX from frontend, not from backend.
 * This keeps the architecture clean and follows SPA patterns.
 */
class PlatformAdminController extends Controller
{
    /**
     * Display the platform admin dashboard.
     *
     * All data is loaded dynamically via AJAX from the view.
     * The controller simply renders the view with the authenticated user.
     *
     * @return View
     */
    public function dashboard(): View
    {
        // Middleware already validated JWT and role (jwt.require + role:PLATFORM_ADMIN)
        // No need to fetch user from backend - JWT defines everything
        return view('app.platform-admin.dashboard');
    }
}
