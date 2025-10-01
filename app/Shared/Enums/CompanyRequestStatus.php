<?php

namespace App\Shared\Enums;

/**
 * Enum para estados de solicitudes de empresa
 *
 * Estados del flujo de aprobación de nuevas empresas.
 */
enum CompanyRequestStatus: string
{
    /**
     * Solicitud pendiente de revisión
     */
    case PENDING = 'pending';

    /**
     * Solicitud aprobada y empresa creada
     */
    case APPROVED = 'approved';

    /**
     * Solicitud rechazada con motivo
     */
    case REJECTED = 'rejected';

    /**
     * Obtiene el label legible para UI
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::APPROVED => 'Aprobada',
            self::REJECTED => 'Rechazada',
        };
    }

    /**
     * Verifica si la solicitud está pendiente
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Verifica si la solicitud fue aprobada
     */
    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Verifica si la solicitud fue rechazada
     */
    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Verifica si se puede modificar
     */
    public function canModify(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Obtiene color para UI (Tailwind classes)
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
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
            self::PENDING->value => self::PENDING->label(),
            self::APPROVED->value => self::APPROVED->label(),
            self::REJECTED->value => self::REJECTED->label(),
        ];
    }
}