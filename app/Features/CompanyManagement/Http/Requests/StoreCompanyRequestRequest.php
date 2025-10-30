<?php

namespace App\Features\CompanyManagement\Http\Requests;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Company Request Request
 *
 * Validación para crear una solicitud de empresa.
 * Equivalente a GraphQL Mutation: submitCompanyRequest
 */
class StoreCompanyRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Endpoint público
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Información de la empresa
            'company_name' => ['required', 'string', 'min:2', 'max:200'],
            'legal_name' => ['nullable', 'string', 'min:2', 'max:200'],
            'admin_email' => ['required', 'email', 'max:255'],
            'business_description' => ['required', 'string', 'min:50', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry_type' => ['required', 'string', 'max:100'],
            'estimated_users' => ['nullable', 'integer', 'min:1', 'max:10000'],

            // Información de contacto
            'contact_address' => ['nullable', 'string', 'max:255'],
            'contact_city' => ['nullable', 'string', 'max:100'],
            'contact_country' => ['nullable', 'string', 'max:100'],
            'contact_postal_code' => ['nullable', 'string', 'max:20'],

            // Información fiscal (opcional)
            'tax_id' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verificar si el admin_email ya tiene una solicitud pendiente
            $adminEmail = $this->input('admin_email');

            if ($adminEmail) {
                $existingRequest = CompanyRequest::where('admin_email', $adminEmail)
                    ->where('status', 'pending')
                    ->exists();

                if ($existingRequest) {
                    $validator->errors()->add(
                        'admin_email',
                        'Ya existe una solicitud pendiente con este email de administrador.'
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'El nombre de la empresa es obligatorio.',
            'company_name.min' => 'El nombre de la empresa debe tener al menos 2 caracteres.',
            'company_name.max' => 'El nombre de la empresa no puede superar 200 caracteres.',
            'legal_name.min' => 'El nombre legal debe tener al menos 2 caracteres.',
            'legal_name.max' => 'El nombre legal no puede superar 200 caracteres.',
            'admin_email.required' => 'El email del administrador es obligatorio.',
            'admin_email.email' => 'El email del administrador debe ser válido.',
            'admin_email.max' => 'El email no puede superar 255 caracteres.',
            'business_description.required' => 'La descripción del negocio es obligatoria.',
            'business_description.min' => 'La descripción debe tener al menos 50 caracteres.',
            'business_description.max' => 'La descripción no puede superar 2000 caracteres.',
            'website.url' => 'El sitio web debe ser una URL válida.',
            'website.max' => 'El sitio web no puede superar 255 caracteres.',
            'industry_type.required' => 'El tipo de industria es obligatorio.',
            'industry_type.max' => 'El tipo de industria no puede superar 100 caracteres.',
            'estimated_users.integer' => 'El número de usuarios debe ser un número entero.',
            'estimated_users.min' => 'Debe haber al menos 1 usuario estimado.',
            'estimated_users.max' => 'El número de usuarios no puede superar 10,000.',
            'contact_address.max' => 'La dirección no puede superar 255 caracteres.',
            'contact_city.max' => 'La ciudad no puede superar 100 caracteres.',
            'contact_country.max' => 'El país no puede superar 100 caracteres.',
            'contact_postal_code.max' => 'El código postal no puede superar 20 caracteres.',
            'tax_id.max' => 'El identificador fiscal no puede superar 50 caracteres.',
        ];
    }
}
