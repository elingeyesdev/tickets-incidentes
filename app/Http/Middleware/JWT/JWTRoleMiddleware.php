<?php

declare(strict_types=1);

namespace App\Http\Middleware\JWT;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JWT Role Middleware
 *
 * Validates that the authenticated user has at least one of the required roles.
 * Must be used after JWTAuthenticationMiddleware.
 *
 * Usage: Route::middleware(['jwt.auth', 'jwt.role:PLATFORM_ADMIN,COMPANY_ADMIN'])
 *
 * @package App\Http\Middleware\JWT
 */
class JWTRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware closure
     * @param string ...$roles Required role codes (at least one must match)
     * @return Response
     * @throws AuthenticationException If user is not authenticated
     * @throws AuthorizationException If user lacks required roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Get authenticated user from request attributes
        $user = $request->attributes->get('jwt_user');

        if (!$user) {
            throw new AuthenticationException(
                'Authentication required. This middleware must run after JWTAuthenticationMiddleware.'
            );
        }

        // Get user's role codes
        $userRoleCodes = $user->roles->pluck('role_code')->toArray();

        // Check if user has at least one of the required roles
        $hasRequiredRole = !empty(array_intersect($userRoleCodes, $roles));

        if (!$hasRequiredRole) {
            throw new AuthorizationException('Insufficient permissions');
        }

        return $next($request);
    }
}
