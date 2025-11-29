<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

/**
 * DeviceInfoParser
 *
 * Helper para extraer y parsear información de dispositivos desde requests HTTP.
 * Utiliza jenssegers/agent para detección robusta de navegadores y dispositivos.
 * Reutilizable en todas las mutations de autenticación (register, login, etc.)
 *
 * @usage
 * ```php
 * $deviceInfo = DeviceInfoParser::fromRequest($request);
 * // ['ip' => '192.168.1.1', 'user_agent' => '...', 'name' => 'Chrome on Windows']
 * ```
 */
class DeviceInfoParser
{
    /**
     * Extrae información del dispositivo desde un request HTTP
     *
     * @param Request|null $request Request de Laravel
     * @return array{ip: string, user_agent: string|null, name: string}
     */
    public static function fromRequest(?Request $request): array
    {
        if (!$request) {
            return self::getDefaultDeviceInfo();
        }

        $rawUserAgent = $request->userAgent();
        $ip = self::getClientIp($request);

        // Normalizar user agent para apps móviles
        $normalizedUserAgent = self::normalizeUserAgent($rawUserAgent);

        // Parseear nombre amigable del dispositivo usando Agent
        $deviceName = self::parseDeviceName($rawUserAgent);

        return [
            'ip' => $ip ?? '127.0.0.1',
            'user_agent' => $normalizedUserAgent,
            'name' => $deviceName,
        ];
    }

    /**
     * Normaliza el user agent para detectar y limpiar apps móviles nativas
     *
     * Convierte user agents técnicos de apps móviles (okhttp, CFNetwork, Dart)
     * a valores legibles y consistentes para mejor visualización y analytics.
     *
     * Ejemplos:
     * - "okhttp/4.12.0" → "Mobile App - Android"
     * - "CFNetwork/1445.104.11 Darwin/22.4.0" → "Mobile App - iOS"
     * - "Dart/3.0" → "Mobile App - Flutter"
     * - "Mozilla/5.0..." (browsers) → mantiene original
     *
     * @param string|null $userAgent User agent raw del cliente
     * @return string|null User agent normalizado o null si está vacío
     */
    private static function normalizeUserAgent(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $userAgent = trim($userAgent);

        if (empty($userAgent)) {
            return null;
        }

        // Detectar React Native / Expo (Android)
        if (str_contains($userAgent, 'okhttp')) {
            return 'Mobile App - Android';
        }

        // Detectar iOS nativo (CFNetwork es lo que usan apps iOS)
        if (str_contains($userAgent, 'CFNetwork')) {
            return 'Mobile App - iOS';
        }

        // Detectar Flutter
        if (str_contains($userAgent, 'Dart')) {
            return 'Mobile App - Flutter';
        }

        // Mantener user agents de browsers tal cual (Chrome, Firefox, Safari, etc.)
        return $userAgent;
    }

    /**
     * Parsea el User-Agent a un nombre de dispositivo amigable
     *
     * Utiliza jenssegers/agent para detección robusta y automática de:
     * - Navegadores (Chrome, Firefox, Safari, Edge, Brave, Vivaldi, Opera, etc.)
     * - Sistemas operativos (Windows, macOS, Linux, iOS, Android)
     * - Tipos de dispositivo (móvil, tablet, escritorio)
     *
     * @param string|null $userAgent User-Agent del navegador
     * @return string Nombre amigable del dispositivo en formato "Browser on OS" o "Device Type"
     */
    public static function parseDeviceName(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        // Detectar bots y crawlers
        if ($agent->isBot()) {
            $browserName = $agent->browser() ?? 'Bot';
            return "{$browserName} (Bot)";
        }

        // Obtener información base
        $browser = $agent->browser() ?? 'Unknown Browser';
        $platform = $agent->platform() ?? 'Unknown OS';

        // Detectar tipo de dispositivo móvil específico
        if ($agent->isPhone()) {
            // Para dispositivos específicos, usar marca
            if (str_contains($userAgent, 'iPhone')) {
                return 'iPhone';
            }
            if (str_contains($userAgent, 'Android')) {
                return 'Android Phone';
            }
            return "{$browser} Phone";
        }

        if ($agent->isTablet()) {
            // Para tablets específicas
            if (str_contains($userAgent, 'iPad')) {
                return 'iPad';
            }
            if (str_contains($userAgent, 'Android')) {
                return 'Android Tablet';
            }
            return "{$browser} Tablet";
        }

        // Dispositivos de escritorio
        if ($agent->isDesktop()) {
            return "{$browser} on {$platform}";
        }

        // Fallback general
        return "{$browser} on {$platform}";
    }

    /**
     * Detecta si el dispositivo es móvil (phone o tablet)
     *
     * Utiliza jenssegers/agent para detección precisa y confiable.
     *
     * @param string|null $userAgent User-Agent del navegador
     * @return bool True si es un dispositivo móvil
     */
    public static function isMobile(?string $userAgent): bool
    {
        if (!$userAgent) {
            return false;
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return $agent->isPhone() || $agent->isTablet();
    }

    /**
     * Detecta el navegador principal
     *
     * Utiliza jenssegers/agent para detección robusta y precisa.
     * Resuelve automáticamente conflictos como Edge vs Chrome (Edge usa Chromium).
     *
     * Navegadores soportados:
     * - Chrome, Firefox, Safari, Edge, Brave, Vivaldi, Opera, IE, etc.
     * - Mobile browsers: Chrome Mobile, Firefox Mobile, Safari (iOS), etc.
     *
     * @param string|null $userAgent User-Agent del navegador
     * @return string Nombre del navegador o 'Unknown'
     */
    public static function getBrowser(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return $agent->browser() ?? 'Unknown';
    }

    /**
     * Detecta el sistema operativo
     *
     * Utiliza jenssegers/agent para detección precisa y confiable.
     *
     * Sistemas operativos soportados:
     * - Desktop: Windows, macOS, Linux, Chrome OS
     * - Mobile: iOS, Android, Windows Phone, BlackBerry
     * - Other: FreeBSD, OpenBSD, SunOS, etc.
     *
     * @param string|null $userAgent User-Agent del navegador
     * @return string Nombre del OS o 'Unknown'
     */
    public static function getOS(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return $agent->platform() ?? 'Unknown';
    }

    /**
     * Obtiene la IP real del cliente considerando proxies y load balancers
     *
     * Intenta obtener de X-Forwarded-For primero (la IP más a la izquierda es el cliente real),
     * luego CF-Connecting-IP (Cloudflare), luego X-Real-IP, finalmente $request->ip() como fallback.
     *
     * @param Request $request Request de Laravel
     * @return string|null IP del cliente
     */
    private static function getClientIp(Request $request): ?string
    {
        // Try X-Forwarded-For (most common with proxies like GCP CLB, AWS ALB, nginx)
        // Format: client, proxy1, proxy2, ...
        if ($request->header('X-Forwarded-For')) {
            $ips = array_map('trim', explode(',', $request->header('X-Forwarded-For')));
            if (!empty($ips[0]) && filter_var($ips[0], FILTER_VALIDATE_IP)) {
                return $ips[0];
            }
        }

        // Try Cloudflare
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        // Try X-Real-IP (nginx, some proxies)
        if ($request->header('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }

        // Fallback to standard method (direct connection)
        return $request->ip();
    }

    /**
     * Retorna información de dispositivo por defecto
     * Usado cuando no hay request disponible (tests, CLI, etc.)
     *
     * @return array{ip_address: string, user_agent: string|null, device_name: string}
     */
    private static function getDefaultDeviceInfo(): array
    {
        return [
            'ip' => '127.0.0.1',
            'user_agent' => null,
            'name' => 'Unknown Device',
        ];
    }
}
