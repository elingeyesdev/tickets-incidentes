<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use Illuminate\Auth\AuthenticationException as LaravelAuthenticationException;

/**
 * Custom Authentication Error Handler
 *
 * Maneja errores de autenticación (401) con diferenciación por entorno.
 *
 * DESARROLLO:
 * - Mensaje específico del error
 * - locations, path, timestamp, environment visibles
 * - stacktrace completo
 *
 * PRODUCCIÓN:
 * - Mensaje genérico: "No estás autenticado"
 * - locations y path OCULTOS
 * - Sin stacktrace
 *
 * Mejoras sobre handler anterior:
 * - ✅ Diferenciación DEV/PROD automática
 * - ✅ Reutiliza BaseErrorHandler
 * - ✅ Logging automático en producción
 * - ✅ Códigos de error específicos (UNAUTHENTICATED, TOKEN_EXPIRED, etc.)
 */
class CustomAuthenticationErrorHandler extends BaseErrorHandler
{
    /**
     * Determina si debe manejar errores de autenticación
     */
    protected function shouldHandle(\Throwable $exception): bool
    {
        // Laravel AuthenticationException
        if ($exception instanceof LaravelAuthenticationException) {
            return true;
        }

        // Custom AuthenticationException del proyecto (Shared)
        if ($exception instanceof \App\Shared\Exceptions\AuthenticationException) {
            return true;
        }

        return false;
    }

    /**
     * Formatea errores de autenticación
     */
    protected function formatError(array $result, \Throwable $exception): array
    {
        // No necesita formateo adicional, BaseErrorHandler maneja todo
        // El código y mensaje se determinan en getErrorCode() y get*Message()
        return $result;
    }

    /**
     * Código de error específico según tipo de excepción
     */
    protected function getErrorCode(\Throwable $exception): string
    {
        // Si la excepción tiene getErrorCode(), usarlo
        if ($exception instanceof \App\Shared\Exceptions\AuthenticationException) {
            return $exception->getErrorCode();
        }

        // Default: UNAUTHENTICATED
        return ErrorCodeRegistry::UNAUTHENTICATED;
    }

    /**
     * Mensaje para desarrollo (específico)
     */
    protected function getDevelopmentMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        // Return message as-is for consistency with test expectations
        return $message ?: 'Unauthenticated';
    }

    /**
     * Mensaje para producción (genérico y user-friendly)
     */
    protected function getProductionMessage(\Throwable $exception): string
    {
        // Usar mensaje de la excepción si es user-friendly
        if ($exception instanceof \App\Shared\Exceptions\AuthenticationException) {
            return $exception->getMessage();
        }

        // Mensaje genérico
        return 'No estás autenticado. Por favor inicia sesión.';
    }

    /**
     * Verifica si es un error de autenticación
     *
     * @param \Throwable|null $exception
     * @return bool
     */
    private function isAuthenticationError(?\Throwable $exception): bool
    {
        if ($exception === null) {
            return false;
        }

        // Laravel AuthenticationException
        if ($exception instanceof LaravelAuthenticationException) {
            return true;
        }

        // Custom AuthenticationException del proyecto
        if ($exception instanceof \App\Features\Authentication\Exceptions\AuthenticationException) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene mensaje limpio y user-friendly
     *
     * @param \Throwable|null $exception
     * @return string
     */
    private function getCleanMessage(?\Throwable $exception): string
    {
        if ($exception === null) {
            return 'No estás autenticado';
        }

        // Usar mensaje de la excepción si existe y es claro
        $message = $exception->getMessage();

        // Mensajes genéricos de Laravel → reemplazar con mensajes claros
        if (empty($message) || $message === 'Unauthenticated.') {
            return 'No estás autenticado. Por favor inicia sesión.';
        }

        return $message;
    }
}
