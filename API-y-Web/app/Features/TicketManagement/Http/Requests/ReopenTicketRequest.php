<?php

namespace App\Features\TicketManagement\Http\Requests;

use App\Features\TicketManagement\Rules\CanReopenTicket;
use Illuminate\Foundation\Http\FormRequest;

class ReopenTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketPolicy
        return true;
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');

        return [
            'reason' => 'nullable|string|max:500',
            'ticket_status' => [new CanReopenTicket($ticket)],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Add dummy field for CanReopenTicket rule validation
        $this->merge(['ticket_status' => 'check']);
    }
}
