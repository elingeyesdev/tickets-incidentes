<?php

declare(strict_types=1);

namespace App\Http\Middleware\JWT;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JWT Guest Middleware
 *
 * Ensures the user is NOT authenticated.
 * Redirects authenticated users to appropriate dashboard based on their primary role.
 *
 * Used for login, register, and other guest-only routes.
 *
 * @package App\Http\Middleware\JWT
 */
class JWTGuestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware closure
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to get authenticated user from request attributes
        $user = $request->attributes->get('jwt_user');

        // If user exists, they are authenticated - redirect them
        if ($user) {
            $redirectPath = $this->getRedirectPath($user);

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Already authenticated',
                    'redirect' => $redirectPath,
                ], 403);
            }

            // Redirect for web requests
            return redirect($redirectPath);
        }

        // User is not authenticated, continue to next middleware
        return $next($request);
    }

    /**
     * Determine the redirect path based on user's primary role.
     *
     * @param mixed $user The authenticated user
     * @return string The redirect path
     */
    private function getRedirectPath($user): string
    {
        // Ensure roles are loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Get all role codes
        $roleCodes = $user->roles->pluck('role_code')->toArray();

        // Redirect based on primary role (priority order)
        if (in_array('PLATFORM_ADMIN', $roleCodes)) {
            return '/admin/dashboard';
        }

        if (in_array('COMPANY_ADMIN', $roleCodes)) {
            return '/empresa/dashboard';
        }

        if (in_array('AGENT', $roleCodes)) {
            return '/agent/dashboard';
        }

        if (in_array('USER', $roleCodes)) {
            return '/tickets';
        }

        // No recognized role - redirect to role selector
        return '/role-selector';
    }
}
