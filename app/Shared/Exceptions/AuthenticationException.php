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
    protected string $errorCode = 'UNAUTHENTICATED';

    public static function invalidCredentials(): self
    {
        $exception = new self('Credenciales inválidas. Verifica tu email y contraseña.');
        $exception->errorCode = 'INVALID_CREDENTIALS';
        return $exception;
    }

    public static function tokenExpired(): self
    {
        $exception = new self('Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
        $exception->errorCode = 'TOKEN_EXPIRED';
        return $exception;
    }

    public static function tokenInvalid(): self
    {
        $exception = new self('Token de acceso inválido.');
        $exception->errorCode = 'INVALID_TOKEN';
        return $exception;
    }

    public static function userNotFound(): self
    {
        $exception = new self('Usuario no encontrado.');
        $exception->errorCode = 'USER_NOT_FOUND';
        return $exception;
    }

    public static function accountSuspended(): self
    {
        $exception = new self('Tu cuenta está suspendida. Contacta al administrador.');
        $exception->errorCode = 'ACCOUNT_SUSPENDED';
        return $exception;
    }

    public static function emailNotVerified(): self
    {
        $exception = new self('Debes verificar tu email antes de continuar.');
        $exception->errorCode = 'EMAIL_NOT_VERIFIED';
        return $exception;
    }

    public static function unauthenticated(): self
    {
        $exception = new self('Authentication required: No valid token provided or token is invalid.');
        $exception->errorCode = 'UNAUTHENTICATED';
        return $exception;
    }
}
