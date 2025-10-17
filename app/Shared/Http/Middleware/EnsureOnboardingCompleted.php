<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * EnsureOnboardingCompleted Middleware
 *
 * Verifica que el usuario haya completado el proceso de onboarding antes de
 * permitir acceso a rutas de la zona autenticada.
 *
 * Criterio de onboarding completado:
 * - onboarding_completed_at IS NOT NULL (columna timestamp en BD)
 * - El accessor booleano $user->onboarding_complete se calcula dinámicamente
 *
 * Flujo de onboarding:
 * 1. CompleteProfile (first_name, last_name)
 * 2. ConfigurePreferences (theme, language)
 * 3. Mutation markOnboardingCompleted establece timestamp
 *
 * IMPORTANTE: Email verification NO es prerequisito del onboarding
 *
 * Uso en rutas:
 * Route::middleware(['auth:sanctum', 'onboarding.completed'])->group(function () { ... });
 *
 * @package App\Shared\Http\Middleware
 */
class EnsureOnboardingCompleted
{
    /**
     * Rutas excluidas del chequeo de onboarding
     * (necesarias para completar el onboarding)
     */
    protected array $excludedRoutes = [
        'onboarding.profile',
        'onboarding.preferences',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si no hay usuario, redirigir a login
        if (!$user) {
            return redirect('/login');
        }

        // Verificar si la ruta actual está excluida del chequeo
        $currentRoute = $request->route()?->getName();
        if ($currentRoute && in_array($currentRoute, $this->excludedRoutes)) {
            return $next($request);
        }

        // Verificar si completó onboarding usando el timestamp
        if (is_null($user->onboarding_completed_at)) {
            Log::info('EnsureOnboardingCompleted: Usuario sin onboarding completado redirigido', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'onboarding_completed_at' => $user->onboarding_completed_at,
                'route' => $request->path(),
            ]);

            // Redirigir al primer paso del onboarding
            return redirect('/onboarding/profile');
        }

        // Onboarding completado, continuar
        return $next($request);
    }
}
