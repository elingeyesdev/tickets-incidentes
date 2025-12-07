<?php

declare(strict_types=1);

namespace App\Features\AuditLog\Listeners;

use App\Features\AuditLog\Services\ActivityLogService;
use App\Features\TicketManagement\Events\TicketCreated;
use App\Features\TicketManagement\Events\TicketResolved;
use App\Features\TicketManagement\Events\TicketClosed;
use App\Features\TicketManagement\Events\TicketReopened;
use App\Features\TicketManagement\Events\TicketAssigned;
use App\Features\TicketManagement\Events\ResponseAdded;

/**
 * LogTicketActivity
 *
 * Listener para registrar actividad de tickets.
 */
class LogTicketActivity
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    /**
     * Registrar creación de ticket
     */
    public function handleTicketCreated(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        $this->activityLogService->logTicketCreated(
            userId: $ticket->created_by_user_id,
            ticketId: $ticket->id,
            ticketData: [
                'ticket_code' => $ticket->ticket_code,
                'title' => $ticket->title,
                'status' => $ticket->status->value,
                'priority' => $ticket->priority?->value,
                'category_id' => $ticket->category_id,
                'company_id' => $ticket->company_id,
            ]
        );
    }

    /**
     * Registrar resolución de ticket
     */
    public function handleTicketResolved(TicketResolved $event): void
    {
        $ticket = $event->ticket;

        // El usuario que resolvió es el owner_agent (o quien ejecutó la acción)
        $userId = auth()->id() ?? $ticket->owner_agent_id;

        $this->activityLogService->logTicketResolved(
            userId: $userId,
            ticketId: $ticket->id
        );
    }

    /**
     * Registrar cierre de ticket
     */
    public function handleTicketClosed(TicketClosed $event): void
    {
        $ticket = $event->ticket;

        $userId = auth()->id() ?? $ticket->owner_agent_id ?? $ticket->created_by_user_id;

        $this->activityLogService->logTicketClosed(
            userId: $userId,
            ticketId: $ticket->id
        );
    }

    /**
     * Registrar reapertura de ticket
     */
    public function handleTicketReopened(TicketReopened $event): void
    {
        $ticket = $event->ticket;

        $userId = auth()->id() ?? $ticket->created_by_user_id;

        $this->activityLogService->logTicketReopened(
            userId: $userId,
            ticketId: $ticket->id
        );
    }

    /**
     * Registrar asignación de ticket
     */
    public function handleTicketAssigned(TicketAssigned $event): void
    {
        $ticket = $event->ticket;

        $userId = auth()->id();

        $this->activityLogService->logTicketAssigned(
            userId: $userId,
            ticketId: $ticket->id,
            agentId: $ticket->owner_agent_id
        );
    }

    /**
     * Registrar respuesta agregada
     */
    public function handleResponseAdded(ResponseAdded $event): void
    {
        $response = $event->response;
        $ticket = $response->ticket;

        $this->activityLogService->logTicketResponseAdded(
            userId: $response->author_id,
            ticketId: $ticket->id,
            responseId: $response->id
        );
    }
}
