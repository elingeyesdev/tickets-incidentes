<?php

declare(strict_types=1);

namespace App\Features\Analytics\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Features\Analytics\Services\RealtimeTrafficService;

/**
 * TrackApiRequest Middleware
 * 
 * Records every API request for real-time traffic monitoring.
 * This middleware should be applied globally to all API routes.
 * 
 * The tracking is non-blocking and failures are silently logged
 * to avoid impacting the actual API response.
 */
class TrackApiRequest
{
    public function __construct(
        private RealtimeTrafficService $trafficService
    ) {}

    /**
     * Routes to exclude from tracking (to prevent self-counting)
     */
    private const EXCLUDED_ROUTES = [
        'api/analytics/realtime-traffic',
        'api/health',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tracking for excluded routes (like the traffic chart polling endpoint)
        $path = $request->path();
        foreach (self::EXCLUDED_ROUTES as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return $next($request);
            }
        }

        // Record the request BEFORE processing
        // This ensures we count all requests, even ones that fail
        $this->trafficService->recordRequest();

        return $next($request);
    }
}
