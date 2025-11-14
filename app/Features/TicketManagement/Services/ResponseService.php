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
     * @param array $data Datos de la respuesta (ticket_id, author_id, response_content)
     * @return TicketResponse
     */
    public function create(array $data): TicketResponse
    {
        // Buscar el ticket
        $ticket = Ticket::findOrFail($data['ticket_id']);

        // Buscar el usuario/autor
        $author = User::findOrFail($data['author_id']);

        // Determinar autor_type automáticamente
        $authorType = $this->determineAuthorType($author);

        // Crear la respuesta
        $response = TicketResponse::create([
            'ticket_id' => $data['ticket_id'],
            'author_id' => $data['author_id'],
            'content' => $data['response_content'],
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
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Actualiza una respuesta
     *
     * @param TicketResponse $response Respuesta a actualizar
     * @param array $data Datos a actualizar (response_content)
     * @return TicketResponse
     */
    public function update(TicketResponse $response, array $data): TicketResponse
    {
        $updateData = [];

        if (isset($data['response_content'])) {
            $updateData['content'] = $data['response_content'];
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
        // Si tiene rol AGENT, es autor tipo AGENT
        if (JWTHelper::hasRoleFromJWT('AGENT')) {
            return AuthorType::AGENT;
        }

        // De lo contrario, es USER
        return AuthorType::USER;
    }
}
