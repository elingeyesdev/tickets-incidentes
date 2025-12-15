<?php

declare(strict_types=1);

namespace App\Http\Middleware\JWT;

use App\Features\Authentication\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web Authentication Middleware
 *
 * Valida JWT token en rutas web.
 * Si existe token válido, lo carga en request attributes.
 * Si no existe o es inválido, continúa sin autenticar (el frontend lo maneja).
 */
class WebAuthenticationMiddleware
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if ($token) {
            try {
                $payload = $this->tokenService->validate($token);
                $request->attributes->set('jwt_user', $payload);
            } catch (\Exception $e) {
                // Token inválido, no hacer nada
                // Frontend manejará la redirección
            }
        }

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }
}
