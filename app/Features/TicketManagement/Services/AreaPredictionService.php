<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Area Prediction Service
 *
 * Servicio que usa Gemini AI para predecir el área más adecuada para un ticket
 * basado en la categoría y descripción proporcionada por el usuario.
 *
 * Arquitectura:
 * - Patrón Proxy: El frontend nunca accede directamente a la API de Gemini
 * - Contexto Dinámico: Carga las áreas activas de la company del usuario autenticado
 * - Seguridad: API Key almacenada en .env, no expuesta al cliente
 */
class AreaPredictionService
{
    /**
     * Predecir el área más adecuada usando Gemini AI
     *
     * @param Company $company Empresa del usuario (para obtener sus áreas)
     * @param string $categoryName Nombre de la categoría seleccionada
     * @param string|null $categoryDescription Descripción de la categoría (opcional)
     * @param bool $returnRaw Si es true devuelve un array con keys ['area_id','raw']
     * @return array|string|null UUID del área predicha, o null si no se pudo predecir.
     *                                  Si $returnRaw=true devuelve array ['area_id'=>string|null,'raw'=>string]
     */
    public function predictArea(
        Company $company,
        string $categoryName,
        ?string $categoryDescription = null,
        bool $returnRaw = false
    ): array|string|null {
        // 1. Obtener áreas activas de la empresa dinámicamente
        $areas = $this->getCompanyAreas($company);

        if ($areas->isEmpty()) {
            Log::warning('AreaPredictionService: No hay áreas activas para la empresa', [
                'company_id' => $company->id,
            ]);
            return null;
        }

        // 2. Construir el contexto para Gemini
        $areasContext = $this->buildAreasContext($areas);

        // 3. Construir el prompt para Gemini
        $prompt = $this->buildPrompt($categoryName, $categoryDescription, $areasContext);

        // 4. Llamar a Gemini API
        try {
            $result = $this->callGeminiAPI($prompt, $areas);

            $areaId = $result['area_id'] ?? null;
            $raw = $result['raw'] ?? '';

            Log::info('AreaPredictionService: Predicción exitosa', [
                'company_id' => $company->id,
                'category' => $categoryName,
                'predicted_area_id' => $areaId,
            ]);

            if ($returnRaw) {
                return ['area_id' => $areaId, 'raw' => $raw];
            }

            return $areaId;

        } catch (\Exception $e) {
            Log::error('AreaPredictionService: Error al predecir área', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtener áreas activas de la empresa
     */
    private function getCompanyAreas(Company $company)
    {
        return Area::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);
    }

    /**
     * Construir el contexto de áreas para el prompt
     */
    private function buildAreasContext($areas): string
    {
        $context = [];

        foreach ($areas as $area) {
            $context[] = sprintf(
                '- ID: %s | Nombre: "%s" | Descripción: "%s"',
                $area->id,
                $area->name,
                $area->description ?? 'Sin descripción'
            );
        }

        return implode("\n", $context);
    }

    /**
     * Construir un contexto más corto (solo ID y Nombre) para reintentos cuando
     * el prompt completo produzca respuestas vacías o llegue al límite de tokens.
     */
    private function buildAreasContextShort($areas): string
    {
        $context = [];

        foreach ($areas as $area) {
            $context[] = sprintf(
                '- ID: %s | Nombre: "%s"',
                $area->id,
                $area->name
            );
        }

        return implode("\n", $context);
    }

    /**
     * Construir el prompt para Gemini
     */
    private function buildPrompt(
        string $categoryName,
        ?string $categoryDescription,
        string $areasContext
    ): string {
        $categoryInfo = "Categoría: {$categoryName}";
        if ($categoryDescription) {
            $categoryInfo .= "\nDescripción: {$categoryDescription}";
        }

        // Prompt claro que fuerza razonamiento detallado
        return <<<PROMPT
TAREA: Asignar el ticket a la MEJOR área según su contenido.

TICKET:
{$categoryInfo}

ÁREAS DISPONIBLES:
{$areasContext}

ANÁLISIS REQUERIDO - PIENSA PASO A PASO:
1. ¿Cuál es el TIPO de problema? (técnico, financiero, administrativo, cliente, ventas, RH, producción, operativo)
2. ¿Qué área GESTIONA este tipo de problema específicamente?
3. Lee cada descripción de área. ¿Cuál coincide EXACTAMENTE?
4. ¿Es "Administración"? Solo si es contabilidad, presupuestos, tesorería o legal puro.

PIENSA DETENIDAMENTE - NO APRESURES LA RESPUESTA.

RESPUESTA (SOLO JSON):
{"area_id":"[UUID_CORRECTO]"}
PROMPT;
    }

    /**
     * Llamar a la API de Gemini
     *
     * @throws \Exception si la llamada falla o la respuesta no es válida
     */
    private function callGeminiAPI(string $prompt, $areas): array
    {
        $apiKey = config('services.gemini.api_key');
            $model = config('services.gemini.model', 'gemini-2.5-flash');

            // Aceptamos que la config pueda venir con o sin el prefijo 'models/'.
            // La API espera el resource name completo como 'models/gemini-2.5-flash'.
            if (str_starts_with($model, 'models/')) {
                $modelName = $model;
            } else {
                $modelName = 'models/' . $model;
            }

        if (empty($apiKey)) {
            throw new \Exception('Gemini API Key no configurada. Verifica GEMINI_API_KEY en .env');
        }

        // Endpoint de Gemini API
            $url = "https://generativelanguage.googleapis.com/v1beta/{$modelName}:generateContent?key={$apiKey}";

        // Log del modelo usado para facilitar debugging en entornos de staging/producción
            Log::debug('AreaPredictionService: Using Gemini model', ['configured' => $model, 'resource' => $modelName]);

        // Hacer la petición HTTP con muchos tokens y sin thinking mode (no soportado en 2.5-flash)
        $response = Http::timeout(60)
            ->retry(2, 1000)
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.3, // Bajo pero no cero, para evitar sesgos
                    'maxOutputTokens' => 8192, // Muchos tokens para que el modelo no se trunce
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('Error en la API de Gemini: ' . $response->body());
        }

        // Parsear la respuesta
        $responseData = $response->json();

        // Si la respuesta no contiene el texto esperado, intentamos un reintento
        // con un prompt más corto (solo IDs y nombres) y más tokens.
        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            Log::warning('AreaPredictionService: Primera respuesta sin contenido, reintentando con prompt reducido', [
                'company_areas_count' => count($areas),
            ]);

            // Construir prompt reducido
            $areasContextShort = $this->buildAreasContextShort($areas);
            $shortPrompt = $this->buildPrompt($categoryName = '', $categoryDescription = null, $areasContextShort);

            $response2 = Http::timeout(30)
                ->retry(2, 1000)
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $shortPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.0,
                        'maxOutputTokens' => 512,
                    ],
                ]);

            if ($response2->successful()) {
                $responseData2 = $response2->json();
                if (isset($responseData2['candidates'][0]['content']['parts'][0]['text'])) {
                    $rawResponse = trim($responseData2['candidates'][0]['content']['parts'][0]['text']);
                } else {
                    // Log completo de la respuesta para depuración cuando el formato no coincide
                    Log::error('AreaPredictionService: Gemini response missing expected path after retry', [
                        'response_body' => substr($response2->body(), 0, 5000),
                        'response_json' => $responseData2,
                    ]);

                    // Fallback heurístico inmediato
                    $fallback = $this->heuristicFallback($areas, $shortPrompt);
                    return ['area_id' => $fallback, 'raw' => $response2->body()];
                }
            } else {
                Log::error('AreaPredictionService: Error en la API de Gemini en reintento', ['body' => $response2->body()]);
                throw new \Exception('Error en la API de Gemini en reintento: ' . $response2->body());
            }

        } else {
            $rawResponse = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
        }

        // Log para depuración: respuesta cruda de Gemini (no incluye API key)
        Log::debug('AreaPredictionService: Gemini raw response', [
            'raw' => substr($rawResponse, 0, 2000),
            'full_response' => $rawResponse, // Log completo para debugging
        ]);

        // Intentar parsear JSON estrictamente: {"area_id":"..."}
        $areaId = $this->parseAreaIdFromJson($rawResponse);

        Log::debug('AreaPredictionService: After JSON parsing', [
            'extracted_area_id' => $areaId,
            'raw_text' => substr($rawResponse, 0, 500),
        ]);

        // Si no encontramos un area_id en JSON, intentar extraer UUID libremente
        if (empty($areaId)) {
            $areaId = $this->extractUUID($rawResponse);
        }

        // Validar que el UUID pertenece a una de las áreas disponibles
        if (!$this->validateAreaId($areaId, $areas)) {
            // Fallback heurístico: intentar emparejar por nombre/keywords
            $fallback = $this->heuristicFallback($areas, $rawResponse);
            if ($fallback) {
                return ['area_id' => $fallback, 'raw' => $rawResponse];
            }

            return ['area_id' => null, 'raw' => $rawResponse];
        }

        return ['area_id' => $areaId, 'raw' => $rawResponse];
    }

    /**
     * Intentar extraer area_id desde un JSON en el texto
     */
    private function parseAreaIdFromJson(string $text): ?string
    {
        // Buscar patrón: {"area_id":"<uuid>"}
        $pattern = '/\{"area_id"\s*:\s*"([0-9a-fA-F-]{36})"\}/';
        if (preg_match($pattern, $text, $matches)) {
            return $matches[1];
        }

        // También intentar decodificar si el texto es JSON puro
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['area_id'])) {
            return (string) $decoded['area_id'];
        }

        return null;
    }

    /**
     * Heurística simple para fallback cuando la IA no devuelve un ID válido.
     * - Intenta igualdad exacta por nombre
     * - Luego compara tokens y elige la mayor coincidencia
     * - Finalmente devuelve la primera área disponible como último recurso
     */
    private function heuristicFallback($areas, string $promptOrRaw): ?string
    {
        // Intentar extraer la categoría del prompt
        $category = null;
        if (preg_match('/Categoría:\s*"([^"]+)"/i', $promptOrRaw, $m)) {
            $category = trim($m[1]);
        } elseif (preg_match('/Categoría seleccionada:\s*"([^"]+)"/i', $promptOrRaw, $m2)) {
            $category = trim($m2[1]);
        }

        if ($category) {
            // Búsqueda exacta por nombre
            foreach ($areas as $area) {
                if (mb_strtolower($area->name) === mb_strtolower($category)) {
                    return $area->id;
                }
            }

            // Búsqueda por tokens de similitud
            $catTokens = preg_split('/\s+/', mb_strtolower($category));
            $best = null;
            $bestScore = 0;

            foreach ($areas as $area) {
                $hay = mb_strtolower($area->name . ' ' . ($area->description ?? ''));
                $score = 0;
                foreach ($catTokens as $t) {
                    if (strlen($t) < 3) {
                        continue;
                    }
                    if (mb_strpos($hay, $t) !== false) {
                        $score++;
                    }
                }
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $area;
                }
            }

            if ($best && $bestScore > 0) {
                return $best->id;
            }
        }

        // Último recurso: devolver la primera área disponible
        if ($areas->isNotEmpty()) {
            return $areas->first()->id;
        }

        return null;
    }

    /**
     * Extraer UUID de la respuesta de Gemini
     */
    private function extractUUID(string $text): string
    {
        // Patrón regex para UUID v4
        $pattern = '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i';

        if (preg_match($pattern, $text, $matches)) {
            return $matches[0];
        }

        // Si no encuentra UUID, devolver el texto limpio (por si acaso Gemini respondió bien)
        return trim($text);
    }

    /**
     * Validar que el área ID pertenece a las áreas disponibles
     */
    private function validateAreaId(string $areaId, $areas): bool
    {
        return $areas->contains('id', $areaId);
    }
}
