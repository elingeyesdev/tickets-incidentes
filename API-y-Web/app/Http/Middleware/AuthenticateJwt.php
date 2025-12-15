<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Shared\Exceptions\AuthenticationException;
use App\Features\Authentication\Services\TokenService;

/**
 * Middleware para autenticación con JWT
 *
 * Valida tokens JWT en el header Authorization: Bearer <token>
 * Compatible con guards 'api' y puede usarse con 'auth:api' en routes.
 */
class AuthenticateJwt
{
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Extraer token del header Authorization: Bearer <token>
        $token = $this->getTokenFromHeader($request);

        if (!$token) {
            throw new AuthenticationException('Token no proporcionado. Usa header: Authorization: Bearer <token>');
        }

        try {
            // Validar token - esto chequea:
            // 1. Firma JWT válida
            // 2. Claims requeridos presentes
            // 3. No está blacklisteado (logout)
            // 4. Usuario no está blacklisteado globalmente
            $payload = $this->tokenService->validateAccessToken($token);

            // Obtener usuario desde BD usando el user_id del token
            $user = \App\Features\UserManagement\Models\User::find($payload->user_id);

            if (!$user) {
                throw new AuthenticationException('Usuario no encontrado');
            }

            // Establecer usuario autenticado en request
            $request->setUserResolver(fn() => $user);

            // Establecer en auth() helper
            auth()->setUser($user);
        } catch (\App\Features\Authentication\Exceptions\TokenExpiredException $e) {
            throw \App\Shared\Exceptions\AuthenticationException::tokenExpired();
        } catch (\App\Features\Authentication\Exceptions\TokenInvalidException $e) {
            throw \App\Shared\Exceptions\AuthenticationException::tokenInvalid();
        } catch (\Exception $e) {
            throw new AuthenticationException($e->getMessage());
        }

        return $next($request);
    }

    /**
     * Extraer token del header Authorization
     *
     * Formato esperado: Authorization: Bearer <token>
     */
    protected function getTokenFromHeader(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return null;
        }

        // Verificar que comienza con "Bearer "
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        // Extraer el token (después de "Bearer ")
        return substr($header, 7);
    }
}
