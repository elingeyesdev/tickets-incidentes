<?php

namespace App\Features\TicketManagement\Listeners;

use App\Features\TicketManagement\Events\ResponseAdded;
use App\Features\TicketManagement\Jobs\SendTicketResponseEmailJob;

/**
 * Send Ticket Response Email Listener
 *
 * Escucha el evento ResponseAdded y dispara el job de envío de email.
 *
 * Lógica:
 * 1. Si la respuesta es de un AGENTE (author_type === 'agent')
 *    → Enviar email al usuario del ticket notificándolo de la respuesta
 * 2. Si la respuesta es del USUARIO (author_type === 'user')
 *    → NO enviar email (el usuario ya lo sabe porque él respondió)
 *
 * Se ejecuta sincrónicamente (dentro del request) pero dispara un job
 * asincrónico para que el email se envíe en background via Redis queue.
 *
 * Por eso es rápido: el listener solo verifica el tipo de respuesta
 * y lanza el job. El job pesado se ejecuta en un worker separado.
 */
class SendTicketResponseEmail
{
    /**
     * Handle the event.
     *
     * @param ResponseAdded $event El evento disparado cuando se agrega una respuesta
     */
    public function handle(ResponseAdded $event): void
    {
        $response = $event->response;
        $ticket = $response->ticket;

        \Log::debug('SendTicketResponseEmail listener: Handling ResponseAdded event', [
            'response_id' => $response->id,
            'ticket_id' => $ticket->id,
            'author_type' => $response->author_type->value,
            'author_id' => $response->author_id,
        ]);

        // Solo enviar email si la respuesta es de un AGENTE
        // Si es del usuario, no hacemos nada (él ya sabe que respondió)
        if (!$response->isFromAgent()) {
            \Log::debug('SendTicketResponseEmail listener: Skipping - response is from user, not agent', [
                'response_id' => $response->id,
                'ticket_id' => $ticket->id,
            ]);
            return;
        }

        \Log::debug('SendTicketResponseEmail listener: Response is from agent - will send email', [
            'response_id' => $response->id,
            'ticket_id' => $ticket->id,
            'agent_id' => $response->author_id,
        ]);

        // Obtener el usuario que creó el ticket (destinatario del email)
        $recipient = $ticket->creator;

        // Obtener el agente que respondió
        $agent = $response->author;

        // Validar que tenemos todos los datos necesarios
        if (!$recipient || !$agent) {
            \Log::warning('SendTicketResponseEmail listener: Missing recipient or agent', [
                'response_id' => $response->id,
                'ticket_id' => $ticket->id,
                'has_recipient' => (bool) $recipient,
                'has_agent' => (bool) $agent,
            ]);
            return;
        }

        // Validar que el agente no es el mismo que el usuario (edge case)
        if ($recipient->id === $agent->id) {
            \Log::debug('SendTicketResponseEmail listener: Agent and recipient are the same - skipping email', [
                'response_id' => $response->id,
                'ticket_id' => $ticket->id,
                'user_id' => $recipient->id,
            ]);
            return;
        }

        \Log::debug('SendTicketResponseEmail listener: Dispatching SendTicketResponseEmailJob', [
            'response_id' => $response->id,
            'ticket_id' => $ticket->id,
            'recipient_email' => $recipient->email,
            'agent_email' => $agent->email,
        ]);

        // Disparar el job asincrónico para enviar el email
        SendTicketResponseEmailJob::dispatch(
            $ticket,
            $response,
            $recipient,
            $agent
        );

        \Log::debug('SendTicketResponseEmail listener: Job dispatched successfully', [
            'response_id' => $response->id,
            'ticket_id' => $ticket->id,
        ]);
    }
}