<?php

namespace App\Shared\GraphQL\Queries;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthQuery
{
    public function __invoke(): array
    {
        $health = [];

        $timestamp = now();

        // PostgreSQL Health Check
        try {
            DB::connection()->getPdo();
            $health[] = [
                'service' => 'PostgreSQL',
                'status' => 'healthy',
                'details' => 'Database connection successful',
                'timestamp' => $timestamp
            ];
        } catch (\Exception $e) {
            $health[] = [
                'service' => 'PostgreSQL',
                'status' => 'unhealthy',
                'details' => 'Database connection failed: ' . $e->getMessage(),
                'timestamp' => $timestamp
            ];
        }

        // Redis Health Check
        try {
            Redis::ping();
            $health[] = [
                'service' => 'Redis',
                'status' => 'healthy',
                'details' => 'Redis connection successful',
                'timestamp' => $timestamp
            ];
        } catch (\Exception $e) {
            $health[] = [
                'service' => 'Redis',
                'status' => 'unhealthy',
                'details' => 'Redis connection failed: ' . $e->getMessage(),
                'timestamp' => $timestamp
            ];
        }

        // Laravel Application Health Check
        $health[] = [
            'service' => 'Laravel',
            'status' => 'healthy',
            'details' => 'Application is running',
            'timestamp' => $timestamp
        ];

        return $health;
    }
}
