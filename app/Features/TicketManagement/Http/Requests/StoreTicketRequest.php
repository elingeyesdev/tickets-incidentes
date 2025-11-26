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
            'company_id' => [
                'required',
                'uuid',
                function ($attribute, $value, $fail) {
                    $company = \App\Features\CompanyManagement\Models\Company::find($value);
                    if (!$company) {
                        $fail('La compañía seleccionada no existe.');
                    }
                },
            ],
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
            'priority' => [
                'sometimes',
                'string',
                function ($attribute, $value, $fail) {
                    if (!in_array($value, ['low', 'medium', 'high'])) {
                        $fail('La prioridad debe ser una de: low, medium, high.');
                    }
                },
            ],
            'area_id' => [
                'nullable',
                'uuid',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $area = \App\Features\CompanyManagement\Models\Area::find($value);
                        if (!$area) {
                            $fail('El área seleccionada no existe.');
                            return;
                        }
                        if (!$area->is_active) {
                            $fail('El área seleccionada no está activa.');
                            return;
                        }
                        if ($area->company_id !== $this->input('company_id')) {
                            $fail('El área no pertenece a la compañía seleccionada.');
                        }
                    }
                },
            ],
        ];
    }
}
