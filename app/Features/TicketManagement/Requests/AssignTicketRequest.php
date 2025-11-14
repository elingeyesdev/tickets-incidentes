<?php

namespace App\Features\TicketManagement\Requests;

use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketPolicy
        return true;
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');
        $companyId = $ticket->company_id;

        return [
            'new_agent_id' => [
                'required',
                'uuid',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($companyId) {
                    $agent = User::find($value);
                    if (!$agent) {
                        $fail('El agente especificado no existe.');
                        return;
                    }

                    // Validar que tiene rol AGENT en la compañía correcta
                    $hasAgentRole = collect($agent->roles)
                        ->contains(function ($role) use ($companyId) {
                            return $role->code === 'AGENT' && $role->pivot->company_id === $companyId;
                        });

                    if (!$hasAgentRole) {
                        $fail('El usuario especificado no es un agente de esta compañía.');
                    }
                },
            ],
            'note' => 'nullable|string|max:500',
        ];
    }
}
