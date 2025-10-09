<?php

namespace App\Shared\Exceptions;

/**
 * Excepción de autenticación (401)
 *
 * Lanzada cuando falla la autenticación del usuario.
 * Usada por el sistema de autenticación JWT y resolvers GraphQL.
 *
 * Esta excepción es específica para errores de autenticación:
 * - Credenciales inválidas
 * - Token JWT expirado o inválido
 * - Usuario no encontrado
 * - Cuenta suspendida
 */
class AuthenticationException extends HelpdeskException
{
    protected string $category = 'authentication';
    protected string $errorCode = 'AUTHENTICATION_FAILED';

    public static function invalidCredentials(): self
    {
        return new self('Credenciales inválidas. Verifica tu email y contraseña.');
    }

    public static function tokenExpired(): self
    {
        return new self('Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
    }

    public static function tokenInvalid(): self
    {
        return new self('Token de acceso inválido.');
    }

    public static function userNotFound(): self
    {
        return new self('Usuario no encontrado.');
    }

    public static function accountSuspended(): self
    {
        return new self('Tu cuenta está suspendida. Contacta al administrador.');
    }

    public static function emailNotVerified(): self
    {
        return new self('Debes verificar tu email antes de continuar.');
    }
}
