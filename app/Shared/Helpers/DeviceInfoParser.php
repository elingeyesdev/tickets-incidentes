<?php

namespace App\Shared\Helpers;

use Illuminate\Http\Request;

/**
 * DeviceInfoParser
 *
 * Helper para extraer y parsear información de dispositivos desde requests HTTP.
 * Reutilizable en todas las mutations de autenticación (register, login, etc.)
 *
 * @usage
 * ```php
 * $deviceInfo = DeviceInfoParser::fromRequest($request);
 * // ['ip_address' => '192.168.1.1', 'user_agent' => '...', 'device_name' => 'Chrome on Windows']
 * ```
 */
class DeviceInfoParser
{
    /**
     * Extrae información del dispositivo desde un request HTTP
     *
     * @param Request|null $request Request de Laravel
     * @return array{ip_address: string, user_agent: string|null, device_name: string}
     */
    public static function fromRequest(?Request $request): array
    {
        if (!$request) {
            return self::getDefaultDeviceInfo();
        }

        $userAgent = $request->userAgent();

        return [
            'ip' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $userAgent,
            'name' => self::parseDeviceName($userAgent),
        ];
    }

    /**
     * Parsea el User-Agent a un nombre de dispositivo amigable
     *
     * @param string|null $userAgent User-Agent del navegador
     * @return string Nombre amigable del dispositivo
     */
    public static function parseDeviceName(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        // Mobile devices
        if (str_contains($userAgent, 'iPhone')) {
            return 'iPhone';
        }

        if (str_contains($userAgent, 'iPad')) {
            return 'iPad';
        }

        if (str_contains($userAgent, 'Android') && str_contains($userAgent, 'Mobile')) {
            return 'Android Phone';
        }

        if (str_contains($userAgent, 'Android')) {
            return 'Android Tablet';
        }

        // Desktop browsers
        if (str_contains($userAgent, 'Chrome') && str_contains($userAgent, 'Windows')) {
            return 'Chrome on Windows';
        }

        if (str_contains($userAgent, 'Firefox') && str_contains($userAgent, 'Windows')) {
            return 'Firefox on Windows';
        }

        if (str_contains($userAgent, 'Edge') && str_contains($userAgent, 'Windows')) {
            return 'Edge on Windows';
        }

        if (str_contains($userAgent, 'Safari') && str_contains($userAgent, 'Macintosh')) {
            return 'Safari on macOS';
        }

        if (str_contains($userAgent, 'Chrome') && str_contains($userAgent, 'Macintosh')) {
            return 'Chrome on macOS';
        }

        if (str_contains($userAgent, 'Firefox') && str_contains($userAgent, 'Linux')) {
            return 'Firefox on Linux';
        }

        if (str_contains($userAgent, 'Chrome') && str_contains($userAgent, 'Linux')) {
            return 'Chrome on Linux';
        }

        // Generic fallbacks
        if (str_contains($userAgent, 'Windows')) {
            return 'Windows Browser';
        }

        if (str_contains($userAgent, 'Macintosh')) {
            return 'macOS Browser';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux Browser';
        }

        return 'Web Browser';
    }

    /**
     * Detecta si el dispositivo es móvil
     *
     * @param string|null $userAgent User-Agent del navegador
     * @return bool True si es un dispositivo móvil
     */
    public static function isMobile(?string $userAgent): bool
    {
        if (!$userAgent) {
            return false;
        }

        $mobileKeywords = [
            'iPhone',
            'iPad',
            'Android',
            'Mobile',
            'BlackBerry',
            'Windows Phone',
        ];

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta el navegador principal
     *
     * @param string|null $userAgent User-Agent del navegador
     * @return string Nombre del navegador (Chrome, Firefox, Safari, Edge, Unknown)
     */
    public static function getBrowser(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        if (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        }

        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }

        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }

        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }

        return 'Unknown';
    }

    /**
     * Detecta el sistema operativo
     *
     * @param string|null $userAgent User-Agent del navegador
     * @return string Nombre del OS (Windows, macOS, Linux, iOS, Android, Unknown)
     */
    public static function getOS(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            return 'iOS';
        }

        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        }

        if (str_contains($userAgent, 'Macintosh')) {
            return 'macOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        return 'Unknown';
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
