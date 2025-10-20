<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use App\Shared\Exceptions\AuthenticationException;

/**
 * JWT Authentication Error Handler
 *
 * Maneja errores específicos de JWT con integración al sistema de errores existente.
 * Proporciona mensajes contextualizados y logging estructurado.
 *
 * INTEGRACIÓN PROFESIONAL:
 * - ✅ Reutiliza BaseErrorHandler existente
 * - ✅ Mensajes específicos por tipo de error JWT
 * - ✅ Logging estructurado para observabilidad
 * - ✅ Compatible con ErrorCodeRegistry
 * - ✅ Formateo automático DEV/PROD
 *
 * TIPOS DE ERROR JWT MANEJADOS:
 * - UNAUTHENTICATED: Usuario no autenticado
 * - ACCOUNT_SUSPENDED: Cuenta suspendida
 * - EMAIL_NOT_VERIFIED: Email no verificado
 *
 * @package App\Shared\GraphQL\Errors
 */
class JWTAuthenticationErrorHandler extends BaseErrorHandler
{
    /**
     * Determina si debe manejar errores JWT específicos
     */
    protected function shouldHandle(\Throwable $exception): bool
    {
        return $exception instanceof AuthenticationException;
    }

    /**
     * Formatea errores JWT específicos
     */
    protected function formatError(array $result, \Throwable $exception): array
    {
        // Agregar información específica de JWT si está disponible
        if ($exception instanceof AuthenticationException) {
            $result['extensions']['jwt_error_type'] = $exception->getErrorCode();
            
            // Agregar contexto específico según el tipo de error
            $errorCode = $exception->getErrorCode();
            if ($errorCode === 'ACCOUNT_SUSPENDED') {
                $result['extensions']['requires_admin_contact'] = true;
            } elseif ($errorCode === 'EMAIL_NOT_VERIFIED') {
                $result['extensions']['requires_email_verification'] = true;
            }
        }

        return $result;
    }

    /**
     * Código de error específico según tipo de excepción JWT
     */
    protected function getErrorCode(\Throwable $exception): string
    {
        if ($exception instanceof AuthenticationException) {
            return $exception->getErrorCode();
        }

        return ErrorCodeRegistry::UNAUTHENTICATED;
    }

    /**
     * Mensaje para desarrollo (específico y detallado)
     */
    protected function getDevelopmentMessage(\Throwable $exception): string
    {
        if ($exception instanceof AuthenticationException) {
            return $exception->getMessage();
        }

        return 'JWT Authentication failed: ' . $exception->getMessage();
    }

    /**
     * Mensaje para producción (user-friendly y genérico)
     */
    protected function getProductionMessage(\Throwable $exception): string
    {
        if ($exception instanceof AuthenticationException) {
            // Usar mensaje de la excepción si es user-friendly
            return $exception->getMessage();
        }

        return 'No estás autenticado. Por favor inicia sesión.';
    }

    /**
     * Obtiene el nombre del servicio para metadata
     */
    protected function getServiceName(): string
    {
        return 'JWT-Authentication-Service';
    }

    /**
     * Log específico para errores JWT con contexto adicional
     */
    protected function logError(\Throwable $exception, array $result): void
    {
        $logContext = [
            'handler' => static::class,
            'service' => $this->getServiceName(),
            'jwt_error_type' => $result['extensions']['jwt_error_type'] ?? 'UNKNOWN',
            'error_code' => $result['extensions']['code'] ?? 'UNKNOWN',
            'category' => $result['extensions']['category'] ?? 'unknown',
        ];

        // Agregar contexto específico según el tipo de error
        if ($exception instanceof AuthenticationException) {
            $logContext['action_required'] = 'investigate';
            $logContext['severity'] = 'warning';
        }

        EnvironmentErrorFormatter::logError($exception, $logContext);
    }
}
