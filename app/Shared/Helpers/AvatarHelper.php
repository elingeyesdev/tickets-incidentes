<?php

namespace App\Shared\Helpers;

use Illuminate\Support\Facades\Storage;
use Exception;

class AvatarHelper
{
    private const STATE_FILE = 'framework/cache/used_avatars.json';
    private const MAX_SAFE_LIMIT = 500; // Hard stop to prevent infinite disk usage

    /**
     * Get a unique random avatar URL.
     * Auto-expands the pool if exhausted, up to MAX_SAFE_LIMIT.
     */
    public static function getRandom(string $gender = 'male'): string
    {
        // Normalize gender
        $gender = strtolower($gender);
        if (!in_array($gender, ['male', 'female'])) {
            $gender = 'male';
        }

        // 1. Check physical availability
        $physicalCount = self::getPhysicalCount($gender);

        // 2. Load usage state
        $state = self::loadState();
        $usedIndices = $state[$gender] ?? [];

        // 3. Calculate available pool based on PHYSICAL files
        $pool = range(1, $physicalCount);
        $available = array_diff($pool, $usedIndices);

        // 4. If exhausted, try to expand
        if (empty($available)) {
            // Check if we hit the hard limit
            if ($physicalCount >= self::MAX_SAFE_LIMIT) {
                throw new Exception("❌ Avatar pool exhausted (Limit reached: {$physicalCount}). Please reset usage (php artisan avatars:reset) or increase limit.");
            }

            // Expand pool by downloading 50 more
            $batchSize = 50;
            $startIndex = $physicalCount + 1;
            
            // Call artisan command to append new files
            // 'php artisan app:download-avatars 50 --start-index=101'
            \Illuminate\Support\Facades\Artisan::call('app:download-avatars', [
                'count' => $batchSize, 
                '--start-index' => $startIndex
            ]);

            // Update physical count and pool
            $physicalCount += $batchSize;
            $pool = range(1, $physicalCount);
            $available = array_diff($pool, $usedIndices); // Re-calculate availability
        }

        // Double check safety
        if (empty($available)) {
             throw new Exception("❌ Failed to expand avatar pool. API error?");
        }

        // Pick random index
        $index = $available[array_rand($available)];

        // Update state
        $state[$gender][] = $index;
        self::saveState($state);

        $filename = str_pad($index, 3, '0', STR_PAD_LEFT) . '.jpg';
        $folder = $gender === 'male' ? 'men' : 'women';

        return asset("storage/avatars/pool/{$folder}/{$filename}");
    }

    private static function getPhysicalCount(string $gender): int
    {
        $folder = $gender === 'male' ? 'men' : 'women';
        $files = Storage::disk('public')->files("avatars/pool/{$folder}");
        // Filter only .jpg to be safe
        $jpgs = array_filter($files, fn($f) => str_ends_with($f, '.jpg'));
        return count($jpgs);
    }

    /**
     * Reset the usage history.
     */
    public static function reset(): void
    {
        if (file_exists(storage_path(self::STATE_FILE))) {
            unlink(storage_path(self::STATE_FILE));
        }
    }

    private static function loadState(): array
    {
        $path = storage_path(self::STATE_FILE);

        if (!file_exists($path)) {
            return [
                'male' => [],
                'female' => [],
            ];
        }

        $content = file_get_contents($path);
        return json_decode($content, true) ?? ['male' => [], 'female' => []];
    }

    private static function saveState(array $state): void
    {
        // Ensure directory exists
        $path = storage_path(self::STATE_FILE);
        $dir = dirname($path);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT));
    }
}
