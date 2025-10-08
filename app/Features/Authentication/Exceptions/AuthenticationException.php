<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\HelpdeskException;

/**
 * Excepción de autenticación específica del feature Authentication
 *
 * Lanzada cuando hay problemas relacionados con el proceso de autenticación.
 * Casos específicos: credenciales inválidas, email no verificado, cuenta suspendida.
 */
class AuthenticationException extends HelpdeskException
{
    protected string $category = 'authentication';
    protected string $errorCode = 'UNAUTHENTICATED';

    public static function notAuthenticated(): self
    {
        return new self('No estás autenticado. Por favor inicia sesión.');
    }

    public static function invalidToken(): self
    {
        return new self('Token de acceso inválido o expirado.');
    }

    public static function sessionExpired(): self
    {
        return new self('Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
    }

    public static function accountSuspended(string $reason = ''): self
    {
        $message = $reason
            ? "Tu cuenta ha sido suspendida. Razón: {$reason}"
            : 'Tu cuenta ha sido suspendida. Contacta al administrador.';

        return new self($message);
    }

    public static function accountDeleted(): self
    {
        return new self('Esta cuenta ha sido eliminada.');
    }

    public static function invalidProvider(string $provider): self
    {
        return new self("Proveedor de autenticación '{$provider}' no válido.");
    }
}
