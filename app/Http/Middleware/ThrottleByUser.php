<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Shared\Helpers\JWTHelper;

/**
 * Throttle requests by authenticated user ID
 *
 * This middleware provides rate limiting based on the authenticated user's ID,
 * not by IP address or route parameters. Must run AFTER JWT authentication middleware.
 *
 * Usage:
 * Route::middleware(['jwt.require', 'throttle.user:100,60'])->group(function () { ... });
 */
class ThrottleByUser
{
    public function __construct(
        private readonly RateLimiter $limiter
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  int  $maxAttempts  Maximum number of requests
     * @param  int  $decayMinutes  Time window in minutes
     * @param  string|null  $prefix  Optional prefix for the rate limit key
     * @return Response
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1, ?string $prefix = null): Response
    {
        try {
            // Get authenticated user ID (requires JWT middleware to have run first)
            $userId = JWTHelper::getUserId();
            $key = ($prefix ?? 'throttle') . ':user:' . $userId;
        } catch (\Exception $e) {
            // Fallback to IP-based throttling if user is not authenticated
            $key = ($prefix ?? 'throttle') . ':ip:' . $request->ip();
        }

        // Check if rate limit is exceeded
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        // Increment the counter
        $this->limiter->hit($key, $decayMinutes * 60);

        // Add rate limit headers to response
        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Create a 'too many attempts' response.
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'retry_after_seconds' => $retryAfter,
        ], 429, [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->retriesLeft($key, $maxAttempts);
    }

    /**
     * Add rate limit headers to the response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }
}
