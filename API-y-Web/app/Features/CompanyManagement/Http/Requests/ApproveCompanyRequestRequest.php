<?php

namespace App\Features\CompanyManagement\Http\Requests;

use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Approve Company Request Request
 *
 * Validación para aprobar una solicitud de empresa.
 * 
 * ARQUITECTURA NORMALIZADA:
 * - El route parameter es un string (UUID), no el modelo
 * - Buscamos la Company con scope pending() manualmente
 */
class ApproveCompanyRequestRequest extends FormRequest
{
    /**
     * La empresa pendiente encontrada (para reutilizar en el controlador)
     */
    public ?Company $pendingCompany = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('PLATFORM_ADMIN');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // No hay body, el ID viene en la URL
        return [];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $companyId = $this->route('companyRequest'); // Es un string UUID

            if (!$companyId) {
                $validator->errors()->add('company_request', 'La solicitud de empresa no existe.');
                return;
            }

            // Buscar empresa con cualquier status (incluyendo pending)
            $company = Company::withAllStatuses()->find($companyId);

            if (!$company) {
                $validator->errors()->add('company_request', 'La solicitud de empresa no existe.');
                return;
            }

            // Guardar referencia para uso en el controlador
            $this->pendingCompany = $company;

            // Verificar que la solicitud esté en estado PENDING
            if ($company->status !== 'pending') {
                $validator->errors()->add(
                    'company_request',
                    'Only pending requests can be approved. Current status: ' . $company->status
                );
            }
        });
    }
}
