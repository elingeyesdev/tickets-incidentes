<?php

namespace App\Shared\Exceptions;

/**
 * Excepción de prohibido (403)
 *
 * Lanzada cuando el usuario está autenticado pero no tiene permisos
 * para realizar la acción solicitada.
 */
class ForbiddenException extends HelpdeskException
{
    protected string $category = 'authorization';
    protected string $errorCode = 'FORBIDDEN';

    public static function insufficientPermissions(string $action = ''): self
    {
        $message = $action
            ? "No tienes permisos para: {$action}"
            : 'No tienes permisos para realizar esta acción.';

        return new self($message);
    }

    public static function wrongContext(string $context = 'empresa'): self
    {
        return new self("No tienes acceso a este {$context}.");
    }

    public static function roleRequired(string $role): self
    {
        return new self("Esta acción requiere el rol: {$role}");
    }

    public static function featureDisabled(string $feature): self
    {
        return new self("La funcionalidad '{$feature}' está deshabilitada.");
    }

    public static function resourceLocked(string $resource): self
    {
        return new self("{$resource} está bloqueado y no puede ser modificado.");
    }
}
