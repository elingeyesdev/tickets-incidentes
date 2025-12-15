<?php

declare(strict_types=1);

namespace App\Features\ExternalIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Controller para las vistas del Widget embebible.
 * 
 * Estas rutas son accedidas a través del iframe desde proyectos externos.
 * La autenticación se maneja a través del token JWT pasado en la URL.
 */
class WidgetController extends Controller
{
    /**
     * Vista principal del widget.
     * 
     * Si viene con token → muestra tickets directamente
     * Si viene sin token → muestra el flujo de autenticación
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $token = $request->query('token');
        $apiKey = $request->query('api_key');
        
        // Datos que pasaremos a la vista
        $viewData = [
            'token' => $token,
            'apiKey' => $apiKey,
            'helpdeskUrl' => config('app.url'),
            'hasToken' => !empty($token),
        ];
        
        // Si viene con token válido, intentamos decodificarlo para obtener info
        if ($token) {
            $tokenData = $this->decodeTokenPayload($token);
            $viewData['tokenData'] = $tokenData;
            $viewData['role'] = $tokenData['active_role']['code'] ?? 'USER';
            $viewData['companyId'] = $tokenData['company_id'] ?? null;
        }
        
        return view('widget.index', $viewData);
    }

    /**
     * Vista de tickets del widget (ya autenticado).
     * 
     * Esta vista es la que muestra los tickets una vez autenticado.
     * Es idéntica a shared/tickets/index pero con layout de widget.
     * 
     * @param Request $request
     * @return View
     */
    public function tickets(Request $request): View
    {
        $token = $request->query('token');
        
        if (!$token) {
            abort(401, 'Token requerido');
        }
        
        $tokenData = $this->decodeTokenPayload($token);
        
        return view('widget.tickets.index', [
            'token' => $token,
            'role' => $tokenData['active_role']['code'] ?? 'USER',
            'companyId' => $tokenData['company_id'] ?? null,
            'userId' => $tokenData['sub'] ?? null,
        ]);
    }

    /**
     * Decodifica el payload de un JWT (sin validar firma).
     * 
     * La validación de firma se hace en las llamadas a la API.
     * Aquí solo extraemos información para la UI.
     * 
     * @param string $token
     * @return array
     */
    private function decodeTokenPayload(string $token): array
    {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return [];
            }
            
            $payload = $parts[1];
            // Add padding if needed
            $payload = str_pad($payload, strlen($payload) + (4 - strlen($payload) % 4) % 4, '=');
            $decoded = base64_decode($payload);
            
            if (!$decoded) {
                return [];
            }
            
            return json_decode($decoded, true) ?? [];
            
        } catch (\Exception $e) {
            return [];
        }
    }
}
