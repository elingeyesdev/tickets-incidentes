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
        \Illuminate\Support\Facades\Log::info('[DASHBOARD] Redirect method called', [
            'url' => $request->fullUrl(),
            'has_jwt_payload' => $request->attributes->has('jwt_payload'),
        ]);
        
        // Get JWT payload from request attributes (set by JWT middleware)
        $payload = $request->attributes->get('jwt_payload');

        if (!$payload) {
            \Illuminate\Support\Facades\Log::warning('[DASHBOARD] No JWT payload found, redirecting to login');
            // No JWT payload - redirect to login
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        \Illuminate\Support\Facades\Log::info('[DASHBOARD] JWT payload found', [
            'payload_keys' => array_keys($payload),
            'has_active_role' => isset($payload['active_role']),
            'has_roles' => isset($payload['roles']),
        ]);

        // Get active role from JWT payload
        // The active role should be set when user selects a role
        $activeRole = $payload['active_role'] ?? null;
        $activeRoleCode = $activeRole['code'] ?? null;

        // If no active role selected, check if user has only one role
        if (!$activeRoleCode) {
            \Illuminate\Support\Facades\Log::info('[DASHBOARD] No active role code, checking available roles');
            
            // Get all available roles from JWT
            $roles = $payload['roles'] ?? [];

            if (empty($roles)) {
                \Illuminate\Support\Facades\Log::warning('[DASHBOARD] User has no roles assigned');
                // User has no roles - redirect to login
                return redirect()->route('login')
                    ->with('error', 'No roles assigned. Please contact administrator.');
            }

            \Illuminate\Support\Facades\Log::info('[DASHBOARD] User has roles', [
                'role_count' => count($roles),
                'roles' => $roles,
            ]);

            // If user has only ONE role, redirect directly to that dashboard
            if (count($roles) === 1) {
                $singleRole = reset($roles); // Get first (and only) role
                $activeRoleCode = $singleRole['code'] ?? null;

                \Illuminate\Support\Facades\Log::info('[DASHBOARD] User has single role', [
                    'role_code' => $activeRoleCode,
                ]);

                if (!$activeRoleCode) {
                    \Illuminate\Support\Facades\Log::warning('[DASHBOARD] Invalid role configuration');
                    return redirect()->route('login')
                        ->with('error', 'Invalid role configuration.');
                }

                // Redirect directly without requiring role selection
                return $this->redirectToDashboard($activeRoleCode);
            }

            \Illuminate\Support\Facades\Log::info('[DASHBOARD] User has multiple roles, redirecting to role selector');
            // User has multiple roles - redirect to role selector
            return redirect('/auth-flow/role-selector')
                ->with('info', 'Please select a role to continue.');
        }

        \Illuminate\Support\Facades\Log::info('[DASHBOARD] Active role is set, redirecting to dashboard', [
            'role_code' => $activeRoleCode,
        ]);

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
