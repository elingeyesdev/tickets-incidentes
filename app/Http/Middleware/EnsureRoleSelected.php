<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure user has selected an active role
 *
 * This middleware checks if the JWT token contains an 'active_role' claim.
 * If not, it redirects the user to the role selector page.
 *
 * Usage in routes:
 * Route::middleware(['jwt.require', 'role.selected'])->group(...);
 */
class EnsureRoleSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get JWT payload from request attributes (set by JWT middleware)
        $payload = $request->attributes->get('jwt_payload');

        if (!$payload) {
            // No JWT payload - should not happen if jwt.require middleware is used
            return redirect()->route('login')
                ->with('error', 'Authentication required');
        }

        // Check if active_role exists in JWT payload
        $activeRole = $payload['active_role'] ?? null;

        if (!$activeRole || !isset($activeRole['code'])) {
            // No active role selected - redirect to role selector
            return redirect('/auth-flow/role-selector')
                ->with('info', 'Please select a role to continue');
        }

        // Active role is present - allow request to proceed
        return $next($request);
    }
}
