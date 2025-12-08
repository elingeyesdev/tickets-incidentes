<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreCategoryRequest - Validación para crear categoría
 *
 * Reglas:
 * - name: requerido, 3-100 caracteres, único por empresa
 * - description: opcional, máximo 500 caracteres
 * - company_id: ignorado en payload (se toma del JWT)
 */
class StoreCategoryRequest extends FormRequest
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
        // Obtener company_id del rol ACTIVO en el JWT
        $companyId = JWTHelper::getActiveCompanyId();

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                // Nombre único por empresa
                Rule::unique(\App\Features\TicketManagement\Models\Category::class, 'name')
                    ->where('company_id', $companyId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            // Ignorar company_id del payload - se toma del JWT
            'company_id' => 'prohibited',
        ];
    }

    /**
     * Mensajes de error personalizados
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required',
            'name.min' => 'The category name must be at least 3 characters',
            'name.max' => 'The category name must not exceed 100 characters',
            'name.unique' => 'A category with this name already exists in your company',
            'description.max' => 'The description must not exceed 500 characters',
        ];
    }
}
