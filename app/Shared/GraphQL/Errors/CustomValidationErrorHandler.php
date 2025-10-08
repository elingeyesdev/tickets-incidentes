<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use Nuwave\Lighthouse\Exceptions\ValidationException;

/**
 * Custom Validation Error Handler
 *
 * Maneja errores de validación de GraphQL con diferenciación por entorno.
 *
 * DESARROLLO:
 * - Estructura `validation` detallada: {"email": ["Email is required"]}
 * - locations, path, timestamp, environment visibles
 * - Mensaje técnico: "Validation error"
 *
 * PRODUCCIÓN:
 * - Estructura `fieldErrors` user-friendly: [{"field": "email", "message": "..."}]
 * - locations y path OCULTOS (seguridad)
 * - Mensaje genérico: "Los datos proporcionados no son válidos."
 *
 * Mejoras sobre handler anterior:
 * - ✅ Diferenciación DEV/PROD automática
 * - ✅ Reutiliza BaseErrorHandler
 * - ✅ Códigos de error consistentes
 * - ✅ Quita prefijos "input." de campos
 */
class CustomValidationErrorHandler extends BaseErrorHandler
{
    /**
     * Determina si debe manejar ValidationException
     */
    protected function shouldHandle(\Throwable $exception): bool
    {
        return $exception instanceof ValidationException;
    }

    /**
     * Formatea errores de validación
     */
    protected function formatError(array $result, \Throwable $exception): array
    {
        // Si no hay extensiones de validación, retornar como está
        if (!isset($result['extensions']['validation'])) {
            return $result;
        }

        // Limpiar nombres de campos (quitar "input." prefix)
        $validationErrors = $result['extensions']['validation'];
        $cleanedErrors = $this->cleanValidationErrors($validationErrors);

        // Formatear según entorno
        if (EnvironmentErrorFormatter::isProduction()) {
            // PRODUCCIÓN: fieldErrors array (user-friendly para frontend)
            $result['extensions']['fieldErrors'] = EnvironmentErrorFormatter::toFieldErrors($cleanedErrors);
            unset($result['extensions']['validation']); // Quitar estructura técnica
        } else {
            // DESARROLLO: validation map (detallado para debugging)
            $result['extensions']['validation'] = $cleanedErrors;
        }

        return $result;
    }

    /**
     * Código de error
     */
    protected function getErrorCode(\Throwable $exception): string
    {
        return ErrorCodeRegistry::VALIDATION_ERROR;
    }

    /**
     * Mensaje para desarrollo (técnico)
     */
    protected function getDevelopmentMessage(\Throwable $exception): string
    {
        return 'Validation error';
    }

    /**
     * Mensaje para producción (user-friendly)
     */
    protected function getProductionMessage(\Throwable $exception): string
    {
        return 'Los datos proporcionados no son válidos. Por favor verifica la información.';
    }

    /**
     * Limpiar errores de validación
     *
     * Quita prefijos "input." y limpia mensajes
     *
     * @param array<string, array<string>> $errors
     * @return array<string, array<string>>
     */
    private function cleanValidationErrors(array $errors): array
    {
        $cleaned = [];

        foreach ($errors as $field => $messages) {
            // Quitar prefijo "input." del nombre del campo
            // "input.email" -> "email"
            // "input.passwordConfirmation" -> "passwordConfirmation"
            $cleanField = str_replace('input.', '', $field);

            // Limpiar cada mensaje
            $cleanedMessages = array_map(
                fn(string $message) => $this->cleanValidationMessage($message, $cleanField),
                $messages
            );

            $cleaned[$cleanField] = $cleanedMessages;
        }

        return $cleaned;
    }

    /**
     * Limpiar mensaje de validación individual
     *
     * Reemplaza referencias a "input.X" con solo "X"
     *
     * @param string $message
     * @param string $fieldName
     * @return string
     */
    private function cleanValidationMessage(string $message, string $fieldName): string
    {
        // Reemplazar todas las referencias a "input.campo" con solo "campo"
        $message = preg_replace('/\binput\.(\w+)\b/', '$1', $message);

        // Opcional: Hacer el primer carácter mayúscula
        return ucfirst($message);
    }
}
