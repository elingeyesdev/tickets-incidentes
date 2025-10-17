<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * EnsureUserHasRole Middleware
 *
 * Verifica que el usuario autenticado tenga al menos uno de los roles requeridos.
 *
 * Uso en rutas:
 * Route::middleware(['auth:sanctum', 'role:USER'])->group(function () { ... });
 * Route::middleware(['auth:sanctum', 'role:USER,AGENT'])->group(function () { ... });
 *
 * @package App\Shared\Http\Middleware
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Roles requeridos (separados por comas o múltiples parámetros)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado, retornar 401
        if (!$user) {
            Log::warning('EnsureUserHasRole: Usuario no autenticado intentó acceder a ruta protegida', [
                'route' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Unauthenticated. Please login first.'
            ], 401);
        }

        // Obtener roles del usuario desde la relación
        $userRoles = $user->userRoles->pluck('role_code')->toArray();

        // Verificar si el usuario tiene al menos uno de los roles requeridos
        $hasRole = count(array_intersect($roles, $userRoles)) > 0;

        if (!$hasRole) {
            Log::warning('EnsureUserHasRole: Usuario sin permisos intentó acceder a ruta', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $userRoles,
                'required_roles' => $roles,
                'route' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Forbidden. Required roles: ' . implode(', ', $roles),
                'your_roles' => $userRoles,
            ], 403);
        }

        // Usuario tiene permiso, continuar
        return $next($request);
    }
}
