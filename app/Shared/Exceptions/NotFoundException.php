<?php

namespace App\Shared\Exceptions;

/**
 * Excepción de recurso no encontrado
 *
 * Lanzada cuando un recurso solicitado no existe.
 */
class NotFoundException extends HelpdeskException
{
    protected string $category = 'not_found';
    protected string $errorCode = 'NOT_FOUND';

    public static function resource(string $resource, string $id = ''): self
    {
        $message = $id
            ? "{$resource} con ID '{$id}' no encontrado."
            : "{$resource} no encontrado.";

        return new self($message);
    }

    public static function user(string $id = ''): self
    {
        return self::resource('Usuario', $id);
    }

    public static function company(string $id = ''): self
    {
        return self::resource('Empresa', $id);
    }

    public static function ticket(string $id = ''): self
    {
        return self::resource('Ticket', $id);
    }

    public static function role(string $id = ''): self
    {
        return self::resource('Rol', $id);
    }
}