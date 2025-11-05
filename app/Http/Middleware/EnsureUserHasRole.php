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

        // Check if user has any of the required roles
        // HYBRID APPROACH: Check JWT first (for stateless auth), then DB (for backward compatibility)
        foreach ($roles as $role) {
            // FIRST: Check JWT payload (stateless verification - for Content Management REST API)
            try {
                if (JWTHelper::hasRoleFromJWT($role)) {
                    return $next($request);
                }
            } catch (\Exception $e) {
                // JWT payload not available, fall through to DB check
            }

            // SECOND: Check database (for backward compatibility with other features)
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Insufficient permissions',
            'code' => 'INSUFFICIENT_PERMISSIONS'
        ], 403);
    }
}
