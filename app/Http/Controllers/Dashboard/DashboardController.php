<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Dashboard Redirect Controller
 *
 * Redirects users to the appropriate dashboard based on their active role from JWT.
 * This controller acts as a central entry point for authenticated users.
 */
class DashboardController extends Controller
{
    /**
     * Redirect to appropriate dashboard based on active role from JWT.
     *
     * The active role is determined from the JWT payload set during role selection.
     * Users must have completed role selection to access dashboards.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function redirect(Request $request): RedirectResponse
    {
        // Get JWT payload from request attributes (set by JWT middleware)
        $payload = $request->attributes->get('jwt_payload');

        if (!$payload) {
            // No JWT payload - redirect to login
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        // Get active role from JWT payload
        // The active role should be set when user selects a role
        $activeRole = $payload['active_role'] ?? null;
        $activeRoleCode = $activeRole['code'] ?? null;

        // If no active role selected, redirect to role selector
        if (!$activeRoleCode) {
            // User has not selected a role yet
            return redirect('/auth-flow/role-selector')
                ->with('info', 'Please select a role to continue.');
        }

        // Redirect based on active role code
        return match($activeRoleCode) {
            'PLATFORM_ADMIN' => redirect()->route('dashboard.platform-admin'),
            'COMPANY_ADMIN' => redirect()->route('dashboard.company-admin'),
            'AGENT' => redirect()->route('dashboard.agent'),
            'USER' => redirect()->route('dashboard.user'),
            default => redirect()->route('login')
                ->with('error', 'Invalid role. Please login again.')
        };
    }
}
