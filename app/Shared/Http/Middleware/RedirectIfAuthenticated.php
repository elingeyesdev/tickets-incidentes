<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RedirectIfAuthenticated Middleware
 *
 * Redirige usuarios ya autenticados lejos de páginas públicas (login, register, etc.)
 * hacia su dashboard apropiado según su rol principal.
 *
 * Uso en rutas:
 * Route::middleware(['guest'])->group(function () { ... });
 *
 * @package App\Shared\Http\Middleware
 */
class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  ...$guards
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                // Obtener el dashboard path según el rol principal del usuario
                $dashboardPath = $this->getDashboardPath($user);

                return redirect($dashboardPath);
            }
        }

        return $next($request);
    }

    /**
     * Obtiene el path del dashboard según el rol principal del usuario
     */
    private function getDashboardPath($user): string
    {
        // Obtener el primer rol (rol principal)
        $primaryRole = $user->roles->first()?->role_code;

        // Mapeo de roles a dashboards
        return match($primaryRole) {
            'PLATFORM_ADMIN' => '/admin/dashboard',
            'COMPANY_ADMIN' => '/empresa/dashboard',
            'AGENT' => '/agent/dashboard',
            'USER' => '/tickets',
            default => '/role-selector', // Si no tiene rol o es desconocido
        };
    }
}
