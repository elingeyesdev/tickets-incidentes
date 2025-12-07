<?php

declare(strict_types=1);

namespace App\Features\Analytics\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * RealtimeTrafficService
 * 
 * Manages real-time API request tracking using Redis.
 * Stores request counts per second for the last 2 minutes.
 * 
 * Redis Structure:
 * - Key: helpdesk:api_traffic (Sorted Set)
 * - Score: Unix timestamp in seconds
 * - Member: "ts:{timestamp}" with value being the count
 * 
 * Actually we use a simpler approach:
 * - Key pattern: helpdesk:api_traffic:{timestamp_second}
 * - Value: Request count for that second
 * - TTL: 130 seconds (2 min + buffer)
 */
class RealtimeTrafficService
{
    private const KEY_PREFIX = 'helpdesk:api_traffic:';
    private const HISTORY_SECONDS = 120; // 2 minutes of history
    private const DISPLAY_SECONDS = 60;  // 1 minute displayed in chart
    private const TTL_SECONDS = 130;     // TTL with buffer

    /**
     * Increment the request counter for the current second.
     * Called by the TrackApiRequest middleware.
     */
    public function recordRequest(): void
    {
        try {
            $timestamp = (int) floor(microtime(true));
            $key = self::KEY_PREFIX . $timestamp;

            // Use Redis INCR for atomic increment
            Redis::incr($key);
            
            // Set TTL only if this is a new key (first request of this second)
            // We use EXPIRE which resets TTL, but that's fine for our use case
            Redis::expire($key, self::TTL_SECONDS);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::warning('[RealtimeTraffic] Failed to record request', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get traffic history for the last N seconds.
     * Returns an array of [timestamp, count] pairs suitable for Flot charts.
     * 
     * @param int $seconds Number of seconds of history to retrieve (default: 60)
     * @return array Array of data points for the chart
     */
    public function getTrafficHistory(int $seconds = self::DISPLAY_SECONDS): array
    {
        try {
            $now = (int) floor(microtime(true));
            $startTime = $now - $seconds;
            
            $data = [];
            $keys = [];
            
            // Build list of keys to fetch
            for ($ts = $startTime; $ts <= $now; $ts++) {
                $keys[$ts] = self::KEY_PREFIX . $ts;
            }
            
            // Use MGET for efficient bulk retrieval
            $values = Redis::mget(array_values($keys));
            
            // Map results back to timestamps
            $timestamps = array_keys($keys);
            foreach ($values as $index => $value) {
                $ts = $timestamps[$index];
                // Convert timestamp to milliseconds for JavaScript
                // Flot expects [x, y] pairs where x is timestamp in ms
                $data[] = [
                    $ts * 1000, // Convert to milliseconds
                    (int) ($value ?? 0)
                ];
            }
            
            return $data;
        } catch (\Exception $e) {
            Log::error('[RealtimeTraffic] Failed to get traffic history', [
                'error' => $e->getMessage()
            ]);
            
            // Return empty data on error
            return [];
        }
    }

    /**
     * Get current requests per second (last second).
     * 
     * @return int Current RPS
     */
    public function getCurrentRPS(): int
    {
        try {
            $timestamp = (int) floor(microtime(true)) - 1; // Previous second (completed)
            $key = self::KEY_PREFIX . $timestamp;
            
            return (int) (Redis::get($key) ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get summary statistics for the traffic.
     * 
     * @return array Statistics including avg, max, total
     */
    public function getStatistics(): array
    {
        $history = $this->getTrafficHistory();
        
        if (empty($history)) {
            return [
                'current_rps' => 0,
                'avg_rps' => 0,
                'max_rps' => 0,
                'total_requests' => 0,
                'period_seconds' => self::DISPLAY_SECONDS,
            ];
        }
        
        $counts = array_column($history, 1);
        $total = array_sum($counts);
        $nonZeroCounts = array_filter($counts, fn($c) => $c > 0);
        
        return [
            'current_rps' => $this->getCurrentRPS(),
            'avg_rps' => count($nonZeroCounts) > 0 
                ? round($total / count($nonZeroCounts), 2) 
                : 0,
            'max_rps' => max($counts),
            'total_requests' => $total,
            'period_seconds' => self::DISPLAY_SECONDS,
        ];
    }
}
