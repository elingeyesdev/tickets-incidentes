<?php

namespace App\Features\TicketManagement\Jobs;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketPriority;
use App\Features\TicketManagement\Enums\TicketStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para auto-escalamiento de prioridad de tickets
 *
 * Si un ticket OPEN no recibe respuesta de agente en 24 horas,
 * se escala automáticamente la prioridad a HIGH.
 */
class EscalateTicketPriorityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de veces que el job puede ser reintentado
     */
    public int $tries = 3;

    /**
     * Timeout del job en segundos
     */
    public int $timeout = 30;

    /**
     * Constructor
     */
    public function __construct(public Ticket $ticket)
    {
        // Usar cola específica para auto-escalada
        $this->onQueue('default');
    }

    /**
     * Ejecutar el job
     */
    public function handle(): void
    {
        // Refrescar el ticket para obtener el estado más reciente
        $this->ticket->refresh();

        Log::info('EscalateTicketPriorityJob: Checking ticket', [
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'current_status' => $this->ticket->status->value,
            'current_priority' => $this->ticket->priority->value,
            'first_response_at' => $this->ticket->first_response_at,
        ]);

        // Verificar que el ticket sigue siendo OPEN
        if ($this->ticket->status !== TicketStatus::OPEN) {
            Log::info('EscalateTicketPriorityJob: Ticket no longer OPEN, skipping', [
                'ticket_code' => $this->ticket->ticket_code,
            ]);
            return;
        }

        // Verificar que no ha recibido respuesta de agente
        if ($this->ticket->first_response_at !== null) {
            Log::info('EscalateTicketPriorityJob: Ticket already has first response, skipping', [
                'ticket_code' => $this->ticket->ticket_code,
            ]);
            return;
        }

        // Verificar que no es ya HIGH
        if ($this->ticket->priority === TicketPriority::HIGH) {
            Log::info('EscalateTicketPriorityJob: Ticket already HIGH priority, skipping', [
                'ticket_code' => $this->ticket->ticket_code,
            ]);
            return;
        }

        // Escalar prioridad a HIGH
        $oldPriority = $this->ticket->priority;
        $this->ticket->update([
            'priority' => TicketPriority::HIGH,
        ]);

        Log::info('EscalateTicketPriorityJob: Priority escalated', [
            'ticket_code' => $this->ticket->ticket_code,
            'old_priority' => $oldPriority->value,
            'new_priority' => TicketPriority::HIGH->value,
        ]);
    }

    /**
     * Manejar fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('EscalateTicketPriorityJob: Failed', [
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'error' => $exception->getMessage(),
        ]);
    }
}
