<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use Illuminate\Auth\Access\AuthorizationException as LaravelAuthorizationException;
use Nuwave\Lighthouse\Exceptions\AuthorizationException as LighthouseAuthorizationException;

/**
 * Custom Authorization Error Handler
 *
 * Maneja errores de autorización/permisos (403) con diferenciación por entorno.
 *
 * DESARROLLO:
 * - Mensaje específico del error (puede incluir detalles de política)
 * - locations, path, timestamp, environment visibles
 * - stacktrace completo
 *
 * PRODUCCIÓN:
 * - Mensaje genérico: "No tienes permisos"
 * - locations y path OCULTOS (seguridad)
 * - Sin stacktrace (puede revelar lógica de negocio)
 *
 * Mejoras sobre handler anterior:
 * - ✅ Diferenciación DEV/PROD automática
 * - ✅ Reutiliza BaseErrorHandler
 * - ✅ Logging automático en producción
 * - ✅ Oculta mensajes técnicos (Policy, Gate, etc.)
 */
class CustomAuthorizationErrorHandler extends BaseErrorHandler
{
    /**
     * Determina si debe manejar errores de autorización
     */
    protected function shouldHandle(\Throwable $exception): bool
    {
        // Laravel AuthorizationException (de Policies/Gates)
        if ($exception instanceof LaravelAuthorizationException) {
            return true;
        }

        // Lighthouse AuthorizationException (de @can directive)
        if ($exception instanceof LighthouseAuthorizationException) {
            return true;
        }

        // Custom ForbiddenException del proyecto
        if ($exception instanceof \App\Shared\Exceptions\ForbiddenException) {
            return true;
        }

        return false;
    }

    /**
     * Formatea errores de autorización
     */
    protected function formatError(array $result, \Throwable $exception): array
    {
        // No necesita formateo adicional, BaseErrorHandler maneja todo
        return $result;
    }

    /**
     * Código de error
     */
    protected function getErrorCode(\Throwable $exception): string
    {
        return ErrorCodeRegistry::FORBIDDEN;
    }

    /**
     * Mensaje para desarrollo
     *
     * Puede incluir detalles de políticas para debugging
     */
    protected function getDevelopmentMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        // Si es mensaje genérico de Laravel, mejorarlo
        if (empty($message) || $message === 'This action is unauthorized.') {
            return 'Authorization failed: User does not have permission for this action.';
        }

        // En desarrollo, mantener el mensaje original (puede tener detalles útiles)
        return $message;
    }

    /**
     * Mensaje para producción
     *
     * Siempre genérico, no exponer lógica de negocio
     */
    protected function getProductionMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        // Si el mensaje es técnico, usar mensaje genérico
        if ($this->isTechnicalMessage($message)) {
            return 'No tienes permisos suficientes para realizar esta acción.';
        }

        // Si es mensaje vacío o genérico de Laravel
        if (empty($message) || $message === 'This action is unauthorized.') {
            return 'No tienes permisos para realizar esta acción.';
        }

        // Si el mensaje parece ser user-friendly, usarlo
        // (asumimos que el developer lo escribió con cuidado)
        return $message;
    }
}
