<?php

namespace App\Shared\Exceptions;

/**
 * Excepción de conflicto (409)
 *
 * Lanzada cuando hay un conflicto con el estado actual del recurso.
 * Ejemplos: email duplicado, username ya existe, estado inválido para la operación.
 */
class ConflictException extends HelpdeskException
{
    protected string $category = 'conflict';
    protected string $errorCode = 'CONFLICT';

    public static function duplicateEmail(string $email): self
    {
        return new self("El email '{$email}' ya está registrado en el sistema.");
    }

    public static function duplicateField(string $field, string $value): self
    {
        return new self("El {$field} '{$value}' ya existe.");
    }

    public static function alreadyExists(string $resource): self
    {
        return new self("{$resource} ya existe.");
    }

    public static function invalidState(string $resource, string $currentState, string $attemptedAction): self
    {
        return new self(
            "{$resource} está en estado '{$currentState}' y no puede realizar la acción: {$attemptedAction}"
        );
    }

    public static function concurrentModification(string $resource): self
    {
        return new self(
            "{$resource} fue modificado por otro usuario. Por favor recarga y vuelve a intentar."
        );
    }
}
