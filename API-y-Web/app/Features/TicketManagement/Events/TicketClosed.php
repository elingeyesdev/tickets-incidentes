<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Events;

use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket
    ) {
    }
}
