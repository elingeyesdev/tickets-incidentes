<?php

namespace App\Features\CompanyManagement\Http\Requests;

use App\Shared\Helpers\JWTHelper;
use App\Shared\Exceptions\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Company Request
 *
 * Validación para actualizar una empresa.
 * Equivalente a GraphQL Mutation: updateCompany
 */
class UpdateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * JWT Pure Stateless: Matches UpdateCompanyMutation pattern (lines 32-51)
     * - PLATFORM_ADMIN can update any company
     * - COMPANY_ADMIN can update ONLY their own company
     * - Different error messages for COMPANY_ADMIN trying to access other company vs. lacking role entirely
     */
    public function authorize(): bool
    {
        $user = JWTHelper::getAuthenticatedUser();
        $company = $this->route('company');

        $isPlatformAdmin = $user->hasRole('PLATFORM_ADMIN');
        $isCompanyAdmin = $user->hasRoleInCompany('COMPANY_ADMIN', $company->id);

        if (!$isPlatformAdmin && !$isCompanyAdmin) {
            // Check if user has COMPANY_ADMIN role for any company (but not this one)
            $hasCompanyAdminRoleElsewhere = $user->hasRole('COMPANY_ADMIN');

            if ($hasCompanyAdminRoleElsewhere) {
                // User is a COMPANY_ADMIN but not for THIS company
                throw new AuthorizationException('This action is unauthorized');
            } else {
                // User doesn't have the required role at all
                throw new AuthorizationException('Insufficient permissions');
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Información básica
            'name' => ['sometimes', 'string', 'min:2', 'max:200'],
            'legal_name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'industry_id' => ['sometimes', 'uuid', 'exists:business.company_industries,id'],
            'support_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],

            // Dirección de contacto
            'contact_info.address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_info.city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contact_info.state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contact_info.country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contact_info.postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'contact_info.tax_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact_info.legal_representative' => ['sometimes', 'nullable', 'string', 'max:200'],

            // Configuración
            'config.timezone' => ['sometimes', 'nullable', 'string', 'timezone'],
            'config.business_hours' => ['sometimes', 'nullable', 'array'],
            'config.settings' => ['sometimes', 'nullable', 'array'],
            'config.max_agents' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'config.max_tickets_per_month' => ['sometimes', 'nullable', 'integer', 'min:1'],

            // Branding
            'branding.logo_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'branding.favicon_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'branding.primary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'branding.secondary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Aplanar datos anidados para validación
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

        if ($this->has('config')) {
            $config = $this->input('config');

            $this->merge([
                'timezone' => $config['timezone'] ?? null,
                'business_hours' => $config['business_hours'] ?? null,
                'settings' => $config['settings'] ?? null,
                'max_agents' => $config['max_agents'] ?? null,
                'max_tickets_per_month' => $config['max_tickets_per_month'] ?? null,
            ]);
        }

        if ($this->has('branding')) {
            $branding = $this->input('branding');

            $this->merge([
                'logo_url' => $branding['logo_url'] ?? null,
                'favicon_url' => $branding['favicon_url'] ?? null,
                'primary_color' => $branding['primary_color'] ?? null,
                'secondary_color' => $branding['secondary_color'] ?? null,
            ]);
        }
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede superar 200 caracteres.',
            'legal_name.min' => 'El nombre legal debe tener al menos 2 caracteres.',
            'legal_name.max' => 'El nombre legal no puede superar 200 caracteres.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'industry_id.uuid' => 'El ID de industria debe ser un UUID válido.',
            'industry_id.exists' => 'La industria seleccionada no es válida.',
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
            'config.timezone.timezone' => 'La zona horaria no es válida.',
            'config.business_hours.array' => 'El horario de negocio debe ser un objeto válido.',
            'config.max_agents.integer' => 'El máximo de agentes debe ser un número entero.',
            'config.max_agents.min' => 'Debe haber al menos 1 agente.',
            'config.max_agents.max' => 'No se pueden tener más de 1000 agentes.',
            'config.max_tickets_per_month.integer' => 'El máximo de tickets debe ser un número entero.',
            'config.max_tickets_per_month.min' => 'Debe haber al menos 1 ticket por mes.',
            'branding.logo_url.url' => 'La URL del logo debe ser válida.',
            'branding.logo_url.max' => 'La URL del logo no puede superar 500 caracteres.',
            'branding.favicon_url.url' => 'La URL del favicon debe ser válida.',
            'branding.favicon_url.max' => 'La URL del favicon no puede superar 500 caracteres.',
            'branding.primary_color.regex' => 'El color primario debe ser un código hexadecimal válido (ej: #FF5733).',
            'branding.secondary_color.regex' => 'El color secundario debe ser un código hexadecimal válido (ej: #FF5733).',
        ];
    }
}
