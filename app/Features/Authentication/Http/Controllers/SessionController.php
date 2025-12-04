<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Resources\SessionInfoResource;
use App\Features\Authentication\Models\RefreshToken;
use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Services\TokenService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\AuthorizationException;
use App\Features\Authentication\Exceptions\CannotRevokeCurrentSessionException;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\TokenInvalidException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Session Controller
 *
 * REST endpoints para gestión de sesiones.
 * Una sesión = un refresh token = un dispositivo
 */
class SessionController
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * List user sessions
     *
     * Lista todas las sesiones activas del usuario.
     * Marca cuál es la sesión actual.
     *
     * @authenticated true
     * @response 200 {"sessions": [{...}, {...}]}
     */
    #[OA\Get(
        path: '/api/auth/sessions',
        summary: 'List user sessions',
        description: 'Get all active sessions for authenticated user. Returns all non-revoked refresh tokens ordered by last usage.',
        tags: ['Sessions'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sessions retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'sessions',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'sessionId', type: 'string', format: 'uuid', description: 'Session identifier', example: '9d4e3c2a-8b7f-4e5d-9c8b-7a6f5e4d3c2b'),
                                    new OA\Property(property: 'deviceName', type: 'string', nullable: true, description: 'Device name', example: 'Chrome on Windows'),
                                    new OA\Property(property: 'ipAddress', type: 'string', nullable: true, description: 'IP address', example: '192.168.1.1'),
                                    new OA\Property(property: 'userAgent', type: 'string', nullable: true, description: 'User agent string', example: 'Mozilla/5.0...'),
                                    new OA\Property(property: 'lastUsedAt', type: 'string', format: 'date-time', description: 'Last usage timestamp (ISO 8601)', example: '2025-11-01T12:00:00+00:00'),
                                    new OA\Property(property: 'expiresAt', type: 'string', format: 'date-time', description: 'Expiration timestamp (ISO 8601)', example: '2025-11-08T12:00:00+00:00'),
                                    new OA\Property(property: 'isCurrent', type: 'boolean', description: 'Whether this is the current session', example: true),
                                    new OA\Property(property: 'location', type: 'object', nullable: true, description: 'GeoIP location data from MaxMind GeoLite2', example: ['city' => 'Buenos Aires', 'country' => 'Argentina', 'country_code' => 'AR']),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Replicar exactamente MySessionsQuery

            // Obtener refresh token actual (si existe)
            $currentRefreshToken = $request->header('X-Refresh-Token')
                ?? $request->cookie('refresh_token');

            $currentTokenHash = $currentRefreshToken ? hash('sha256', $currentRefreshToken) : null;

            // Obtener todas las sesiones activas del usuario
            // Ordenar por último uso (o creación si nunca se usó) - como en MySessionsQuery
            $sessions = RefreshToken::where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->orderByRaw('COALESCE(last_used_at, created_at) DESC')
                ->get();

            // Actualizar last_used_at de la sesión actual cuando se consultan las sesiones
            // Esto mejora la precisión del timestamp sin sobrecargar la BD
            if ($currentTokenHash) {
                $currentSession = $sessions->firstWhere('token_hash', $currentTokenHash);
                if ($currentSession) {
                    $currentSession->updateLastUsed();
                }
            }

            // Mapear a formato SessionInfo (replicar query)
            $sessionsData = $sessions->map(function ($session) use ($currentTokenHash) {
                return [
                    'sessionId' => $session->id,
                    'deviceName' => $session->device_name,
                    'ipAddress' => $session->ip_address,
                    'userAgent' => $session->user_agent,
                    'lastUsedAt' => $session->last_used_at?->toIso8601String() ?? $session->created_at->toIso8601String(),
                    'expiresAt' => $session->expires_at->toIso8601String(),
                    'isCurrent' => $currentTokenHash && $session->token_hash === $currentTokenHash,
                    'location' => $session->location,
                ];
            })->toArray();

            return response()->json([
                'sessions' => $sessionsData,
            ], 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Logout user
     *
     * Logout de la sesión actual o de todas las sesiones.
     * Revoca los tokens y limpia la cookie.
     *
     * @authenticated true
     * @response 200 {"success": true, "message": "..."}
     */
    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout user',
        description: 'Logout from current session or all sessions. Revokes tokens, blacklists access token, and clears the refresh_token cookie.',
        tags: ['Sessions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'everywhere', type: 'boolean', nullable: true, description: 'Logout from all sessions', example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful. Cookie refresh_token is cleared.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new AuthenticationException('User not authenticated');
            }

            \Illuminate\Support\Facades\Log::info('[LOGOUT] Logout initiated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'everywhere' => $request->input('everywhere', false),
            ]);

            // Replicar exactamente LogoutMutation
            $everywhere = $request->input('everywhere') ?? false;

            if ($everywhere) {
                \Illuminate\Support\Facades\Log::info('[LOGOUT] Logging out from all devices');
                // Logout de todas las sesiones (todos los dispositivos)
                $this->authService->logoutAllDevices($user->id);
            } else {
                // Logout de sesión actual solamente
                // Necesitamos el access token actual y el refresh token

                // Access token viene del Authorization header (ya validado por middleware)
                $authHeader = $request->header('Authorization');
                $accessToken = null;

                if ($authHeader && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
                    $accessToken = $matches[1];
                }

                if (!$accessToken) {
                    \Illuminate\Support\Facades\Log::warning('[LOGOUT] No access token in header');
                    throw new AuthenticationException('Access token required for logout');
                }

                // Refresh token viene de X-Refresh-Token header o cookie
                $refreshToken = $request->header('X-Refresh-Token')
                    ?? $request->cookie('refresh_token');

                \Illuminate\Support\Facades\Log::info('[LOGOUT] Tokens detected', [
                    'has_access_token' => !!$accessToken,
                    'access_token_length' => $accessToken ? strlen($accessToken) : 0,
                    'has_refresh_token' => !!$refreshToken,
                    'refresh_token_length' => $refreshToken ? strlen($refreshToken) : 0,
                ]);

                if (!$refreshToken) {
                    // Si no hay refresh token, solo invalidamos el access token
                    \Illuminate\Support\Facades\Log::warning('[LOGOUT] Logout without refresh token - only access token will be blacklisted', [
                        'user_id' => $user->id,
                    ]);
                }

                // Llamar al servicio para logout
                $this->authService->logout($accessToken, $refreshToken ?? '', $user->id);

                \Illuminate\Support\Facades\Log::info('[LOGOUT] User logged out from current session', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            \Illuminate\Support\Facades\Log::info('[LOGOUT] Creating response with cleared cookies (jwt_token + refresh_token)', [
                'secure' => !app()->isLocal(),
            ]);

            return response()
                ->json([
                    'success' => true,
                    'message' => 'Logged out successfully',
                ], 200)
                ->withCookie(cookie()->forget('refresh_token'))
                ->withCookie(cookie()->forget('jwt_token'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[LOGOUT] Logout exception', [
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Revoke a session
     *
     * Revoca una sesión específica de otro dispositivo.
     * No se puede revocar la sesión actual.
     *
     * @authenticated true
     * @response 200 {"success": true, "message": "..."}
     */
    #[OA\Delete(
        path: '/api/auth/sessions/{sessionId}',
        summary: 'Revoke a session',
        description: 'Revoke a specific session from another device. Blacklists associated access tokens and revokes the refresh token. Cannot revoke the current session.',
        tags: ['Sessions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'sessionId',
                in: 'path',
                required: true,
                description: 'UUID of the session to revoke',
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                example: '9d4e3c2a-8b7f-4e5d-9c8b-7a6f5e4d3c2b'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Session revoked successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Session revoked successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not authorized to revoke this session'),
            new OA\Response(response: 404, description: 'Session not found or already revoked'),
            new OA\Response(
                response: 409,
                description: 'Conflict: Cannot revoke the current session. Use logout endpoint instead.'
            ),
        ]
    )]
    public function revoke(Request $request, string $sessionId): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Replicar exactamente RevokeOtherSessionMutation

            // Buscar la sesión (directo por ID, como en mutation)
            $session = RefreshToken::find($sessionId);

            // Validar que la sesión existe
            if (!$session) {
                throw new NotFoundException("Session '{$sessionId}' not found.");
            }

            // Validar que la sesión pertenece al usuario
            if ($session->user_id !== $user->id) {
                throw new NotFoundException("Session '{$sessionId}' does not belong to you.");
            }

            // Validar que la sesión no esté ya revocada
            if ($session->is_revoked) {
                throw new NotFoundException('Session already revoked.');
            }

            // Obtener refresh token actual
            $currentRefreshToken = $request->header('X-Refresh-Token')
                ?? $request->cookie('refresh_token');

            // Validar que no se está revocando la sesión actual
            if ($currentRefreshToken) {
                $currentTokenHash = hash('sha256', $currentRefreshToken);

                if ($session->token_hash === $currentTokenHash) {
                    throw new CannotRevokeCurrentSessionException();
                }
            }

            // Blacklist el session_id para invalidar los access tokens asociados
            $this->tokenService->blacklistToken($session->id);

            // Revocar la sesión (refresh token)
            $session->revoke($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Session revoked successfully',
            ], 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
