<?php declare(strict_types=1);

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
use App\Shared\Errors\ErrorCodeRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Middleware para Manejo de Excepciones en API REST
 *
 * Convierte todas las excepciones en respuestas JSON con:
 * - Códigos de error consistentes (ErrorCodeRegistry)
 * - HTTP status codes apropiados
 * - Diferenciación automática DEV/PROD
 *
 * ARQUITECTURA:
 * - Usa ErrorCodeRegistry (agnóstico, compartido con GraphQL)
 * - Excepciones pueden ser HelpdeskException o Laravel ValidationException
 * - Respuestas incluyen: success, message, code, category, errors (si aplica)
 * - En DEV: stacktrace + file/line. En PROD: solo timestamp
 *
 * MIGRACIÓN FUTURA:
 * Cuando se elimine GraphQL, ErrorCodeRegistry en app/Shared/Errors/ se queda
 * porque los códigos son del negocio, no de la implementación.
 */
class ApiExceptionHandler
{
    // Mapa de excepciones (mantenido para compatibilidad, pero se reemplaza por ErrorCodeRegistry)
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
        } catch (ModelNotFoundException $e) {
            // Manejar ModelNotFoundException (ej. findOrFail)
            return $this->handleModelNotFoundException($e);
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
            'message' => 'Validation failed.',
            'code' => ErrorCodeRegistry::VALIDATION_ERROR,
            'category' => ErrorCodeRegistry::getCategory(ErrorCodeRegistry::VALIDATION_ERROR),
            'errors' => $e->errors(),
        ];

        return response()->json($response, 422);
    }

    /**
     * Manejar excepción de Helpdesk
     *
     * Convierte a HTTP status code y aplica formateo DEV/PROD
     * Usa ErrorCodeRegistry para códigos consistentes
     */
    protected function handleHelpdeskException(HelpdeskException $e): \Illuminate\Http\JsonResponse
    {
        // Obtener el código de error (HelpdeskException debe tenerlo)
        $errorCode = $e->getErrorCode();

        // Determinar status code basado en ErrorCodeRegistry
        $statusCode = ErrorCodeRegistry::getSuggestedHttpStatus($errorCode);
        $category = ErrorCodeRegistry::getCategory($errorCode);

        // Construir respuesta base
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
            'code' => $errorCode,
            'category' => $category,
        ];

        // Si la excepción tiene errores de validación (ValidationException), incluirlos
        if (method_exists($e, 'getErrors') && ($errors = $e->getErrors())) {
            $response['errors'] = $errors;
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
            // En PRODUCCIÓN, agregar solo timestamp para auditoría
            $response['timestamp'] = now()->toIso8601String();
        }

        // Log en PRODUCCIÓN
        if (!app()->isLocal()) {
            \Log::error('API Exception: ' . $e->getMessage(), [
                'code' => $errorCode,
                'category' => $category,
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
        $code = ErrorCodeRegistry::DATABASE_ERROR;
        $category = ErrorCodeRegistry::getCategory($code);

        if (app()->isLocal()) {
            $response = [
                'success' => false,
                'message' => 'Database error occurred.',
                'code' => $code,
                'category' => $category,
                'debug' => [
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'timestamp' => now()->toIso8601String(),
                    'environment' => app()->environment(),
                ],
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Internal server error.',
                'code' => ErrorCodeRegistry::INTERNAL_SERVER_ERROR,
                'category' => ErrorCodeRegistry::getCategory(ErrorCodeRegistry::INTERNAL_SERVER_ERROR),
                'timestamp' => now()->toIso8601String(),
            ];

            \Log::error('Database Exception: ' . $e->getMessage(), [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return response()->json($response, 500);
    }

    /**
     * Manejar excepción genérica
     */
    protected function handleGenericException(Throwable $e): \Illuminate\Http\JsonResponse
    {
        $code = ErrorCodeRegistry::INTERNAL_SERVER_ERROR;
        $category = ErrorCodeRegistry::getCategory($code);

        if (app()->isLocal()) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $code,
                'category' => $category,
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
                'message' => 'Internal server error.',
                'code' => $code,
                'category' => $category,
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
     * Manejar ModelNotFoundException
     */
    protected function handleModelNotFoundException(ModelNotFoundException $e): \Illuminate\Http\JsonResponse
    {
        $errorCode = ErrorCodeRegistry::NOT_FOUND;
        $category = ErrorCodeRegistry::getCategory($errorCode);

        $response = [
            'success' => false,
            'message' => 'Resource not found.',
            'code' => $errorCode,
            'category' => $category,
        ];

        if (app()->isLocal()) {
            $response['debug'] = [
                'timestamp' => now()->toIso8601String(),
                'environment' => app()->environment(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->formatStackTrace($e),
            ];
        } else {
            $response['timestamp'] = now()->toIso8601String();
        }

        return response()->json($response, 404);
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
