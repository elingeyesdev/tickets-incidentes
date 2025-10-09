<?php

namespace App\Http\Middleware;

use App\Features\Authentication\Services\TokenService;
use App\Features\UserManagement\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * GraphQL JWT Middleware
 *
 * Extrae y valida JWT tokens del header Authorization
 * Autentica al usuario en Laravel para que Lighthouse pueda accederlo
 *
 * IMPORTANTE: No bloquea requests sin token (queries/mutations públicas deben funcionar)
 * La autenticación se valida a nivel de field con @jwt directive
 *
 * FLOW:
 * 1. Extrae token del header Authorization
 * 2. Valida token y obtiene user_id
 * 3. Carga usuario desde BD
 * 4. Autentica usuario en Laravel (Auth::setUser)
 * 5. Lighthouse automáticamente pasa el usuario al contexto GraphQL
 */
class GraphQLJWTMiddleware
{
    public function __construct(
        private TokenService $tokenService
    ) {
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
        // Extraer token del header Authorization
        $token = $this->extractTokenFromHeader($request);

        if ($token) {
            try {
                // Validar token y extraer payload
                $payload = $this->tokenService->validateAccessToken($token);

                // Extraer user_id del payload
                $userId = $payload->user_id ?? $payload->sub;

                if ($userId) {
                    // Cargar usuario desde base de datos
                    $user = User::find($userId);

                    if ($user && $user->isActive()) {
                        // ✅ CRÍTICO: Autenticar usuario en Laravel
                        // Lighthouse automáticamente lo pasa al contexto GraphQL
                        Auth::setUser($user);

                        // Agregar metadata al request para uso adicional
                        $request->attributes->set('jwt_payload', $payload);
                        $request->attributes->set('jwt_user_id', $userId);
                        $request->merge(['_authenticated_user_id' => $userId]);
                    }
                }
            } catch (\Exception $e) {
                // Token inválido: NO bloqueamos el request
                // La directiva @jwt manejará el error si el field requiere auth
                $request->attributes->set('jwt_error', $e->getMessage());
            }
        }

        return $next($request);
    }

    /**
     * Extraer token del header Authorization
     *
     * Soporta formatos:
     * - "Bearer <token>" (estándar OAuth 2.0)
     * - "<token>" (token directo, para compatibilidad con clientes GraphQL)
     *
     * @param Request $request
     * @return string|null
     */
    private function extractTokenFromHeader(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return null;
        }

        // Formato 1: "Bearer <token>" (estándar OAuth 2.0)
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        // Formato 2: Token directo (para compatibilidad con GraphQL Playground y otros clientes)
        // Solo si parece un JWT (tiene al menos 2 puntos)
        if (substr_count($header, '.') >= 2) {
            return $header;
        }

        return null;
    }
}