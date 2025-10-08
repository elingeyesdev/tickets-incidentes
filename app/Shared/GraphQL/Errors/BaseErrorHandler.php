<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use GraphQL\Error\Error;
use Nuwave\Lighthouse\Execution\ErrorHandler;

/**
 * Base Error Handler
 *
 * Clase abstracta base para todos los error handlers de GraphQL.
 * Proporciona funcionalidad común y reutilizable para formatear errores
 * según el entorno (desarrollo vs producción).
 *
 * VENTAJAS:
 * - ✅ Reutilizable: Cualquier feature extiende esta clase
 * - ✅ DRY: Lógica de formateo en un solo lugar
 * - ✅ Escalable: Agregar nuevo handler es trivial
 * - ✅ Profesional: Sigue estándares de GraphQL
 *
 * CÓMO USAR:
 * ```php
 * class CustomValidationErrorHandler extends BaseErrorHandler
 * {
 *     protected function shouldHandle(\Throwable $exception): bool
 *     {
 *         return $exception instanceof ValidationException;
 *     }
 *
 *     protected function formatError(array $result, \Throwable $exception): array
 *     {
 *         // Tu lógica específica aquí
 *         return $result;
 *     }
 * }
 * ```
 *
 * @package App\Shared\GraphQL\Errors
 */
abstract class BaseErrorHandler implements ErrorHandler
{
    /**
     * Manejar el error
     *
     * Template Method Pattern:
     * 1. Verificar si debe manejar el error (shouldHandle)
     * 2. Formatear error específico (formatError)
     * 3. Aplicar formateo por entorno (EnvironmentErrorFormatter)
     *
     * @param Error|null $error
     * @param \Closure $next
     * @return array|null
     */
    public function __invoke(?Error $error, \Closure $next): ?array
    {
        // Si no hay error, pasar al siguiente handler
        if ($error === null) {
            return $next($error);
        }

        $underlyingException = $error->getPrevious();

        // Verificar si este handler debe procesar el error
        if ($underlyingException === null || !$this->shouldHandle($underlyingException)) {
            return $next($error);
        }

        // Obtener resultado del siguiente handler (incluye extensiones de Lighthouse)
        $result = $next($error);

        if ($result === null) {
            return null;
        }

        // 1. Formateo específico del tipo de error (implementado por subclases)
        $result = $this->formatError($result, $underlyingException);

        // 2. Agregar código y categoría de error
        $result['extensions']['code'] = $this->getErrorCode($underlyingException);
        $result['extensions']['category'] = ErrorCodeRegistry::getCategory(
            $result['extensions']['code']
        );

        // 3. Aplicar formateo por entorno (DEV vs PROD)
        $result = EnvironmentErrorFormatter::format($result, [
            'exception' => $underlyingException,
            'service' => $this->getServiceName(),
            'developmentMessage' => $this->getDevelopmentMessage($underlyingException),
            'productionMessage' => $this->getProductionMessage($underlyingException),
        ]);

        // 4. Log en producción (para debugging interno)
        if (EnvironmentErrorFormatter::isProduction()) {
            $this->logError($underlyingException, $result);
        }

        return $result;
    }

    /**
     * Determina si este handler debe manejar la excepción
     *
     * @param \Throwable $exception
     * @return bool
     */
    abstract protected function shouldHandle(\Throwable $exception): bool;

    /**
     * Formatea el error específico del handler
     *
     * Aquí va la lógica específica del tipo de error (validación, auth, etc.)
     *
     * @param array $result Resultado del error de Lighthouse
     * @param \Throwable $exception Excepción original
     * @return array Error formateado
     */
    abstract protected function formatError(array $result, \Throwable $exception): array;

    /**
     * Obtiene el código de error para la excepción
     *
     * Debe retornar una constante de ErrorCodeRegistry
     *
     * @param \Throwable $exception
     * @return string
     */
    abstract protected function getErrorCode(\Throwable $exception): string;

    /**
     * Obtiene mensaje para desarrollo (detallado)
     *
     * Por defecto usa el mensaje de la excepción, pero puede ser sobreescrito
     *
     * @param \Throwable $exception
     * @return string
     */
    protected function getDevelopmentMessage(\Throwable $exception): string
    {
        return $exception->getMessage();
    }

    /**
     * Obtiene mensaje para producción (genérico y user-friendly)
     *
     * Por defecto usa el mensaje de la excepción, pero debería ser sobreescrito
     * para no exponer detalles internos
     *
     * @param \Throwable $exception
     * @return string
     */
    protected function getProductionMessage(\Throwable $exception): string
    {
        // Por defecto, usar mensaje genérico basado en la categoría
        $code = $this->getErrorCode($exception);
        $category = ErrorCodeRegistry::getCategory($code);

        return match ($category) {
            'authentication' => 'No estás autenticado. Por favor inicia sesión.',
            'authorization' => 'No tienes permisos para realizar esta acción.',
            'validation' => 'Los datos proporcionados no son válidos.',
            'business_logic' => 'No se pudo completar la operación.',
            'not_found' => 'El recurso solicitado no existe.',
            'rate_limit' => 'Demasiadas solicitudes. Por favor intenta más tarde.',
            'server_error' => 'Ocurrió un error interno. Por favor intenta más tarde.',
            default => 'Ocurrió un error al procesar tu solicitud.',
        };
    }

    /**
     * Obtiene el nombre del servicio (para metadata en desarrollo)
     *
     * @return string|null
     */
    protected function getServiceName(): ?string
    {
        return null; // Sobrescribir si se desea agregar metadata de servicio
    }

    /**
     * Log del error (solo en producción)
     *
     * @param \Throwable $exception
     * @param array $result
     * @return void
     */
    protected function logError(\Throwable $exception, array $result): void
    {
        EnvironmentErrorFormatter::logError($exception, [
            'handler' => static::class,
            'code' => $result['extensions']['code'] ?? 'UNKNOWN',
            'category' => $result['extensions']['category'] ?? 'unknown',
        ]);
    }

    /**
     * Helper: Verifica si un mensaje es técnico/interno
     *
     * @param string $message
     * @return bool
     */
    protected function isTechnicalMessage(string $message): bool
    {
        $technicalKeywords = [
            'exception',
            'error',
            'failed',
            'class',
            'method',
            'function',
            'file',
            'line',
            'stack',
            'trace',
            'debug',
            'query',
            'sql',
            'database',
            'connection',
        ];

        $messageLower = strtolower($message);

        foreach ($technicalKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper: Limpia mensaje técnico a uno user-friendly
     *
     * @param string $message
     * @param string $fallback
     * @return string
     */
    protected function cleanMessage(string $message, string $fallback): string
    {
        if (empty($message) || $this->isTechnicalMessage($message)) {
            return $fallback;
        }

        return $message;
    }
}