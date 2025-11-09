<?php

namespace App\Features\TicketManagement\Database\Factories;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para Ticket
 *
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Definir el estado por defecto del modelo.
     */
    public function definition(): array
    {
        // Generar código de ticket único
        $uniqueNumber = fake()->unique()->numberBetween(1, 99999);
        $year = now()->year;
        $ticketCode = CodeGenerator::format(CodeGenerator::TICKET, $year, $uniqueNumber);

        $titles = [
            'No puedo acceder a mi cuenta',
            'Error al exportar reportes a Excel',
            'Problema con reseteo de contraseña',
            'Consulta sobre facturación',
            'El sistema está lento',
            'No recibo notificaciones por email',
            'Error 500 al crear nuevo usuario',
            'Duda sobre permisos de agentes',
        ];

        $descriptions = [
            'Hola, necesito ayuda urgente con este problema. He intentado varias veces pero no funciona. ¿Pueden ayudarme?',
            'Buenos días, vengo experimentando este inconveniente desde ayer. Adjunto capturas de pantalla.',
            'Estimados, por favor necesito asistencia con este tema. Es importante para nuestro trabajo diario.',
            'Hola equipo de soporte, tengo la siguiente consulta: ',
        ];

        return [
            'ticket_code' => $ticketCode,
            'created_by_user_id' => User::factory(),
            'company_id' => Company::factory(),
            'category_id' => Category::factory(),
            'title' => $this->faker->randomElement($titles),
            'initial_description' => $this->faker->randomElement($descriptions) . ' ' . $this->faker->realText(200),
            'status' => TicketStatus::OPEN,
            'owner_agent_id' => null,  // NULL inicialmente, se asigna con trigger
            'created_at' => now(),
            'updated_at' => now(),
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    /**
     * Ticket en estado pending (con agente asignado)
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::PENDING,
            'owner_agent_id' => User::factory(),
            'first_response_at' => now()->subHours(2),
        ]);
    }

    /**
     * Ticket en estado resolved
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::RESOLVED,
            'owner_agent_id' => User::factory(),
            'first_response_at' => now()->subDays(3),
            'resolved_at' => now()->subDay(),
        ]);
    }

    /**
     * Ticket en estado closed
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::CLOSED,
            'owner_agent_id' => User::factory(),
            'first_response_at' => now()->subDays(10),
            'resolved_at' => now()->subDays(8),
            'closed_at' => now()->subDay(),
        ]);
    }

    /**
     * Ticket para una empresa específica
     */
    public function forCompany(string $companyId): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $companyId,
        ]);
    }

    /**
     * Ticket creado por un usuario específico
     */
    public function createdBy(string $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by_user_id' => $userId,
        ]);
    }

    /**
     * Ticket asignado a un agente específico
     */
    public function ownedBy(string $agentId): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_agent_id' => $agentId,
            'status' => TicketStatus::PENDING,
            'first_response_at' => now()->subHour(),
        ]);
    }

    /**
     * Ticket con categoría específica
     */
    public function inCategory(string $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }

    /**
     * Ticket antiguo (para testing de auto-close)
     */
    public function old(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDays($days),
            'updated_at' => now()->subDays($days),
        ]);
    }
}
