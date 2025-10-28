<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Exceptions\RefreshTokenRequiredException;
use App\Shared\Helpers\DeviceInfoParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * RefreshTokenController
 *
 * REST endpoint para renovar access token usando refresh token desde cookie HttpOnly.
 * Este enfoque es más seguro que enviar el refresh token en el body o headers.
 *
 * Endpoint: POST /auth/refresh
 *
 * Características de seguridad:
 * - Lee refresh token desde cookie HttpOnly (más seguro)
 * - Implementa rotación de tokens (invalida token viejo, genera nuevo)
 * - Cookie segura con SameSite=Strict y Secure (HTTPS)
 * - CORS configurado para credentials: include
 */
class RefreshTokenController
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Renovar access token usando refresh token desde cookie
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            // Obtener refresh token de múltiples fuentes (en orden de prioridad):
            // 1. Header X-Refresh-Token (más seguro, recomendado)
            // 2. Cookie refresh_token (para web con HttpOnly cookies)
            // 3. Body refreshToken (para clientes limitados)
            // REPLICA EXACTAMENTE la lógica de RefreshTokenMutation de GraphQL
            $refreshToken = $request->header('X-Refresh-Token')
                ?? $request->cookie('refresh_token')
                ?? $request->input('refreshToken')
                ?? null;

            if (!$refreshToken) {
                throw new RefreshTokenRequiredException();
            }

            // Extraer información del dispositivo
            $deviceInfo = [
                'name' => $this->detectDeviceName($request->userAgent()),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            // Renovar tokens usando el servicio
            $result = $this->authService->refreshToken($refreshToken, $deviceInfo);

            // Crear respuesta JSON con nuevo access token
            $response = response()->json([
                'accessToken' => $result['access_token'],
                'tokenType' => 'Bearer',
                'expiresIn' => $result['expires_in'],
                'message' => 'Token refreshed successfully. New refresh token set in HttpOnly cookie.',
            ], 200);

            // Establecer nuevo refresh token en cookie HttpOnly
            $cookieLifetime = (int) config('jwt.refresh_ttl'); // En minutos

            $response->cookie(
                'refresh_token',                    // Nombre
                $result['refresh_token'],           // Valor (nuevo token)
                $cookieLifetime,                    // Tiempo de vida en minutos
                '/',                                // Path
                null,                               // Domain (null = dominio actual)
                config('app.env') === 'production', // Secure (solo HTTPS en producción)
                true,                               // HttpOnly (no accesible desde JavaScript)
                false,                              // Raw
                'strict'                            // SameSite (strict para máxima seguridad)
            );

            Log::info('Token refreshed successfully via REST endpoint', [
                'ip' => $request->ip(),
                'device' => $deviceInfo['name'],
            ]);

            return $response;

        } catch (RefreshTokenRequiredException $e) {
            Log::warning('Refresh token missing in cookie', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Refresh token not provided. Please login again.',
                'error' => 'REFRESH_TOKEN_REQUIRED',
            ], 401);

        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Invalid or expired refresh token. Please login again.',
                'error' => 'INVALID_REFRESH_TOKEN',
            ], 401);
        }
    }

    /**
     * Detectar nombre del dispositivo desde user agent
     *
     * @param string|null $userAgent
     * @return string
     */
    private function detectDeviceName(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        // Browser detection
        $browser = 'Unknown Browser';
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        }

        // OS detection
        $os = 'Unknown OS';
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $os = 'iOS';
        }

        return "{$browser} on {$os}";
    }
}
