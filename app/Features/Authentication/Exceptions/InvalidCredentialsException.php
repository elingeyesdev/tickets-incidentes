<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\HelpdeskException;

/**
 * Excepción de credenciales inválidas
 *
 * Lanzada cuando el email/password proporcionados no coinciden
 * con ningún usuario en la base de datos.
 *
 * IMPORTANTE: El mensaje debe ser genérico para no revelar
 * si el email existe o no (seguridad).
 */
class InvalidCredentialsException extends HelpdeskException
{
    protected string $category = 'authentication';
    protected string $errorCode = 'INVALID_CREDENTIALS';

    public function __construct()
    {
        // Mensaje genérico por seguridad
        // NO revelar si el email existe o si solo la contraseña es incorrecta
        parent::__construct('Credenciales incorrectas. Verifica tu email y contraseña.');
    }

    public static function credentials(): self
    {
        return new self();
    }

    public static function tooManyAttempts(int $secondsToWait): self
    {
        $minutes = ceil($secondsToWait / 60);
        $exception = new self();
        $exception->message = "Demasiados intentos fallidos. Intenta nuevamente en {$minutes} minutos.";
        $exception->errorCode = 'TOO_MANY_ATTEMPTS';
        return $exception;
    }
}
