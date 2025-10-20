<?php

declare(strict_types=1);

namespace App\Http\Middleware\JWT;

use App\Shared\Helpers\JWTHelper;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JWT Onboarding Middleware
 *
 * Ensures the authenticated user has completed onboarding.
 * Redirects to onboarding page if not completed.
 *
 * @package App\Http\Middleware\JWT
 */
class JWTOnboardingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware closure
     * @return Response
     * @throws AuthenticationException If user is not authenticated
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get authenticated user
        $user = JWTHelper::getAuthenticatedUser();

        // Check if onboarding is completed
        if ($user->onboarding_completed_at === null) {
            // Determine if this is an API request or web request
            if ($request->expectsJson()) {
                // API response
                return response()->json([
                    'message' => 'Onboarding not completed',
                    'redirect' => '/onboarding',
                ], 403);
            }

            // Web response - redirect to onboarding
            return redirect()->route('onboarding.index')
                ->with('message', 'Please complete your onboarding first');
        }

        return $next($request);
    }
}
