<?php

namespace App\Http\Middleware;

use App\Shared\Helpers\JWTHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure authenticated user has required role(s)
 *
 * Usage in routes:
 * Route::middleware(['role:PLATFORM_ADMIN,COMPANY_ADMIN'])->group(...);
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Role codes required (USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = JWTHelper::getAuthenticatedUser();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if user's ACTIVE role matches any of the required roles
        // Uses the active_role system for proper role-based access control
        
        // First, check if JWT has an explicit active_role
        $payload = $request->attributes->get('jwt_payload');
        $hasExplicitActiveRole = $payload && isset($payload['active_role']);

        foreach ($roles as $role) {
            if ($hasExplicitActiveRole) {
                // STRICT MODE: When active_role is present, ONLY check the active role
                // This ensures users can only access resources for their currently selected role
                try {
                    if (JWTHelper::isActiveRole($role)) {
                        return $next($request);
                    }
                } catch (\Exception $e) {
                    // Continue to next role
                }
            } else {
                // FALLBACK MODE: No active_role in JWT (backward compatibility)
                // Check if user HAS the role
                try {
                    if (JWTHelper::hasRoleFromJWT($role)) {
                        return $next($request);
                    }
                } catch (\Exception $e) {
                    // JWT payload not available, fall through to DB check
                }

                // LEGACY: Check database (for backward compatibility with non-JWT auth)
                if ($user->hasRole($role)) {
                    return $next($request);
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'code' => 'INSUFFICIENT_PERMISSIONS'
            ], 403);
        }

        return redirect()->route('dashboard')
            ->with('warning', 'No tienes permisos para acceder a esa sección.');
    }
}
