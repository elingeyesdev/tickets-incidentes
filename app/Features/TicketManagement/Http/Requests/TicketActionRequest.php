<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests;

use App\Features\TicketManagement\Rules\CanReopenTicket;
use Illuminate\Foundation\Http\FormRequest;

class TicketActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketPolicy in controller
        return true;
    }

    public function rules(): array
    {
        $action = $this->getAction();
        $ticket = $this->route('ticket');

        return match ($action) {
            'resolve' => [
                'resolution_note' => 'nullable|string|max:5000',
            ],
            'close' => [
                'close_note' => 'nullable|string|max:5000',
            ],
            'reopen' => [
                'reopen_reason' => 'nullable|string|max:5000',
                'can_reopen' => [new CanReopenTicket($ticket)],
            ],
            'assign' => [
                'new_agent_id' => [
                    'required',
                    'uuid',
                    'exists:users,id',
                    function ($attribute, $value, $fail) use ($ticket) {
                        $agent = \App\Features\UserManagement\Models\User::find($value);

                        if (!$agent) {
                            $fail('El agente especificado no existe.');
                            return;
                        }

                        // Validar que tiene rol AGENT
                        if (!$agent->hasRoleInCompany('AGENT', $ticket->company_id)) {
                            $fail('El usuario no tiene rol de agente o pertenece a otra empresa.');
                            return;
                        }
                    },
                ],
                'assignment_note' => 'nullable|string|max:5000',
            ],
            default => [],
        };
    }

    /**
     * Determina la acción actual desde la ruta
     */
    private function getAction(): string
    {
        $route = $this->route();

        if ($route->getName()) {
            // Si la ruta tiene nombre como 'tickets.resolve', extraer 'resolve'
            $parts = explode('.', $route->getName());
            return end($parts);
        }

        // Fallback: obtener último segmento de URI
        $segments = $this->segments();
        return end($segments);
    }
}
