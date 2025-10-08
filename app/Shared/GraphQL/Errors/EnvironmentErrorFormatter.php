<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use Illuminate\Support\Facades\Log;

/**
 * Environment Error Formatter
 *
 * Formatea errores de GraphQL según el entorno (desarrollo o producción).
 * Implementa las mejores prácticas de la especificación de GraphQL y
 * consideraciones de seguridad.
 *
 * DESARROLLO:
 * - Mensajes detallados
 * - locations y path visibles
 * - stacktrace completo
 * - metadata adicional (timestamp, environment, service)
 *
 * PRODUCCIÓN:
 * - Mensajes genéricos user-friendly
 * - locations y path OCULTOS (seguridad)
 * - stacktrace OCULTO (seguridad)
 * - Solo código de error y mensaje limpio
 *
 * Basado en:
 * - GraphQL Spec: https://spec.graphql.org/June2018/#sec-Errors
 * - Apollo Server Best Practices
 * - Escape.tech Security Guidelines
 *
 * @package App\Shared\GraphQL\Errors
 */
class EnvironmentErrorFormatter
{
    /**
     * Verifica si estamos en entorno de producción
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    /**
     * Verifica si el modo debug está activo
     *
     * @return bool
     */
    public static function isDebugMode(): bool
    {
        return config('app.debug', false);
    }

    /**
     * Formatea el error según el entorno
     *
     * @param array $result Resultado del error de Lighthouse
     * @param array $options Opciones adicionales de formateo
     * @return array Error formateado
     */
    public static function format(array $result, array $options = []): array
    {
        $isProduction = self::isProduction();

        // 1. Formatear campos principales según entorno
        if ($isProduction) {
            $result = self::formatForProduction($result, $options);
        } else {
            $result = self::formatForDevelopment($result, $options);
        }

        // 2. SIEMPRE quitar campos internos de Lighthouse/Laravel
        // (son detalles de implementación que no ayudan al cliente)
        $result = self::removeInternalFields($result);

        return $result;
    }

    /**
     * Formatea error para PRODUCCIÓN
     *
     * Prioridad: Seguridad y UX
     *
     * @param array $result
     * @param array $options
     * @return array
     */
    private static function formatForProduction(array $result, array $options): array
    {
        // Ocultar locations y path (pueden revelar estructura interna)
        unset($result['locations']);
        unset($result['path']);

        // Ocultar stacktrace (puede revelar código fuente)
        unset($result['extensions']['stacktrace']);
        unset($result['extensions']['exception']);

        // Ocultar metadata de debugging
        unset($result['extensions']['environment']);
        unset($result['extensions']['service']);

        // Si hay mensaje de producción personalizado, usarlo
        if (isset($options['productionMessage'])) {
            $result['message'] = $options['productionMessage'];
        }

        // Agregar solo timestamp (útil para soporte)
        if (!isset($result['extensions']['timestamp'])) {
            $result['extensions']['timestamp'] = now()->toIso8601String();
        }

        return $result;
    }

    /**
     * Formatea error para DESARROLLO
     *
     * Prioridad: Debugging y detalle máximo
     *
     * @param array $result
     * @param array $options
     * @return array
     */
    private static function formatForDevelopment(array $result, array $options): array
    {
        // Mantener locations y path (útiles para debugging)
        // (ya están en $result por defecto)

        // Agregar stacktrace si está disponible
        if (isset($options['exception']) && $options['exception'] instanceof \Throwable) {
            $result['extensions']['stacktrace'] = self::formatStackTrace($options['exception']);
        }

        // Agregar metadata útil
        $result['extensions']['timestamp'] = now()->toIso8601String();
        $result['extensions']['environment'] = config('app.env');

        // Agregar nombre del servicio si se proporciona
        if (isset($options['service'])) {
            $result['extensions']['service'] = $options['service'];
        }

        // Si hay mensaje de desarrollo personalizado, usarlo
        if (isset($options['developmentMessage'])) {
            $result['message'] = $options['developmentMessage'];
        }

        return $result;
    }

    /**
     * Remueve campos internos de Lighthouse/Laravel
     *
     * Estos campos son internos del framework y no son útiles para el cliente.
     *
     * @param array $result
     * @return array
     */
    private static function removeInternalFields(array $result): array
    {
        // Campos internos de Lighthouse (solo metadata de debugging del framework)
        unset($result['extensions']['file']);
        unset($result['extensions']['line']);
        unset($result['extensions']['trace']);

        // NO remover 'category' - es útil para el cliente agrupar errores

        return $result;
    }

    /**
     * Formatea el stacktrace de una excepción
     *
     * @param \Throwable $exception
     * @return array
     */
    private static function formatStackTrace(\Throwable $exception): array
    {
        $trace = [];

        // Agregar mensaje de la excepción
        $trace[] = sprintf(
            '%s: %s',
            get_class($exception),
            $exception->getMessage()
        );

        // Agregar archivo y línea donde se lanzó
        $trace[] = sprintf(
            '    at %s:%d',
            $exception->getFile(),
            $exception->getLine()
        );

        // Agregar primeras 5 líneas del stacktrace
        $stackLines = array_slice($exception->getTrace(), 0, 5);

        foreach ($stackLines as $line) {
            $file = $line['file'] ?? 'unknown';
            $lineNum = $line['line'] ?? 0;
            $function = $line['function'] ?? 'unknown';
            $class = isset($line['class']) ? $line['class'] . '::' : '';

            $trace[] = sprintf(
                '    at %s%s (%s:%d)',
                $class,
                $function,
                $file,
                $lineNum
            );
        }

        return $trace;
    }

    /**
     * Convierte errores de validación a estructura fieldErrors
     *
     * Formato PROD-friendly para el frontend:
     * [
     *   {"field": "email", "message": "Email ya registrado"},
     *   {"field": "password", "message": "Debe tener 8 caracteres"}
     * ]
     *
     * @param array<string, array<string>> $validationErrors
     * @return array
     */
    public static function toFieldErrors(array $validationErrors): array
    {
        $fieldErrors = [];

        foreach ($validationErrors as $field => $messages) {
            foreach ($messages as $message) {
                $fieldErrors[] = [
                    'field' => $field,
                    'message' => $message
                ];
            }
        }

        return $fieldErrors;
    }

    /**
     * Obtiene mensaje contextual según entorno
     *
     * @param string $developmentMessage Mensaje detallado para desarrollo
     * @param string $productionMessage Mensaje genérico para producción
     * @return string
     */
    public static function getContextualMessage(
        string $developmentMessage,
        string $productionMessage
    ): string {
        return self::isProduction() ? $productionMessage : $developmentMessage;
    }

    /**
     * Log del error (útil para producción)
     *
     * @param \Throwable $exception
     * @param array $context
     * @return void
     */
    public static function logError(\Throwable $exception, array $context = []): void
    {
        Log::error('GraphQL Error: ' . $exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            ...$context
        ]);
    }
}