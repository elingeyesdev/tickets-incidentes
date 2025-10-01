<?php

namespace App\Shared\Enums;

/**
 * Enum para estados de usuarios
 *
 * Estados posibles de un usuario en el sistema.
 * Usado en User model y validaciones.
 */
enum UserStatus: string
{
    /**
     * Usuario activo - puede acceder al sistema
     */
    case ACTIVE = 'active';

    /**
     * Usuario suspendido temporalmente - no puede acceder
     */
    case SUSPENDED = 'suspended';

    /**
     * Usuario eliminado (soft delete) - mantiene datos para auditoría
     */
    case DELETED = 'deleted';

    /**
     * Obtiene el label legible para UI
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Activo',
            self::SUSPENDED => 'Suspendido',
            self::DELETED => 'Eliminado',
        };
    }

    /**
     * Verifica si el usuario puede acceder al sistema
     */
    public function canAccess(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Verifica si el usuario está suspendido
     */
    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    /**
     * Verifica si el usuario está eliminado
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    /**
     * Obtiene color para UI (Tailwind classes)
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::SUSPENDED => 'yellow',
            self::DELETED => 'red',
        };
    }

    /**
     * Obtiene todos los estados como array
     *
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return [
            self::ACTIVE->value => self::ACTIVE->label(),
            self::SUSPENDED->value => self::SUSPENDED->label(),
            self::DELETED->value => self::DELETED->label(),
        ];
    }

    /**
     * Obtiene estados disponibles para transición desde el estado actual
     *
     * @return array<UserStatus>
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::ACTIVE => [self::SUSPENDED, self::DELETED],
            self::SUSPENDED => [self::ACTIVE, self::DELETED],
            self::DELETED => [], // No se puede reactivar un usuario eliminado
        };
    }

    /**
     * Verifica si se puede transicionar al estado dado
     */
    public function canTransitionTo(UserStatus $status): bool
    {
        return in_array($status, $this->allowedTransitions());
    }
}