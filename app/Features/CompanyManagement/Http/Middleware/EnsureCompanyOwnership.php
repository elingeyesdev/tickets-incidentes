<?php

namespace App\Features\CompanyManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Shared\Helpers\JWTHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: EnsureCompanyOwnership
 *
 * Valida que COMPANY_ADMIN solo pueda acceder a su propia empresa.
 * PLATFORM_ADMIN tiene acceso a todas las empresas.
 *
 * Uso en rutas:
 * Route::get('/companies/{company}', [CompanyController::class, 'show'])
 *     ->middleware(['jwt.require', 'company.ownership']);
 */
class EnsureCompanyOwnership
{
    /**
     * Maneja la solicitud entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = JWTHelper::getAuthenticatedUser();
        $company = $request->route('company');

        // Verificar que exista la empresa en la ruta
        if (!$company) {
            abort(404, 'Empresa no encontrada');
        }

        // PLATFORM_ADMIN puede acceder a todas las empresas
        if ($user->hasRole('PLATFORM_ADMIN')) {
            return $next($request);
        }

        // COMPANY_ADMIN solo puede acceder a su propia empresa
        if ($user->hasRole('COMPANY_ADMIN') && $company->admin_user_id === $user->id) {
            return $next($request);
        }

        // En cualquier otro caso, denegar acceso
        abort(403, 'No tienes permisos para acceder a esta empresa');
    }
}
