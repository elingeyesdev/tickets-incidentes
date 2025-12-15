<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Exceptions\TicketNotRateableException;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketRating;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;

class RatingService
{
    /**
     * Crea una calificación para un ticket
     *
     * @param Ticket $ticket Ticket a calificar
     * @param User $user Usuario que califica (debe ser creador del ticket)
     * @param array $data Datos (rating, comment)
     * @return TicketRating
     * @throws TicketNotRateableException
     * @throws \Exception
     */
    public function createRating(Ticket $ticket, User $user, array $data): TicketRating
    {
        // Validar que el ticket puede ser calificado
        if (!in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED])) {
            throw new TicketNotRateableException(
                "Ticket must be resolved or closed to be rated. Current status: {$ticket->status->value}"
            );
        }

        // Validar que el usuario es el creador del ticket
        if ($ticket->created_by_user_id !== $user->id) {
            throw new TicketNotRateableException(
                "Only the ticket owner can rate this ticket."
            );
        }

        // Crear la calificación
        $rating = TicketRating::create([
            'ticket_id' => $ticket->id,
            'rated_by_user_id' => $user->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'rated_agent_id' => $ticket->owner_agent_id, // Snapshot del agente actual
        ]);

        return $rating;
    }

    /**
     * Obtiene la calificación de un ticket si existe
     *
     * @param Ticket $ticket
     * @return TicketRating|null
     */
    public function get(Ticket $ticket): ?TicketRating
    {
        return $ticket->rating;
    }
}
