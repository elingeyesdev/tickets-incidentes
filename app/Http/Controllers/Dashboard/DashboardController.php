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
     * If user has only one role, redirects directly to that dashboard without requiring selection.
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

        // If no active role selected, check if user has only one role
        if (!$activeRoleCode) {
            // Get all available roles from JWT
            $roles = $payload['roles'] ?? [];

            if (empty($roles)) {
                // User has no roles - redirect to login
                return redirect()->route('login')
                    ->with('error', 'No roles assigned. Please contact administrator.');
            }

            // If user has only ONE role, redirect directly to that dashboard
            if (count($roles) === 1) {
                $singleRole = reset($roles); // Get first (and only) role
                $activeRoleCode = $singleRole['code'] ?? null;

                if (!$activeRoleCode) {
                    return redirect()->route('login')
                        ->with('error', 'Invalid role configuration.');
                }

                // Redirect directly without requiring role selection
                return $this->redirectToDashboard($activeRoleCode);
            }

            // User has multiple roles - redirect to role selector
            return redirect('/auth-flow/role-selector')
                ->with('info', 'Please select a role to continue.');
        }

        // Active role is set - redirect to appropriate dashboard
        return $this->redirectToDashboard($activeRoleCode);
    }

    /**
     * Redirect to dashboard based on role code
     *
     * @param string $roleCode
     * @return RedirectResponse
     */
    private function redirectToDashboard(string $roleCode): RedirectResponse
    {
        return match($roleCode) {
            'PLATFORM_ADMIN' => redirect()->route('dashboard.platform-admin'),
            'COMPANY_ADMIN' => redirect()->route('dashboard.company-admin'),
            'AGENT' => redirect()->route('dashboard.agent'),
            'USER' => redirect()->route('dashboard.user'),
            default => redirect()->route('login')
                ->with('error', 'Invalid role. Please login again.')
        };
    }
}
