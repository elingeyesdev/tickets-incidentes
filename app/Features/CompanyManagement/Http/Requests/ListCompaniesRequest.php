<?php

namespace App\Features\CompanyManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * List Companies Request
 *
 * Validación para el listado de empresas con filtros y paginación.
 * Equivalente a GraphQL Input: CompanyFiltersInput
 */
class ListCompaniesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // La autorización depende del contexto (minimal=público, explore=auth, management=admin)
        // Se maneja en el controller según el endpoint
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalizar status a lowercase para ser case-insensitive
        if ($this->has('status')) {
            $this->merge([
                'status' => strtolower($this->status),
            ]);
        }

        // Convert string "true"/"false" to proper boolean values for followed_by_me
        if ($this->has('followed_by_me')) {
            $value = $this->followed_by_me;
            if (is_string($value)) {
                $this->merge([
                    'followed_by_me' => in_array($value, ['true', '1', 'yes', 'on'], true) ? 1 : 0,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Paginación
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],

            // Filtros
            'status' => ['nullable', 'string', 'in:active,suspended,inactive'],
            'industry' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'has_active_tickets' => ['nullable', 'boolean'],
            'followed_by_me' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],

            // Ordenamiento
            'sort_by' => [
                'nullable',
                'string',
                'in:name,created_at,total_users,active_agents,total_tickets,followers_count',
            ],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser mayor o igual a 1.',
            'per_page.integer' => 'Los elementos por página deben ser un número entero.',
            'per_page.min' => 'Debe haber al menos 1 elemento por página.',
            'per_page.max' => 'No se pueden mostrar más de 100 elementos por página.',
            'status.in' => 'El estado debe ser: active, suspended o inactive.',
            'industry.max' => 'La industria no puede superar 100 caracteres.',
            'country.max' => 'El país no puede superar 100 caracteres.',
            'has_active_tickets.boolean' => 'El filtro de tickets activos debe ser verdadero o falso.',
            'followed_by_me.boolean' => 'El filtro de seguidos debe ser verdadero o falso.',
            'search.max' => 'El término de búsqueda no puede superar 255 caracteres.',
            'sort_by.in' => 'El campo de ordenamiento no es válido.',
            'sort_direction.in' => 'La dirección de ordenamiento debe ser: asc o desc.',
        ];
    }
}
