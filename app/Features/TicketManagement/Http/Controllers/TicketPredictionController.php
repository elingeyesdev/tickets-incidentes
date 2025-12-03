<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Http\Requests\PredictAreaRequest;
use App\Features\TicketManagement\Services\AreaPredictionService;
use Illuminate\Http\JsonResponse;

/**
 * Ticket Prediction Controller
 *
 * Controlador proxy seguro que actúa como intermediario entre el frontend
 * y la API de Gemini AI para predecir el área más adecuada para un ticket.
 *
 * Patrón de Seguridad:
 * - El frontend NUNCA tiene acceso directo a la API Key de Gemini
 * - Todas las llamadas pasan por este proxy backend
 * - La API Key está protegida en .env y no se expone al cliente
 */
class TicketPredictionController
{
    public function __construct(
        private readonly AreaPredictionService $predictionService
    ) {}

    /**
     * Predecir área usando IA basándose en la categoría seleccionada
     *
     * POST /api/tickets/predict-area
     *
     * Body:
     * {
     *   "company_id": "uuid",
     *   "category_name": "Soporte Técnico",
     *   "category_description": "Problemas técnicos con el sistema..."
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "predicted_area_id": "uuid",
     *     "area_name": "Red y Operaciones",
     *     "confidence": "high"
     *   }
     * }
     */
    public function predictArea(PredictAreaRequest $request): JsonResponse
    {
        // 1. Obtener la empresa (ya validada en el Request)
        $company = Company::find($request->input('company_id'));

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada.',
            ], 404);
        }

        // 2. Llamar al servicio de predicción
        try {
            $debug = $request->query('debug') === '1';

            $prediction = $this->predictionService->predictArea(
                company: $company,
                categoryName: $request->input('category_name'),
                categoryDescription: $request->input('category_description'),
                returnRaw: $debug
            );

            // Manejar retorno cuando solicitamos raw (debug)
            if (is_array($prediction)) {
                $predictedAreaId = $prediction['area_id'] ?? null;
                $rawResponse = $prediction['raw'] ?? null;
            } else {
                $predictedAreaId = $prediction;
                $rawResponse = null;
            }

            // 3. Si no se pudo predecir, devolver mensaje amigable
            if (!$predictedAreaId) {
                $base = [
                    'success' => false,
                    'message' => 'No se pudo determinar el área más adecuada. Por favor, selecciona manualmente.',
                ];

                if (!empty($rawResponse) && $debug) {
                    $base['raw'] = $rawResponse;
                }

                return response()->json($base, 200); // 200 porque no es un error del servidor
            }

            // 4. Obtener información del área predicha
            $area = Area::find($predictedAreaId);

            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'El área predicha no es válida.',
                ], 500);
            }

            // 5. Devolver respuesta exitosa
            $response = [
                'success' => true,
                'data' => [
                    'predicted_area_id' => $area->id,
                    'area_name' => $area->name,
                    'area_description' => $area->description,
                    'confidence' => 'high', // Gemini generalmente tiene alta confianza con temperatura baja
                ],
                'message' => 'Área sugerida automáticamente usando IA.',
            ];

            if (!empty($rawResponse) && $debug) {
                $response['raw'] = $rawResponse;
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            // Log del error (ya lo hace el servicio, pero por si acaso)
            \Log::error('TicketPredictionController: Error en predicción', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al predecir el área. Por favor, selecciona manualmente.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
