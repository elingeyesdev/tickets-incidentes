<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateCategoryRequest - Validación para actualizar categoría
 *
 * Reglas:
 * - name: opcional, 3-100 caracteres, único por empresa (excluyendo la categoría actual)
 * - description: opcional, máximo 500 caracteres
 * - is_active: opcional, boolean
 * - company_id: prohibido (inmutable)
 */
class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta petición
     */
    public function authorize(): bool
    {
        // La autorización real se hace en el controller con Policy
        return true;
    }

    /**
     * Reglas de validación
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Obtener ID de la categoría actual desde la ruta
        $categoryId = $this->route('id');

        // Obtener la categoría para validación de unicidad
        $category = \App\Features\TicketManagement\Models\Category::find($categoryId);

        $rules = [
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            // Prohibir cambio de company_id (inmutable)
            'company_id' => 'prohibited',
        ];

        // Agregar validación de nombre solo si la categoría existe
        // Esto previene errores cuando la categoría no se encuentra
        if ($category) {
            $rules['name'] = [
                'sometimes',
                'string',
                'min:3',
                'max:100',
                // Nombre único por empresa, excluyendo la categoría actual
                Rule::unique(\App\Features\TicketManagement\Models\Category::class, 'name')
                    ->where('company_id', $category->company_id)
                    ->ignore($categoryId),
            ];
        }

        return $rules;
    }

    /**
     * Mensajes de error personalizados
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.min' => 'The category name must be at least 3 characters',
            'name.max' => 'The category name must not exceed 100 characters',
            'name.unique' => 'A category with this name already exists in your company',
            'description.max' => 'The description must not exceed 500 characters',
            'is_active.boolean' => 'The is_active field must be true or false',
        ];
    }
}
