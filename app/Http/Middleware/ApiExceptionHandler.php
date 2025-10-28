<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Throwable;
use App\Shared\Exceptions\HelpdeskException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\AuthorizationException;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\RateLimitExceededException;
use App\Shared\GraphQL\Errors\EnvironmentErrorFormatter;
use Illuminate\Database\QueryException;

/**
 * Middleware para Manejo de Excepciones en API REST
 *
 * Convierte todas las excepciones (HelpdeskException y otras) en respuestas JSON
 * con códigos HTTP apropiados y diferenciación DEV/PROD.
 *
 * FLUJO:
 * 1. Captura excepción
 * 2. Determina HTTP status code
 * 3. Formatea respuesta JSON
 * 4. Aplica EnvironmentErrorFormatter (DEV/PROD)
 * 5. Retorna respuesta JSON
 */
class ApiExceptionHandler
{
    /**
     * Mapa de excepciones a códigos HTTP
     *
     * Estas excepciones se convierten automáticamente a los códigos indicados
     */
    protected array $statusCodes = [
        ValidationException::class => 422,
        AuthenticationException::class => 401,
        AuthorizationException::class => 403,
        NotFoundException::class => 404,
        ConflictException::class => 409,
        RateLimitExceededException::class => 429,
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (LaravelValidationException $e) {
            // Validación de Laravel Form Requests
            return $this->handleValidationException($e);
        } catch (HelpdeskException $e) {
            // Excepciones custom de Helpdesk
            return $this->handleHelpdeskException($e);
        } catch (QueryException $e) {
            // Errores de base de datos
            return $this->handleDatabaseException($e);
        } catch (Throwable $e) {
            // Cualquier otra excepción
            return $this->handleGenericException($e);
        }
    }

    /**
     * Manejar excepción de validación de Laravel
     *
     * Laravel Form Requests lanzan LaravelValidationException
     */
    protected function handleValidationException(LaravelValidationException $e): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => 'Errores de validación.',
            'code' => 'VALIDATION_ERROR',
            'errors' => $e->errors(),
        ];

        return response()->json($response, 422);
    }

    /**
     * Manejar excepción de Helpdesk
     *
     * Convierte a HTTP status code y aplica formateo DEV/PROD
     */
    protected function handleHelpdeskException(HelpdeskException $e): \Illuminate\Http\JsonResponse
    {
        // Determinar status code
        $statusCode = $this->getStatusCodeFor($e);

        // Construir respuesta base
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
            'code' => $e->getErrorCode(),
        ];

        // Si la excepción tiene errores de validación, incluirlos
        if (method_exists($e, 'getErrors')) {
            $response['errors'] = $e->getErrors();
        }

        // Agregar información adicional según la excepción
        if (method_exists($e, 'getCategory')) {
            $response['category'] = $e->getCategory();
        }

        // En DESARROLLO, agregar información de debugging
        if (app()->isLocal()) {
            $response['debug'] = [
                'timestamp' => now()->toIso8601String(),
                'environment' => app()->environment(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->formatStackTrace($e),
            ];
        } else {
            // En PRODUCCIÓN, agregar solo timestamp
            $response['timestamp'] = now()->toIso8601String();
        }

        // Log en PRODUCCIÓN
        if (!app()->isLocal()) {
            \Log::error('API Exception: ' . $e->getMessage(), [
                'code' => $e->getErrorCode(),
                'category' => $e->getCategory() ?? 'unknown',
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Manejar excepción de base de datos
     */
    protected function handleDatabaseException(QueryException $e): \Illuminate\Http\JsonResponse
    {
        if (app()->isLocal()) {
            $response = [
                'success' => false,
                'message' => 'Error de base de datos.',
                'code' => 'DATABASE_ERROR',
                'debug' => [
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                ],
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Error interno del servidor.',
                'code' => 'INTERNAL_SERVER_ERROR',
                'timestamp' => now()->toIso8601String(),
            ];

            \Log::error('Database Exception: ' . $e->getMessage(), [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
        }

        return response()->json($response, 500);
    }

    /**
     * Manejar excepción genérica
     */
    protected function handleGenericException(Throwable $e): \Illuminate\Http\JsonResponse
    {
        if (app()->isLocal()) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INTERNAL_SERVER_ERROR',
                'debug' => [
                    'timestamp' => now()->toIso8601String(),
                    'environment' => app()->environment(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $this->formatStackTrace($e),
                ],
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Error interno del servidor.',
                'code' => 'INTERNAL_SERVER_ERROR',
                'timestamp' => now()->toIso8601String(),
            ];

            \Log::error('Unhandled Exception: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json($response, 500);
    }

    /**
     * Determinar HTTP status code para la excepción
     */
    protected function getStatusCodeFor(HelpdeskException $e): int
    {
        // Buscar en mapa de excepciones
        foreach ($this->statusCodes as $exceptionClass => $statusCode) {
            if ($e instanceof $exceptionClass) {
                return $statusCode;
            }
        }

        // Por defecto, 500
        return 500;
    }

    /**
     * Formatear stack trace para respuesta
     */
    protected function formatStackTrace(Throwable $e): array
    {
        $trace = [];
        foreach ($e->getTrace() as $item) {
            $trace[] = [
                'function' => $item['function'] ?? 'unknown',
                'file' => $item['file'] ?? 'unknown',
                'line' => $item['line'] ?? 0,
                'class' => $item['class'] ?? null,
            ];
        }
        return $trace;
    }
}
