<?php

namespace App\Features\CompanyManagement\Http\Requests;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Company Request
 *
 * Validación para crear una empresa directamente (solo PLATFORM_ADMIN).
 * Equivalente a GraphQL Mutation: createCompany
 */
class CreateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * JWT Pure Stateless: Use JWTHelper to get authenticated user,
     * NOT Laravel's Auth facade (which uses Sessions).
     *
     * Matches GraphQL pattern: CreateCompanyMutation line 22-23
     */
    public function authorize(): bool
    {
        $user = JWTHelper::getAuthenticatedUser();
        return $user && $user->hasRole('PLATFORM_ADMIN');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Información básica
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'legal_name' => ['nullable', 'string', 'min:2', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'industry_id' => [
                'required',
                'uuid',
                Rule::exists(CompanyIndustry::class, 'id'),
            ],
            'admin_user_id' => [
                'required',
                'uuid',
                Rule::exists(User::class, 'id'),
            ],

            // Contacto
            'support_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],

            // Dirección de contacto
            'contact_info.address' => ['nullable', 'string', 'max:255'],
            'contact_info.city' => ['nullable', 'string', 'max:100'],
            'contact_info.state' => ['nullable', 'string', 'max:100'],
            'contact_info.country' => ['nullable', 'string', 'max:100'],
            'contact_info.postal_code' => ['nullable', 'string', 'max:20'],
            'contact_info.tax_id' => ['nullable', 'string', 'max:50'],
            'contact_info.legal_representative' => ['nullable', 'string', 'max:200'],

            // Configuración inicial
            'initial_config.timezone' => ['nullable', 'string', 'timezone'],
            'initial_config.max_agents' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'initial_config.max_tickets_per_month' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $adminUserId = $this->input('admin_user_id');

            if ($adminUserId) {
                // Verificar si el usuario ya es admin de otra empresa
                $existingCompany = Company::where('admin_user_id', $adminUserId)
                    ->where('status', '!=', 'inactive')
                    ->exists();

                if ($existingCompany) {
                    $validator->errors()->add(
                        'admin_user_id',
                        'Este usuario ya es administrador de otra empresa activa.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Aplanar datos anidados para validación (matches UpdateCompanyRequest pattern)
        if ($this->has('contact_info')) {
            $contactInfo = $this->input('contact_info');

            $this->merge([
                'contact_address' => $contactInfo['address'] ?? null,
                'contact_city' => $contactInfo['city'] ?? null,
                'contact_state' => $contactInfo['state'] ?? null,
                'contact_country' => $contactInfo['country'] ?? null,
                'contact_postal_code' => $contactInfo['postal_code'] ?? null,
                'tax_id' => $contactInfo['tax_id'] ?? null,
                'legal_representative' => $contactInfo['legal_representative'] ?? null,
            ]);
        }

        if ($this->has('initial_config')) {
            $config = $this->input('initial_config');

            $this->merge([
                'timezone' => $config['timezone'] ?? null,
                'max_agents' => $config['max_agents'] ?? null,
                'max_tickets_per_month' => $config['max_tickets_per_month'] ?? null,
            ]);
        }
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la empresa es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede superar 200 caracteres.',
            'legal_name.min' => 'El nombre legal debe tener al menos 2 caracteres.',
            'legal_name.max' => 'El nombre legal no puede superar 200 caracteres.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'industry_id.required' => 'Debe seleccionar una industria.',
            'industry_id.uuid' => 'El ID de industria debe ser un UUID válido.',
            'industry_id.exists' => 'La industria seleccionada no es válida.',
            'admin_user_id.required' => 'El ID del administrador es obligatorio.',
            'admin_user_id.uuid' => 'El ID del administrador debe ser un UUID válido.',
            'admin_user_id.exists' => 'El usuario administrador no existe.',
            'support_email.email' => 'El email de soporte debe ser válido.',
            'support_email.max' => 'El email no puede superar 255 caracteres.',
            'phone.max' => 'El teléfono no puede superar 20 caracteres.',
            'website.url' => 'El sitio web debe ser una URL válida.',
            'website.max' => 'El sitio web no puede superar 255 caracteres.',
            'contact_info.address.max' => 'La dirección no puede superar 255 caracteres.',
            'contact_info.city.max' => 'La ciudad no puede superar 100 caracteres.',
            'contact_info.state.max' => 'El estado no puede superar 100 caracteres.',
            'contact_info.country.max' => 'El país no puede superar 100 caracteres.',
            'contact_info.postal_code.max' => 'El código postal no puede superar 20 caracteres.',
            'contact_info.tax_id.max' => 'El ID fiscal no puede superar 50 caracteres.',
            'contact_info.legal_representative.max' => 'El nombre del representante legal no puede superar 200 caracteres.',
            'initial_config.timezone.timezone' => 'La zona horaria no es válida.',
            'initial_config.max_agents.integer' => 'El máximo de agentes debe ser un número entero.',
            'initial_config.max_agents.min' => 'Debe haber al menos 1 agente.',
            'initial_config.max_agents.max' => 'No se pueden tener más de 1000 agentes.',
            'initial_config.max_tickets_per_month.integer' => 'El máximo de tickets debe ser un número entero.',
            'initial_config.max_tickets_per_month.min' => 'Debe haber al menos 1 ticket por mes.',
        ];
    }
}
