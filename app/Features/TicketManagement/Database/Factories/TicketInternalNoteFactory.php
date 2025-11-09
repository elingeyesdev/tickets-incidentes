<?php

namespace App\Features\TicketManagement\Database\Factories;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketInternalNote;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para TicketInternalNote
 *
 * @extends Factory<TicketInternalNote>
 */
class TicketInternalNoteFactory extends Factory
{
    protected $model = TicketInternalNote::class;

    /**
     * Definir el estado por defecto del modelo.
     */
    public function definition(): array
    {
        $notes = [
            'Este usuario ya reportó un problema similar hace 2 meses.',
            'Escalé el caso al equipo de backend.',
            'El problema está relacionado con la versión de PostgreSQL.',
            'Ya coordiné con el cliente por teléfono.',
            'Ticket duplicado, revisar TKT-2025-00123',
            'Cliente VIP, dar prioridad.',
        ];

        return [
            'ticket_id' => Ticket::factory(),
            'agent_id' => User::factory(),
            'note_content' => $this->faker->randomElement($notes),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Nota para un ticket específico
     */
    public function forTicket(string $ticketId): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_id' => $ticketId,
        ]);
    }

    /**
     * Nota de un agente específico
     */
    public function by(string $agentId): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_id' => $agentId,
        ]);
    }

    /**
     * Nota con contenido personalizado
     */
    public function withContent(string $content): static
    {
        return $this->state(fn (array $attributes) => [
            'note_content' => $content,
        ]);
    }
}
