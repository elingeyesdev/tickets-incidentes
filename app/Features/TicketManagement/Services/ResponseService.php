<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Enums\AuthorType;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResponseService
{
    /**
     * Crea una nueva respuesta en un ticket
     *
     * @param Ticket $ticket Ticket al que se agregará la respuesta
     * @param array $data Datos de la respuesta (content)
     * @param User $user Usuario que crea la respuesta
     * @return TicketResponse
     */
    public function create(Ticket $ticket, array $data, User $user): TicketResponse
    {
        // Determinar autor_type automáticamente
        $authorType = $this->determineAuthorType($user);

        // Crear la respuesta
        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'content' => $data['content'],
            'author_type' => $authorType->value,
        ]);

        return $response;
    }

    /**
     * Lista todas las respuestas de un ticket ordenadas por created_at ASC
     *
     * @param Ticket $ticket
     * @return Collection
     */
    public function list(Ticket $ticket): Collection
    {
        return $ticket->responses()
            ->with(['author.profile', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Actualiza una respuesta
     *
     * @param TicketResponse $response Respuesta a actualizar
     * @param array $data Datos a actualizar (content)
     * @return TicketResponse
     */
    public function update(TicketResponse $response, array $data): TicketResponse
    {
        $updateData = [];

        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }

        $response->update($updateData);
        return $response;
    }

    /**
     * Elimina una respuesta
     *
     * @param TicketResponse $response Respuesta a eliminar
     * @return bool
     */
    public function delete(TicketResponse $response): bool
    {
        return $response->delete();
    }

    /**
     * Determina el tipo de autor basándose en el rol del usuario
     *
     * @param User $user Usuario que responde
     * @return AuthorType
     */
    private function determineAuthorType(User $user): AuthorType
    {
        // MIGRADO: Usar el rol ACTIVO del usuario
        $activeRole = JWTHelper::getActiveRoleCode();
        
        if ($activeRole === 'AGENT') {
            return AuthorType::AGENT;
        }

        // De lo contrario, es USER
        return AuthorType::USER;
    }
}
