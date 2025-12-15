<?php

namespace App\Features\Authentication\Http\Controllers;

use OpenApi\Attributes as OA;
use Illuminate\Http\JsonResponse;

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
        description: 'Verify that the API is operational and responding correctly',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API is operational',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'string',
                            description: 'Health status of the API',
                            example: 'ok'
                        ),
                        new OA\Property(
                            property: 'timestamp',
                            type: 'string',
                            format: 'date-time',
                            description: 'ISO 8601 timestamp of the health check',
                            example: '2025-11-01T12:00:00+00:00'
                        ),
                    ],
                    type: 'object'
                )
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
