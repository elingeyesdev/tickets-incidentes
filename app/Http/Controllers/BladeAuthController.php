<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

/**
 * Controller para manejar autenticación y redirecciones en vistas Blade
 * Las vistas Blade usan JWT tokens guardados en localStorage
 */
class BladeAuthController extends Controller
{
    /**
     * Verificar si el usuario está autenticado (cliente-side check)
     * Esta es una verificación básica, el servidor valida en cada request API
     */
    public function checkAuth(Request $request)
    {
        // El token se valida en el lado del cliente con JavaScript
        // Si no hay token, se redirige a login
        return response()->json(['authenticated' => true]);
    }

    /**
     * Logout - limpiar sesión del servidor si es necesario
     */
    public function logout(Request $request)
    {
        // El logout se maneja principalmente en el lado del cliente
        // Aquí podríamos invalidar tokens del servidor si fuera necesario
        return response()->json(['success' => true, 'message' => 'Sesión cerrada']);
    }
}
