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
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Insufficient permissions',
            'code' => 'INSUFFICIENT_PERMISSIONS'
        ], 403);
    }
}
