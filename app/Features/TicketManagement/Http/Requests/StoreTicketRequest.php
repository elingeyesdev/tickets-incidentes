<?php

namespace App\Features\TicketManagement\Http\Requests;

use App\Features\TicketManagement\Models\Category;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return JWTHelper::hasRoleFromJWT('USER');
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:5|max:200',
            'description' => 'required|string|min:10|max:2000',
            'company_id' => 'required|uuid|exists:companies,id',
            'category_id' => [
                'required',
                'uuid',
                function ($attribute, $value, $fail) {
                    $category = Category::find($value);
                    if (!$category) {
                        $fail('La categoría seleccionada no existe.');
                        return;
                    }
                    if (!$category->is_active) {
                        $fail('La categoría seleccionada no está activa.');
                        return;
                    }
                    if ($category->company_id !== $this->input('company_id')) {
                        $fail('La categoría no pertenece a la compañía seleccionada.');
                    }
                },
            ],
        ];
    }
}
