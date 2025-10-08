<?php

namespace App\Shared\Exceptions;

/**
 * Excepción de no autorizado (401)
 *
 * Lanzada cuando el usuario no está autenticado.
 * Diferencia con AuthenticationException: esta es más genérica (Shared),
 * mientras que AuthenticationException del feature tiene lógica específica.
 */
class UnauthorizedException extends HelpdeskException
{
    protected string $category = 'authentication';
    protected string $errorCode = 'UNAUTHORIZED';

    public static function notAuthenticated(): self
    {
        return new self('No estás autenticado. Por favor inicia sesión.');
    }

    public static function tokenExpired(): self
    {
        return new self('Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
    }

    public static function tokenInvalid(): self
    {
        return new self('Token de acceso inválido.');
    }

    public static function tokenMissing(): self
    {
        return new self('Token de acceso requerido.');
    }
}
