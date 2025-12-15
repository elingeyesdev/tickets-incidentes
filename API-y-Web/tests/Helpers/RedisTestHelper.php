<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Redis Test Helper
 *
 * Utilidades para trabajar con Redis en tests
 */
class RedisTestHelper
{
    /**
     * Verificar si una clave existe en Redis
     */
    public static function exists(string $key): bool
    {
        $redisKey = config('cache.prefix') . $key;
        return Redis::exists($redisKey) > 0;
    }

    /**
     * Obtener valor desde Redis
     */
    public static function get(string $key): mixed
    {
        return Cache::get($key);
    }

    /**
     * Establecer valor en Redis
     */
    public static function put(string $key, mixed $value, \DateTime|int|null $ttl = null): void
    {
        if ($ttl instanceof \DateTime) {
            Cache::until($ttl, $key, $value);
        } else {
            Cache::put($key, $value, $ttl);
        }
    }

    /**
     * Obtener todas las claves que coincidan con un patrón
     */
    public static function keys(string $pattern): array
    {
        $prefix = config('cache.prefix');
        $fullPattern = $prefix . $pattern;
        return Redis::keys($fullPattern);
    }

    /**
     * Contar claves que coincidan con un patrón
     */
    public static function countKeys(string $pattern): int
    {
        return count(self::keys($pattern));
    }

    /**
     * Eliminar clave de Redis
     */
    public static function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Verificar TTL de una clave en segundos
     */
    public static function ttl(string $key): ?int
    {
        $redisKey = config('cache.prefix') . $key;
        $ttl = Redis::ttl($redisKey);
        
        // -1 = sin expiración, -2 = no existe
        return $ttl === -2 ? null : $ttl;
    }

    /**
     * Verificar si una clave va a expirar en los próximos N segundos
     */
    public static function willExpireWithin(string $key, int $seconds): bool
    {
        $ttl = self::ttl($key);
        return $ttl !== null && $ttl > 0 && $ttl <= $seconds;
    }

    /**
     * Obtener información de debug de Redis
     */
    public static function debugInfo(): array
    {
        return [
            'connected' => self::isConnected(),
            'keys_count' => count(Redis::keys(config('cache.prefix') . '*')),
            'memory_info' => Redis::info('memory'),
            'prefix' => config('cache.prefix'),
        ];
    }

    /**
     * Verificar conexión a Redis
     */
    public static function isConnected(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Limpiar todas las claves de pruebas
     */
    public static function flushTestData(string $pattern = '*'): int
    {
        $keys = self::keys($pattern);
        $count = count($keys);
        
        foreach ($keys as $key) {
            Redis::del($key);
        }
        
        return $count;
    }
}
