<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Exceptions\RefreshTokenRequiredException;
use App\Shared\Helpers\DeviceInfoParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

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
    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Refresh access token',
        description: 'Generates a new JWT access token and refresh token using a valid refresh token. The refresh token is read from an HttpOnly cookie (recommended for security), or alternatively from the X-Refresh-Token header (for testing in Swagger) or request body. This endpoint implements token rotation: the old refresh token is invalidated and a new one is generated. The new refresh token is automatically set in an HttpOnly cookie.',
        tags: ['Authentication'],
        parameters: [
            new OA\Parameter(
                name: 'X-Refresh-Token',
                description: 'Refresh token for testing in Swagger UI. In production, the refresh token should be sent via HttpOnly cookie (automatically handled by browsers). This header takes priority over cookie and body.',
                in: 'header',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...')
            ),
        ],
        requestBody: new OA\RequestBody(
            description: 'Alternative method to send refresh token (not recommended for production)',
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'refreshToken',
                        type: 'string',
                        description: 'Refresh token (use only if cookie and header are not available)',
                        example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed successfully. New access token returned in response body. New refresh token set in HttpOnly cookie.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'accessToken',
                            type: 'string',
                            description: 'New JWT access token for API authentication',
                            example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
                        ),
                        new OA\Property(
                            property: 'tokenType',
                            type: 'string',
                            description: 'Token type',
                            example: 'Bearer'
                        ),
                        new OA\Property(
                            property: 'expiresIn',
                            type: 'integer',
                            description: 'Access token expiration time in seconds',
                            example: 2592000
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            description: 'Success message explaining that refresh token is in cookie',
                            example: 'Token refreshed successfully. New refresh token set in HttpOnly cookie.'
                        ),
                    ]
                ),
                headers: [
                    new OA\Header(
                        header: 'Set-Cookie',
                        description: 'HttpOnly cookie containing new refresh token (name: refresh_token, path: /, httpOnly: true, sameSite: strict, secure: true in production, maxAge: 43200 minutes / 30 days)',
                        schema: new OA\Schema(
                            type: 'string',
                            example: 'refresh_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...; Path=/; HttpOnly; Secure; SameSite=Strict; Max-Age=2592000'
                        )
                    ),
                ]
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid, expired, or missing refresh token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            description: 'Human-readable error message',
                            example: 'Invalid or expired refresh token. Please login again.'
                        ),
                        new OA\Property(
                            property: 'error',
                            type: 'string',
                            description: 'Error code for programmatic handling',
                            enum: ['REFRESH_TOKEN_REQUIRED', 'INVALID_REFRESH_TOKEN'],
                            example: 'INVALID_REFRESH_TOKEN'
                        ),
                    ]
                )
            ),
        ]
    )]
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

            // Extraer información del dispositivo (DeviceInfoParser resuelve IP real detrás de load balancer)
            $deviceInfo = DeviceInfoParser::fromRequest($request);

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

            // TAMBIÉN actualizar la cookie de Access Token (jwt_token)
            // Esto es CRÍTICO para que la navegación web subsiguiente no falle en el middleware
            $response->cookie(
                'jwt_token',
                $result['access_token'],
                $result['expires_in'] / 60,         // Minutos
                '/',
                null,
                config('app.env') === 'production',
                false,                              // Not HttpOnly (JS lo necesita)
                false,                              // Raw
                'lax'                               // SameSite
            );

            Log::info('Token refreshed successfully via REST endpoint', [
                'ip' => $deviceInfo['ip'],
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

}
