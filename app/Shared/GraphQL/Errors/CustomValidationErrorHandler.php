<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use GraphQL\Error\Error;
use Nuwave\Lighthouse\Execution\ErrorHandler;
use Nuwave\Lighthouse\Exceptions\ValidationException;

/**
 * Custom Validation Error Handler
 *
 * Reemplaza el ValidationErrorHandler de Lighthouse para formatear
 * errores de validación de manera profesional y user-friendly.
 *
 * Mejoras:
 * - Quita prefijos "input." de nombres de campos
 * - Limpia mensajes de error
 * - Oculta stack traces en producción
 * - Mensaje principal más claro
 */
class CustomValidationErrorHandler implements ErrorHandler
{
    /**
     * Manejar el error
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

        // Solo procesar ValidationException
        if (!$underlyingException instanceof ValidationException) {
            return $next($error);
        }

        // Dejar que el siguiente handler procese primero para obtener las extensiones
        $result = $next($error);

        // Si el resultado es null o no tiene extensiones de validación, retornar como está
        if ($result === null || !isset($result['extensions']['validation'])) {
            return $result;
        }

        // Limpiar nombres de campos en las extensiones
        $validationErrors = $result['extensions']['validation'];
        $cleanedErrors = $this->cleanValidationErrors($validationErrors);

        // Reemplazar con errores limpios
        $result['extensions']['validation'] = $cleanedErrors;
        $result['message'] = 'Validation error';

        // SIEMPRE quitar file/line/trace de Lighthouse para validation errors
        // (son internos de Lighthouse, no útiles para el usuario)
        unset($result['extensions']['file']);
        unset($result['extensions']['line']);
        unset($result['extensions']['trace']);

        return $result;
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
