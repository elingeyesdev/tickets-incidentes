<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Resources\SessionInfoResource;
use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Services\TokenService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\AuthorizationException;
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

            if (!$user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Obtener todas las sesiones activas del usuario
            $sessions = $user->refreshTokens()
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->orderByDesc('last_used_at')
                ->get();

            // Obtener refresh token actual de header o cookie
            $currentRefreshToken = $request->header('X-Refresh-Token')
                ?? $request->cookie('refresh_token');

            // Marcar sesión actual
            foreach ($sessions as $session) {
                $session->isCurrent = $currentRefreshToken
                    ? hash_equals(hash('sha256', $currentRefreshToken), $session->token_hash)
                    : false;
            }

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

            if (!$user) {
                throw new AuthenticationException('User not authenticated');
            }

            $everywhere = filter_var(
                $request->input('everywhere', false),
                FILTER_VALIDATE_BOOLEAN
            );

            if ($everywhere) {
                // Logout de todas las sesiones
                $this->authService->logoutAllDevices($user->id);
            } else {
                // Logout solo de la sesión actual
                $accessToken = str_replace('Bearer ', '', $request->header('Authorization', ''));
                $refreshToken = $request->header('X-Refresh-Token')
                    ?? $request->cookie('refresh_token');

                $this->authService->logout($accessToken, $refreshToken ?? '', $user->id);
            }

            return response()
                ->json([
                    'success' => true,
                    'message' => 'Logged out successfully',
                ], 200)
                ->cookie(
                    'refresh_token',
                    '',
                    minutes: 0,
                    path: '/',
                    domain: null,
                    secure: !app()->isLocal(),
                    httpOnly: true,
                    sameSite: 'lax'
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

            if (!$user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Obtener token actual del header o cookie
            $accessToken = str_replace('Bearer ', '', $request->header('Authorization', ''));
            $tokenPayload = $this->tokenService->validateAccessToken($accessToken);
            $currentSessionId = $tokenPayload['session_id'];

            // No se puede revocar la sesión actual
            if ($sessionId === $currentSessionId) {
                throw new CannotRevokeCurrentSessionException(
                    'Cannot revoke current session. Use logout instead.'
                );
            }

            // Obtener la sesión
            $session = $user->refreshTokens()
                ->where('id', $sessionId)
                ->first();

            if (!$session) {
                throw new NotFoundException('Session not found');
            }

            // Verificar que pertenece al usuario
            if ($session->user_id !== $user->id) {
                throw new AuthorizationException('Not authorized to revoke this session');
            }

            // Revocar la sesión
            $this->tokenService->blacklistToken($sessionId);
            $session->revoked_at = now();
            $session->save();

            return response()->json([
                'success' => true,
                'message' => 'Session revoked successfully',
            ], 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
