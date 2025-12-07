<?php

declare(strict_types=1);

namespace App\Features\Analytics\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Features\Analytics\Services\RealtimeTrafficService;

/**
 * RealtimeTrafficController
 * 
 * Provides endpoints for real-time API traffic monitoring.
 * Used by the Platform Admin dashboard to display the live chart.
 */
class RealtimeTrafficController extends Controller
{
    public function __construct(
        private RealtimeTrafficService $trafficService
    ) {}

    /**
     * Get real-time traffic data for the chart.
     * 
     * Returns the last 60 seconds of traffic data plus statistics.
     * The frontend polls this endpoint every second.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $history = $this->trafficService->getTrafficHistory();
        $stats = $this->trafficService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => [
                'history' => $history,
                'stats' => $stats,
                'server_time' => now()->toIso8601String(),
                'server_timestamp' => (int) (microtime(true) * 1000), // JS timestamp
            ],
        ]);
    }

    /**
     * Get just the latest data point (for efficient polling).
     * 
     * Returns only the current RPS without full history.
     * Useful for lightweight updates after initial load.
     * 
     * @return JsonResponse
     */
    public function latest(): JsonResponse
    {
        $now = (int) floor(microtime(true));
        $currentRps = $this->trafficService->getCurrentRPS();

        return response()->json([
            'success' => true,
            'data' => [
                'timestamp' => $now * 1000, // JS timestamp
                'rps' => $currentRps,
            ],
        ]);
    }
}
