<?php

namespace App\Shared\Enums;

/**
 * Enum para estados de empresas
 *
 * Estados posibles de una empresa en el sistema multi-tenant.
 */
enum CompanyStatus: string
{
    /**
     * Empresa activa - puede operar normalmente
     */
    case ACTIVE = 'active';

    /**
     * Empresa suspendida - operaciones bloqueadas temporalmente
     */
    case SUSPENDED = 'suspended';

    /**
     * Obtiene el label legible para UI
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Activa',
            self::SUSPENDED => 'Suspendida',
        };
    }

    /**
     * Verifica si la empresa puede operar
     */
    public function canOperate(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Verifica si la empresa está suspendida
     */
    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    /**
     * Obtiene color para UI (Tailwind classes)
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::SUSPENDED => 'red',
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
        ];
    }
}