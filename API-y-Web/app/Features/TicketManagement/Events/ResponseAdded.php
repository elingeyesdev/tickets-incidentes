<?php

namespace App\Features\TicketManagement\Events;

use App\Features\TicketManagement\Models\TicketResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: ResponseAdded
 *
 * Disparado cuando se crea una nueva respuesta en un ticket
 * Permite a listeners reaccionar (ej: enviar notificaciones, actualizar métricas)
 */
class ResponseAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public TicketResponse $response
    ) {
        //
    }
}
