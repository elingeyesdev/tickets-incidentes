<?php

namespace App\Features\TicketManagement\Requests;

use App\Features\TicketManagement\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
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
            'title' => 'sometimes|required|string|min:5|max:200',
            'category_id' => [
                'sometimes',
                'required',
                'uuid',
                function ($attribute, $value, $fail) use ($ticket) {
                    $category = Category::find($value);
                    if (!$category) {
                        $fail('La categoría seleccionada no existe.');
                        return;
                    }
                    if (!$category->is_active) {
                        $fail('La categoría seleccionada no está activa.');
                        return;
                    }
                    if ($category->company_id !== $ticket->company_id) {
                        $fail('La categoría no pertenece a la compañía del ticket.');
                    }
                },
            ],
        ];
    }
}
