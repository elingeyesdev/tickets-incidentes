<?php

namespace App\Features\TicketManagement\Enums;

/**
 * Enum de tipos de autor de respuestas
 *
 * Utilizado para diferenciar quién responde en la conversación del ticket:
 * - user: Respuesta del cliente (created_by_user_id)
 * - agent: Respuesta del agente de soporte
 */
enum AuthorType: string
{
    case USER = 'user';
    case AGENT = 'agent';

    /**
     * Obtiene todos los valores como array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verifica si el autor es un agente
     */
    public function isAgent(): bool
    {
        return $this === self::AGENT;
    }

    /**
     * Verifica si el autor es un usuario
     */
    public function isUser(): bool
    {
        return $this === self::USER;
    }

    /**
     * Obtiene el tipo de autor desde el rol del usuario
     */
    public static function fromRole(string $role): self
    {
        return match($role) {
            'agent', 'company_admin' => self::AGENT,
            default => self::USER,
        };
    }
}
