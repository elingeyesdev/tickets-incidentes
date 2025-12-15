<?php

declare(strict_types=1);

namespace App\Features\Authentication\Services;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Log;

/**
 * GeoIP Service
 *
 * Servicio para resolver ubicación geográfica a partir de dirección IP.
 * Utiliza MaxMind GeoLite2 database local.
 *
 * Cache estratégico: 1 año (IPs raramente cambian de ubicación)
 */
final class GeoIPService
{
    private Reader $reader;

    private const CACHE_TTL_DAYS = 365; // 1 año
    private const CACHE_KEY_PREFIX = 'geoip:location:';

    /**
     * Constructor
     *
     * @throws \RuntimeException Si la BD de GeoIP no existe
     */
    public function __construct()
    {
        $dbPath = storage_path('app/geoip/GeoLite2-City.mmdb');

        if (!file_exists($dbPath)) {
            throw new \RuntimeException(
                "GeoIP database not found at {$dbPath}. " .
                "Download from: https://dev.maxmind.com/geoip/geolite2-free-geolocation-data"
            );
        }

        $this->reader = new Reader($dbPath);
    }

    /**
     * Obtener ubicación desde dirección IP
     *
     * Cachea el resultado por 1 año (IPs no cambian de ubicación).
     * Retorna null para IPs privadas o si falla la resolución.
     *
     * @param string|null $ip Dirección IP (IPv4 o IPv6)
     * @return array|null Array con city, country, country_code, latitude, longitude, timezone
     */
    public function getLocationFromIp(?string $ip): ?array
    {
        if (!$ip) {
            return null;
        }

        // Cachear por 1 año - IP → ubicación es estable
        return cache()->remember(
            self::CACHE_KEY_PREFIX . $ip,
            self::CACHE_TTL_DAYS * 24 * 60 * 60,
            fn() => $this->resolveLocation($ip)
        );
    }

    /**
     * Resolver ubicación desde BD GeoIP
     *
     * @param string $ip Dirección IP
     * @return array|null Datos de ubicación o null
     */
    private function resolveLocation(string $ip): ?array
    {
        try {
            // Saltear IPs privadas (localhost, 192.168.x.x, 10.x.x.x, etc)
            if ($this->isPrivateIp($ip)) {
                return null;
            }

            $record = $this->reader->city($ip);

            return [
                'city' => $record->city->name,
                'country' => $record->country->name,
                'country_code' => $record->country->isoCode,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
                'timezone' => $record->location->timeZone,
            ];
        } catch (\Exception $e) {
            Log::debug("GeoIP lookup failed for IP {$ip}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Verificar si la IP es privada
     *
     * @param string $ip Dirección IP
     * @return bool True si es IP privada
     */
    private function isPrivateIp(string $ip): bool
    {
        // filter_var con FILTER_FLAG_NO_PRIV_RANGE retorna false para IPs privadas
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
    }

    /**
     * Limpiar caché de GeoIP
     *
     * Útil para forzar recalculación o cuando se actualiza la BD GeoIP.
     *
     * @param string|null $ip IP específica, o null para limpiar todo
     * @return void
     */
    public function clearCache(?string $ip = null): void
    {
        if ($ip) {
            cache()->forget(self::CACHE_KEY_PREFIX . $ip);
        } else {
            // Limpiar todas las claves de GeoIP
            // Nota: Esto depende del driver de caché
            cache()->tags([self::CACHE_KEY_PREFIX])->flush();
        }
    }
}
