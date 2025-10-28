<?php

declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Resources\SessionInfoResource;
use App\Features\Authentication\Models\RefreshToken;
use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Services\TokenService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\CannotRevokeCurrentSessionException;
use App\Shared\Exceptions\NotFoundException;
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
     *
     * @response 200 {"sessions": [{...}, {...}]}
     */
    #[OA\Get(
        path: '/api/auth/sessions',
        summary: 'List user sessions',
        description: 'Get all active sessions for authenticated user',
        tags: ['Sessions'],
        responses: [
            new OA\Response(response: 200, description: 'Sessions retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
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
                    'location' => null, // TODO: Implement GeoIP later
                ];
            })->toArray();

            return response()->json([
                'sessions' => SessionInfoResource::collection($sessions),
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
     *
     * @response 200 {"success": true, "message": "..."}
     */
    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout user',
        description: 'Logout from current session or all sessions',
        tags: ['Sessions'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'everywhere', type: 'boolean', nullable: true, description: 'Logout from all sessions'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Logout successful'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Replicar exactamente LogoutMutation
            $everywhere = $request->input('everywhere') ?? false;

            if ($everywhere) {
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

                if (! $accessToken) {
                    throw new AuthenticationException('Access token required for logout');
                }

                // Refresh token viene de X-Refresh-Token header o cookie
                $refreshToken = $request->header('X-Refresh-Token')
                    ?? $request->cookie('refresh_token');

                if (! $refreshToken) {
                    // Si no hay refresh token, solo invalidamos el access token
                    \Illuminate\Support\Facades\Log::warning('Logout without refresh token - only access token will be blacklisted', [
                        'user_id' => $user->id,
                    ]);
                }

                // Llamar al servicio para logout
                $this->authService->logout($accessToken, $refreshToken ?? '', $user->id);

                \Illuminate\Support\Facades\Log::info('User logged out from current session', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            return response()
                ->json([
                    'success' => true,
                    'message' => 'Logged out successfully',
                ], 200)
                ->cookie(
                    'refresh_token',
                    '',
                    0, // minutes
                    '/', // path
                    null, // domain
                    ! app()->isLocal(), // secure
                    true, // httpOnly
                    false, // raw
                    'lax' // sameSite
                );
        } catch (\Exception $e) {
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
     *
     * @response 200 {"success": true, "message": "..."}
     */
    #[OA\Delete(
        path: '/api/auth/sessions/{sessionId}',
        summary: 'Revoke a session',
        description: 'Revoke a specific session from another device',
        tags: ['Sessions'],
        parameters: [
            new OA\Parameter(
                name: 'sessionId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Session revoked'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not authorized'),
            new OA\Response(response: 404, description: 'Session not found'),
            new OA\Response(response: 409, description: 'Cannot revoke current session'),
        ]
    )]
    public function revoke(Request $request, string $sessionId): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Replicar exactamente RevokeOtherSessionMutation

            // Buscar la sesión (directo por ID, como en mutation)
            $session = RefreshToken::find($sessionId);

            // Validar que la sesión existe
            if (! $session) {
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
                    throw new CannotRevokeCurrentSessionException;
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
