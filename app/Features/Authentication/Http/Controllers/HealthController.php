<?php

namespace App\Features\Authentication\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Health Check Controller
 *
 * Endpoints para verificación de estado de la API
 */
class HealthController
{
    /**
     * Health check del API
     *
     * Verifica que el API está funcionando correctamente
     */
    #[OA\Get(
        path: '/api/health',
        summary: 'Health check',
        description: 'Verifica el estado de la API',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API está funcionando'
            ),
        ]
    )]
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
