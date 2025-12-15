<?php

namespace App\Features\TicketManagement\Rules;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CanReopenTicket implements ValidationRule
{
    private Ticket $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Solo se pueden reabrir tickets resolved o closed
        if (!in_array($this->ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED])) {
            $fail('Solo se pueden reabrir tickets resueltos o cerrados.');
            return;
        }

        // Si es agent, puede reabrir siempre
        if (JWTHelper::hasRoleFromJWT('AGENT')) {
            return;
        }

        // Si es user, validar 30 días
        if ($this->ticket->status === TicketStatus::CLOSED && $this->ticket->closed_at) {
            $daysSinceClosed = Carbon::parse($this->ticket->closed_at)->diffInDays(Carbon::now());

            if ($daysSinceClosed > 30) {
                $fail('No puedes reabrir un ticket cerrado después de 30 días.');
                return;
            }
        }
    }
}
