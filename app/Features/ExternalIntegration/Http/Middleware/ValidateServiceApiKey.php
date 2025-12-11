<?php

declare(strict_types=1);

namespace App\Features\ExternalIntegration\Http\Middleware;

use App\Features\ExternalIntegration\Models\ServiceApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validar API Keys de servicios externos (Widget).
 * 
 * Valida el header X-Service-Key y adjunta la empresa asociada al request.
 * 
 * Uso: middleware('service.api-key') en rutas
 */
class ValidateServiceApiKey
{
    /**
     * Nombre del header donde se espera la API Key.
     */
    private const HEADER_NAME = 'X-Service-Key';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Obtener API Key del header
        $apiKey = $request->header(self::HEADER_NAME);

        // 2. Verificar que se proporcionó una key
        if (empty($apiKey)) {
            return $this->errorResponse(
                message: 'API Key requerida. Incluye el header X-Service-Key.',
                code: 'MISSING_API_KEY',
                status: 401
            );
        }

        // 3. Buscar la API Key en la base de datos
        $serviceKey = ServiceApiKey::findByKey($apiKey);

        // 4. Verificar que existe
        if (!$serviceKey) {
            return $this->errorResponse(
                message: 'API Key inválida.',
                code: 'INVALID_API_KEY',
                status: 401
            );
        }

        // 5. Verificar que está activa
        if (!$serviceKey->is_active) {
            return $this->errorResponse(
                message: 'API Key desactivada. Contacta al administrador.',
                code: 'API_KEY_DISABLED',
                status: 401
            );
        }

        // 6. Verificar que no ha expirado
        if ($serviceKey->isExpired()) {
            return $this->errorResponse(
                message: 'API Key expirada. Solicita una nueva al administrador.',
                code: 'API_KEY_EXPIRED',
                status: 401
            );
        }

        // 7. Cargar la empresa asociada (eager load para evitar N+1)
        $serviceKey->load('company');

        // 8. Verificar que la empresa existe y está activa
        if (!$serviceKey->company) {
            return $this->errorResponse(
                message: 'Empresa no encontrada.',
                code: 'COMPANY_NOT_FOUND',
                status: 404
            );
        }

        if ($serviceKey->company->status !== 'active') {
            return $this->errorResponse(
                message: 'La empresa asociada no está activa.',
                code: 'COMPANY_INACTIVE',
                status: 403
            );
        }

        // 9. Marcar como usada (de forma asíncrona para no bloquear)
        dispatch(function () use ($serviceKey) {
            $serviceKey->markAsUsed();
        })->afterResponse();

        // 10. Adjuntar datos al request para uso en controllers
        $request->merge([
            '_service_api_key' => $serviceKey,
            '_service_company' => $serviceKey->company,
            '_service_company_id' => $serviceKey->company->id,
        ]);

        // 11. Continuar con la request
        return $next($request);
    }

    /**
     * Genera una respuesta de error JSON consistente.
     */
    private function errorResponse(string $message, string $code, int $status): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
            'category' => 'authentication',
        ], $status);
    }
}
