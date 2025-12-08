<?php

namespace App\Http\Middleware;

use App\Shared\Helpers\JWTHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware híbrido: Spatie Permission + JWT Active Role
 *
 * Este middleware combina:
 * 1. Verificación de Spatie Permission (tabla model_has_roles)
 * 2. Verificación del active_role en JWT (sistema multi-rol)
 *
 * Uso en rutas:
 * Route::middleware(['spatie.active_role:PLATFORM_ADMIN'])->group(...);
 * Route::middleware(['spatie.active_role:COMPANY_ADMIN,AGENT'])->group(...);
 *
 * IMPORTANTE:
 * - El usuario debe tener el rol en Spatie (model_has_roles)
 * - Y el rol debe ser su active_role en el JWT actual
 * - El company_id viene del JWT, NO de Spatie
 */
class SpatieRoleWithActiveRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Role codes required
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Obtener usuario autenticado vía JWT
        $user = JWTHelper::getAuthenticatedUser();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }
            return redirect()->route('login');
        }

        // 2. Obtener payload JWT para verificar active_role
        $payload = $request->attributes->get('jwt_payload');
        $hasExplicitActiveRole = $payload && isset($payload['active_role']);

        // 3. Verificar cada rol permitido
        foreach ($roles as $role) {
            $role = trim($role);

            // 3a. Verificar con Spatie que el usuario tiene el rol
            if (!$user->hasRole($role)) {
                continue; // No tiene este rol en Spatie, probar siguiente
            }

            // 3b. Si hay active_role en JWT, verificar que coincide
            if ($hasExplicitActiveRole) {
                try {
                    if (JWTHelper::isActiveRole($role)) {
                        // ✅ Usuario tiene rol en Spatie Y es su active_role
                        return $next($request);
                    }
                } catch (\Exception $e) {
                    // Error verificando active_role, continuar
                }
            } else {
                // Sin active_role explícito en JWT (backward compatibility)
                // Solo verificar que tiene el rol en Spatie
                return $next($request);
            }
        }

        // 4. Usuario no tiene ninguno de los roles requeridos (o no es su active_role)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions - role not active',
                'code' => 'ROLE_NOT_ACTIVE',
                'hint' => 'User may have the role but it is not their active_role'
            ], 403);
        }

        return redirect()->route('dashboard')
            ->with('warning', 'No tienes permisos para acceder a esa sección. Verifica tu rol activo.');
    }
}
