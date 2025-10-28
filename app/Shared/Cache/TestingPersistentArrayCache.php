<?php declare(strict_types=1);

namespace App\Shared\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Store;

/**
 * Testing Persistent Array Cache
 *
 * Cache store que persiste entre HTTP requests en testing,
 * pero respeta travelTo() del time mocking.
 *
 * Usa una variable estática para almacenar datos entre requests.
 */
class TestingPersistentArrayCache extends ArrayStore implements Store
{
    /**
     * Variable estática compartida entre todas las instancias
     * y entre todas las requests dentro del mismo test
     */
    private static array $persistentStore = [];

    /**
     * Get an item from the cache.
     */
    public function get($key): mixed
    {
        if (!isset(self::$persistentStore[$key])) {
            return null;
        }

        $item = self::$persistentStore[$key];

        // Revisar expiración basada en travelTo() - usa microtime relativo
        if ($item['expiration'] !== null && microtime(true) >= $item['expiration']) {
            $this->forget($key);
            return null;
        }

        return $item['value'];
    }

    /**
     * Put an item in the cache.
     */
    public function put($key, $value, $seconds): bool
    {
        // Calcular la expiración basada en el tiempo actual (que respeta travelTo())
        // Usar microtime() que es relativo al tiempo del sistema/mocking
        $expiration = $seconds === null
            ? null
            : microtime(true) + ($seconds * 1000);

        self::$persistentStore[$key] = [
            'value' => $value,
            'expiration' => $expiration,
        ];

        return true;
    }

    /**
     * Increment an item's value.
     */
    public function increment($key, $value = 1): int|bool
    {
        $current = $this->get($key) ?? 0;
        $new = $current + $value;
        $this->put($key, $new, PHP_INT_MAX);
        return $new;
    }

    /**
     * Decrement an item's value.
     */
    public function decrement($key, $value = 1): int|bool
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Store multiple items in the cache.
     */
    public function many(array $values): bool
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, PHP_INT_MAX);
        }
        return true;
    }

    /**
     * Store an item in the cache if the key does not exist.
     */
    public function add($key, $value, $seconds): bool
    {
        if ($this->has($key)) {
            return false;
        }
        return $this->put($key, $value, $seconds);
    }

    /**
     * Remove an item from the cache.
     */
    public function forget($key): bool
    {
        unset(self::$persistentStore[$key]);
        return true;
    }

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool
    {
        self::$persistentStore = [];
        return true;
    }

    /**
     * Check if an item exists in the cache.
     */
    public function has($key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get the cache key prefix.
     */
    public function getPrefix(): string
    {
        return '';
    }
}
