<?php

namespace App\Http\Middleware;

use App\Features\Authentication\Services\TokenService;
use Closure;
use Illuminate\Http\Request;

/**
 * GraphQL JWT Middleware
 *
 * Extrae y valida JWT tokens del header Authorization
 * Agrega el usuario autenticado al contexto de GraphQL
 *
 * IMPORTANTE: No bloquea requests sin token (queries/mutations públicas deben funcionar)
 * La autenticación se valida a nivel de field con @jwt directive
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

                // Agregar al request para que esté disponible en resolvers
                $request->attributes->set('jwt_payload', $payload);
                $request->attributes->set('jwt_user_id', $payload->user_id ?? $payload->sub);

                // IMPORTANTE: También agregamos el user_id a la request normal
                // para que los resolvers puedan accederlo fácilmente
                $request->merge(['_authenticated_user_id' => $payload->user_id ?? $payload->sub]);
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
     * Soporta formato: "Bearer <token>"
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

        // Formato esperado: "Bearer <token>"
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}