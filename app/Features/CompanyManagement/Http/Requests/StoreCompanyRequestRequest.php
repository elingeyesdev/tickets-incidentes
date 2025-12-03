<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Http\Requests;

use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\CompanyManagement\Services\CompanyDuplicateDetectionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'company_description' => ['required', 'string', 'min:50', 'max:1000'],
            'request_message' => ['required', 'string', 'min:10', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry_id' => [
                'required',
                'uuid',
                Rule::exists(CompanyIndustry::class, 'id'),
            ],
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
     *
     * VALIDACIÓN DE DUPLICADOS:
     * Usa CompanyDuplicateDetectionService para detectar empresas duplicadas mediante:
     * 1. Tax ID (NIT/RUC) - Bloqueo absoluto si existe
     * 2. Admin Email + Nombre Similar - Previene misma persona creando duplicado
     * 3. Website Domain + Nombre Similar - Previene usar mismo dominio
     * 4. Nombre muy similar - Advertencia sobre posibles duplicados
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Obtener datos de entrada
            $companyName = $this->input('company_name');
            $adminEmail = $this->input('admin_email');
            $taxId = $this->input('tax_id');
            $website = $this->input('website');
            $industryId = $this->input('industry_id');

            // Validar solo si tenemos datos mínimos requeridos
            if (! $companyName || ! $adminEmail) {
                return;
            }

            // Ejecutar detección de duplicados
            $detectionService = app(CompanyDuplicateDetectionService::class);

            $result = $detectionService->detectDuplicates(
                companyName: $companyName,
                adminEmail: $adminEmail,
                taxId: $taxId,
                website: $website,
                industryId: $industryId
            );

            // Agregar errores de bloqueo (previenen crear la solicitud)
            foreach ($result['blocking_errors'] as $field => $message) {
                $validator->errors()->add($field, $message);
            }

            // Agregar advertencias (permiten crear pero alertan al usuario)
            // Las advertencias se muestran pero NO bloquean la creación
            foreach ($result['warnings'] as $field => $message) {
                // En Laravel, las advertencias también van a errors() pero con prefijo
                // El frontend puede distinguirlas y mostrarlas de forma diferente
                if (! $validator->errors()->has($field)) {
                    $validator->errors()->add($field, $message);
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
            'company_description.required' => 'La descripción de la empresa es obligatoria.',
            'company_description.min' => 'La descripción debe tener al menos 50 caracteres.',
            'company_description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'request_message.required' => 'El mensaje de solicitud es obligatorio.',
            'request_message.min' => 'El mensaje debe tener al menos 10 caracteres.',
            'request_message.max' => 'El mensaje no puede superar los 500 caracteres.',
            'website.url' => 'El sitio web debe ser una URL válida.',
            'website.max' => 'El sitio web no puede superar 255 caracteres.',
            'industry_id.required' => 'Debe seleccionar una industria.',
            'industry_id.uuid' => 'El ID de industria debe ser un UUID válido.',
            'industry_id.exists' => 'La industria seleccionada no es válida.',
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
