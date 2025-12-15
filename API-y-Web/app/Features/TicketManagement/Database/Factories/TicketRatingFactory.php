<?php

namespace App\Features\TicketManagement\Database\Factories;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketRating;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para TicketRating
 *
 * @extends Factory<TicketRating>
 */
class TicketRatingFactory extends Factory
{
    protected $model = TicketRating::class;

    /**
     * Definir el estado por defecto del modelo.
     */
    public function definition(): array
    {
        $rating = $this->faker->numberBetween(1, 5);

        $comments = [
            1 => ['Muy mala experiencia', 'No resolvieron mi problema', 'Demoran demasiado'],
            2 => ['No quedé satisfecho', 'Esperaba mejor servicio', 'El problema no se solucionó completamente'],
            3 => ['Servicio regular', 'Nada extraordinario', 'Aceptable'],
            4 => ['Buen servicio', 'Resolvieron mi problema', 'Agradecido por la ayuda'],
            5 => ['Excelente atención!', 'Muy rápidos y eficientes', 'Súper recomendado'],
        ];

        return [
            'ticket_id' => Ticket::factory()->resolved(),
            'customer_id' => User::factory(),
            'rated_agent_id' => User::factory(),
            'rating' => $rating,
            'comment' => $this->faker->optional(0.7)->randomElement($comments[$rating]),
            'created_at' => now(),
        ];
    }

    /**
     * Calificación para un ticket específico
     */
    public function forTicket(string $ticketId): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_id' => $ticketId,
        ]);
    }

    /**
     * Calificación de un cliente específico
     */
    public function by(string $customerId): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Calificación para un agente específico
     */
    public function forAgent(string $agentId): static
    {
        return $this->state(fn (array $attributes) => [
            'rated_agent_id' => $agentId,
        ]);
    }

    /**
     * Calificación positiva (4-5 estrellas)
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(4, 5),
            'comment' => $this->faker->randomElement([
                'Excelente atención!',
                'Muy rápidos y eficientes',
                'Resolvieron mi problema perfectamente',
                'Súper recomendado',
            ]),
        ]);
    }

    /**
     * Calificación negativa (1-2 estrellas)
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(1, 2),
            'comment' => $this->faker->randomElement([
                'Muy mala experiencia',
                'No resolvieron mi problema',
                'Demoran demasiado',
                'Pésimo servicio',
            ]),
        ]);
    }

    /**
     * Calificación con rating específico
     */
    public function withRating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }

    /**
     * Calificación sin comentario
     */
    public function withoutComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => null,
        ]);
    }

    /**
     * Calificación antigua (para testing de actualización)
     */
    public function old(int $hours = 48): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subHours($hours),
        ]);
    }
}
