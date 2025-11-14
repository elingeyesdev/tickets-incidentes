<?php

namespace App\Features\TicketManagement\Database\Factories;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para TicketAttachment
 *
 * @extends Factory<TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    protected $model = TicketAttachment::class;

    /**
     * Definir el estado por defecto del modelo.
     */
    public function definition(): array
    {
        $fileTypes = [
            ['extension' => 'pdf', 'mime' => 'application/pdf', 'size' => [100000, 2000000]],
            ['extension' => 'png', 'mime' => 'image/png', 'size' => [50000, 1000000]],
            ['extension' => 'jpg', 'mime' => 'image/jpeg', 'size' => [50000, 1500000]],
            ['extension' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'size' => [20000, 500000]],
            ['extension' => 'xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'size' => [15000, 800000]],
            ['extension' => 'txt', 'mime' => 'text/plain', 'size' => [1000, 50000]],
        ];

        $fileType = $this->faker->randomElement($fileTypes);
        $fileName = $this->faker->word() . '-' . $this->faker->numerify('####') . '.' . $fileType['extension'];

        return [
            'ticket_id' => Ticket::factory(),
            'response_id' => null,  // Por defecto, adjunto al ticket directamente
            'uploaded_by_user_id' => User::factory(),
            'file_name' => $fileName,
            'file_path' => 'storage/tickets/attachments/' . $this->faker->uuid() . '.' . $fileType['extension'],
            'file_type' => $fileType['mime'],
            'file_size_bytes' => $this->faker->numberBetween($fileType['size'][0], $fileType['size'][1]),
            'created_at' => now(),
        ];
    }

    /**
     * Adjunto para un ticket específico
     */
    public function forTicket(string $ticketId): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_id' => $ticketId,
        ]);
    }

    /**
     * Adjunto para una respuesta específica
     */
    public function forResponse(string $responseId): static
    {
        return $this->state(fn (array $attributes) => [
            'response_id' => $responseId,
        ]);
    }

    /**
     * Adjunto subido por un usuario específico
     */
    public function uploadedBy(string $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_by_user_id' => $userId,
        ]);
    }

    /**
     * Adjunto de tipo imagen
     */
    public function image(): static
    {
        $extensions = ['png', 'jpg', 'gif'];
        $ext = $this->faker->randomElement($extensions);

        return $this->state(fn (array $attributes) => [
            'file_name' => 'screenshot-' . $this->faker->numerify('####') . '.' . $ext,
            'file_type' => 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext),
            'file_size_bytes' => $this->faker->numberBetween(100000, 2000000),
        ]);
    }

    /**
     * Adjunto de tipo PDF
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => 'document-' . $this->faker->numerify('####') . '.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => $this->faker->numberBetween(200000, 3000000),
        ]);
    }

    /**
     * Adjunto antiguo (para testing de eliminación)
     */
    public function old(int $minutes = 60): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subMinutes($minutes),
        ]);
    }
}
