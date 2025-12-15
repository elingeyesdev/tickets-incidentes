<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\HelpdeskException;

/**
 * Excepción de refresh token inválido
 *
 * Lanzada cuando el refresh token proporcionado:
 * - No existe en la base de datos
 * - Ha sido revocado
 * - No coincide con el hash almacenado
 * - Pertenece a otro usuario
 */
class InvalidRefreshTokenException extends HelpdeskException
{
    protected string $category = 'authentication';
    protected string $errorCode = 'INVALID_REFRESH_TOKEN';

    public function __construct(string $message = '')
    {
        $defaultMessage = 'Refresh token inválido. Por favor inicia sesión nuevamente.';
        parent::__construct($message ?: $defaultMessage);
    }

    public static function invalid(): self
    {
        return new self();
    }

    public static function revoked(): self
    {
        return new self('Refresh token revocado. Por favor inicia sesión nuevamente.');
    }

    public static function notFound(): self
    {
        return new self('Refresh token no encontrado. Por favor inicia sesión nuevamente.');
    }

    public static function mismatch(): self
    {
        return new self('Refresh token no coincide. Por favor inicia sesión nuevamente.');
    }
}
