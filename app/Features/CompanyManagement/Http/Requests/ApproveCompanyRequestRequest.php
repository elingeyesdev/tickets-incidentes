<?php

namespace App\Features\CompanyManagement\Http\Requests;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Approve Company Request Request
 *
 * Validación para aprobar una solicitud de empresa.
 * Equivalente a GraphQL Mutation: approveCompanyRequest
 */
class ApproveCompanyRequestRequest extends FormRequest
{
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
            $companyRequest = $this->route('company_request');

            if (!$companyRequest) {
                $validator->errors()->add('company_request', 'La solicitud de empresa no existe.');
                return;
            }

            // Verificar que la solicitud esté en estado PENDING
            if (!$companyRequest->isPending()) {
                $validator->errors()->add(
                    'company_request',
                    'Solo se pueden aprobar solicitudes en estado pendiente. Estado actual: ' . $companyRequest->status
                );
            }
        });
    }
}
