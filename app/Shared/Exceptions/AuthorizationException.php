<?php

namespace App\Shared\Exceptions;

/**
 * Excepción de autorización
 *
 * Lanzada cuando un usuario autenticado no tiene permisos para una acción.
 */
class AuthorizationException extends HelpdeskException
{
    protected string $category = 'authorization';
    protected string $errorCode = 'FORBIDDEN';

    public static function insufficientPermissions(string $resource = ''): self
    {
        $message = $resource
            ? "No tienes permisos para acceder a: {$resource}"
            : 'No tienes permisos para realizar esta acción.';

        return new self($message);
    }

    public static function wrongCompanyContext(): self
    {
        return new self('No tienes acceso a esta empresa.');
    }

    public static function roleRequired(string $role): self
    {
        return new self("Esta acción requiere el rol: {$role}");
    }

    public static function companyRequired(): self
    {
        return new self('Esta acción requiere un contexto de empresa.');
    }
}