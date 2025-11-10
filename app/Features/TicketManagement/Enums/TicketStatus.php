<?php

namespace App\Features\TicketManagement\Enums;

/**
 * Enum de estados del ticket
 *
 * Ciclo de vida:
 * open -> pending -> resolved -> closed
 *
 * - open: Ticket recién creado, sin respuesta de agente
 * - pending: Ticket con al menos una respuesta de agente (auto-asignado)
 * - resolved: Ticket marcado como solucionado por el agente
 * - closed: Ticket cerrado definitivamente (manual o auto después de 7 días)
 */
enum TicketStatus: string
{
    case OPEN = 'OPEN';
    case PENDING = 'PENDING';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';

    /**
     * Obtiene todos los valores como array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verifica si el estado es activo (no cerrado)
     */
    public function isActive(): bool
    {
        return $this !== self::CLOSED;
    }

    /**
     * Verifica si el estado puede ser editado por el usuario
     */
    public function isEditableByUser(): bool
    {
        return $this === self::OPEN;
    }

    /**
     * Verifica si el ticket puede ser reabierto
     */
    public function canBeReopened(): bool
    {
        return in_array($this, [self::RESOLVED, self::CLOSED]);
    }

    /**
     * Verifica si el ticket puede ser calificado
     */
    public function canBeRated(): bool
    {
        return in_array($this, [self::RESOLVED, self::CLOSED]);
    }

    /**
     * Verifica si el ticket puede recibir respuestas
     */
    public function canReceiveResponses(): bool
    {
        return $this !== self::CLOSED;
    }
}
