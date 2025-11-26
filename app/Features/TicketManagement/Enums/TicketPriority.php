<?php

namespace App\Features\TicketManagement\Enums;

/**
 * Enum de prioridades del ticket
 *
 * Prioridades:
 * - low: Baja prioridad (no urgente)
 * - medium: Prioridad media (default)
 * - high: Alta prioridad (requiere atención pronta)
 */
enum TicketPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    /**
     * Obtiene todos los valores como array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verifica si la prioridad es alta
     */
    public function isHigh(): bool
    {
        return $this === self::HIGH;
    }

    /**
     * Obtiene el peso numérico para ordenamiento
     */
    public function order(): int
    {
        return match($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
        };
    }

    /**
     * Obtiene el label legible
     */
    public function label(): string
    {
        return match($this) {
            self::LOW => 'Baja',
            self::MEDIUM => 'Media',
            self::HIGH => 'Alta',
        };
    }
}
