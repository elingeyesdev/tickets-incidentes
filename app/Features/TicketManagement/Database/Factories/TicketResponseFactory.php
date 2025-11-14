<?php

namespace App\Features\TicketManagement\Database\Factories;

use App\Features\TicketManagement\Enums\AuthorType;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para TicketResponse
 *
 * @extends Factory<TicketResponse>
 */
class TicketResponseFactory extends Factory
{
    protected $model = TicketResponse::class;

    /**
     * Definir el estado por defecto del modelo.
     */
    public function definition(): array
    {
        $responses = [
            'user' => [
                'Gracias por la respuesta. ¿Cuándo estará solucionado?',
                'Sí, ya probé eso pero sigue sin funcionar.',
                'Entiendo, pero el problema persiste.',
                'Muchas gracias por su ayuda.',
                'Sigo teniendo el mismo error.',
            ],
            'agent' => [
                'Hola, gracias por contactarnos. Estoy revisando tu caso.',
                'He identificado el problema. Procederé a solucionarlo.',
                'Ya está solucionado. Por favor prueba nuevamente.',
                '¿Puedes enviarme más detalles del error?',
                'He escalado tu caso al equipo técnico.',
            ],
        ];

        $authorType = $this->faker->randomElement([AuthorType::USER, AuthorType::AGENT]);

        return [
            'ticket_id' => Ticket::factory(),
            'author_id' => User::factory(),
            'content' => $this->faker->randomElement($responses[$authorType->value]),
            'author_type' => $authorType,
            'created_at' => now(),
        ];
    }

    /**
     * Respuesta de usuario
     */
    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_type' => AuthorType::USER,
        ]);
    }

    /**
     * Respuesta de agente
     */
    public function fromAgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_type' => AuthorType::AGENT,
        ]);
    }

    /**
     * Respuesta para un ticket específico
     */
    public function forTicket(string $ticketId): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_id' => $ticketId,
        ]);
    }

    /**
     * Respuesta de un autor específico
     */
    public function by(string $userId, AuthorType $authorType): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => $userId,
            'author_type' => $authorType,
        ]);
    }

    /**
     * Respuesta antigua (para testing de edición)
     */
    public function old(int $minutes = 60): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subMinutes($minutes),
        ]);
    }
}
