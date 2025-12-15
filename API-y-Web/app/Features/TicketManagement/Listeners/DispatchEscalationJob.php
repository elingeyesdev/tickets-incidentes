<?php

namespace App\Features\TicketManagement\Listeners;

use App\Features\TicketManagement\Events\TicketCreated;
use App\Features\TicketManagement\Jobs\EscalateTicketPriorityJob;
use Illuminate\Support\Facades\Log;

/**
 * Listener para disparar auto-escalamiento de prioridad
 *
 * Cuando se crea un ticket, programa un job para 24 horas después
 * que verificará si el ticket necesita escalamiento de prioridad.
 */
class DispatchEscalationJob
{
    /**
     * Handle the event
     */
    public function handle(TicketCreated $event): void
    {
        Log::debug('DispatchEscalationJob: Scheduling escalation check', [
            'ticket_id' => $event->ticket->id,
            'ticket_code' => $event->ticket->ticket_code,
            'scheduled_for' => now()->addHours(24)->toIso8601String(),
        ]);

        // Disparar job que se ejecuta en 24 horas
        EscalateTicketPriorityJob::dispatch($event->ticket)
            ->delay(now()->addHours(24));
    }
}
