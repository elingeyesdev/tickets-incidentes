<?php

namespace App\Shared\Exceptions;

/**
 * Excepción de autenticación
 *
 * Lanzada cuando un usuario no está autenticado o su token es inválido.
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

    public static function accountSuspended(): self
    {
        return new self('Tu cuenta ha sido suspendida. Contacta al administrador.');
    }

    public static function accountDeleted(): self
    {
        return new self('Esta cuenta ha sido eliminada.');
    }
}