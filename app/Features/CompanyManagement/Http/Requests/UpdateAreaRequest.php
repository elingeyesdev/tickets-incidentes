<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateAreaRequest - Validación para actualizar área/departamento
 *
 * Reglas:
 * - name: opcional, 3-100 caracteres, único por empresa (excluyendo el área actual)
 * - description: opcional, máximo 500 caracteres
 * - is_active: opcional, boolean
 * - company_id: prohibido (inmutable)
 */
class UpdateAreaRequest extends FormRequest
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
        // Obtener ID del área actual desde la ruta
        $areaId = $this->route('id');

        // Obtener el área para validación de unicidad
        $area = \App\Features\CompanyManagement\Models\Area::find($areaId);

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

        // Agregar validación de nombre solo si el área existe
        // Esto previene errores cuando el área no se encuentra
        if ($area) {
            $rules['name'] = [
                'sometimes',
                'string',
                'min:3',
                'max:100',
                // Nombre único por empresa, excluyendo el área actual
                Rule::unique(\App\Features\CompanyManagement\Models\Area::class, 'name')
                    ->where('company_id', $area->company_id)
                    ->ignore($areaId),
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
            'name.min' => 'The area name must be at least 3 characters',
            'name.max' => 'The area name must not exceed 100 characters',
            'name.unique' => 'An area with this name already exists in your company',
            'description.max' => 'The description must not exceed 500 characters',
            'is_active.boolean' => 'The is_active field must be true or false',
        ];
    }
}
