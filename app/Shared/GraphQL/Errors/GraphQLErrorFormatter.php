<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use Illuminate\Support\Facades\Log;
use Nuwave\Lighthouse\Exceptions\ValidationException;

/**
 * GraphQL Error Formatter
 *
 * Formatea errores de GraphQL de manera profesional:
 * - Oculta stack traces en producción
 * - Limpia nombres de campos (quita prefijos "input.")
 * - Mensajes user-friendly
 * - Logging de errores
 *
 * Se configura en config/lighthouse.php:
 * 'error_handlers' => [
 *     \App\Shared\GraphQL\Errors\GraphQLErrorFormatter::class,
 * ]
 */
class GraphQLErrorFormatter
{
    /**
     * Formatear error GraphQL
     *
     * @param Error $error
     * @return array
     */
    public static function formatError(Error $error): array
    {
        // Obtener excepción original si existe
        $exception = $error->getPrevious();

        // Caso 1: Error de validación (Lighthouse ValidationException)
        if ($exception instanceof ValidationException) {
            return self::formatValidationError($error, $exception);
        }

        // Caso 2: Otros errores (lógica de negocio, autenticación, etc.)
        return self::formatGenericError($error);
    }

    /**
     * Formatear error de validación
     *
     * Limpia los mensajes y quita información innecesaria
     *
     * @param Error $error
     * @param ValidationException $exception
     * @return array
     */
    private static function formatValidationError(Error $error, ValidationException $exception): array
    {
        $validator = $exception->validator();
        $errors = $validator->errors()->toArray();

        // Limpiar nombres de campos (quitar "input." prefix)
        $cleanedErrors = [];
        foreach ($errors as $field => $messages) {
            // "input.email" -> "email"
            // "input.passwordConfirmation" -> "passwordConfirmation"
            $cleanField = str_replace('input.', '', $field);

            // Limpiar mensajes (quitar "input." de los mensajes también)
            $cleanedMessages = array_map(function ($message) {
                return str_replace('input.', '', $message);
            }, $messages);

            $cleanedErrors[$cleanField] = $cleanedMessages;
        }

        $formattedError = [
            'message' => 'Validation error',
            'extensions' => [
                'category' => 'validation',
                'validation' => $cleanedErrors,
            ],
        ];

        // Añadir locations y path si existen
        if ($error->getLocations()) {
            $formattedError['locations'] = FormattedError::formatLocations($error->getLocations());
        }

        if ($error->getPath()) {
            $formattedError['path'] = $error->getPath();
        }

        // Solo en desarrollo: añadir info de debug
        if (config('app.debug')) {
            $formattedError['extensions']['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return $formattedError;
    }

    /**
     * Formatear error genérico
     *
     * @param Error $error
     * @return array
     */
    private static function formatGenericError(Error $error): array
    {
        $exception = $error->getPrevious();

        // Log del error para debugging interno
        if ($exception) {
            Log::error('GraphQL Error', [
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }

        $formattedError = [
            'message' => $error->getMessage(),
            'extensions' => [
                'category' => 'internal',
            ],
        ];

        // Añadir locations y path si existen
        if ($error->getLocations()) {
            $formattedError['locations'] = FormattedError::formatLocations($error->getLocations());
        }

        if ($error->getPath()) {
            $formattedError['path'] = $error->getPath();
        }

        // Solo en desarrollo: mostrar detalles completos
        if (config('app.debug') && $exception) {
            $formattedError['extensions']['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())
                    ->take(5) // Limitar trace a 5 primeras líneas
                    ->map(function ($trace) {
                        return [
                            'file' => $trace['file'] ?? 'unknown',
                            'line' => $trace['line'] ?? 0,
                            'function' => $trace['function'] ?? 'unknown',
                        ];
                    })
                    ->toArray(),
            ];
        }

        return $formattedError;
    }
}
