<?php

namespace App\Features\CompanyManagement\Http\Requests;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Reject Company Request Request
 *
 * Validación para rechazar una solicitud de empresa.
 * Equivalente a GraphQL Mutation: rejectCompanyRequest
 */
class RejectCompanyRequestRequest extends FormRequest
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
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $companyRequest = $this->route('companyRequest');

            if (!$companyRequest) {
                $validator->errors()->add('company_request', 'La solicitud de empresa no existe.');
                return;
            }

            // Verificar que la solicitud esté en estado PENDING
            if (!$companyRequest->isPending()) {
                $validator->errors()->add(
                    'company_request',
                    'Only pending requests can be rejected. Current status: ' . $companyRequest->status
                );
            }
        });
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'La razón del rechazo es obligatoria.',
            'reason.min' => 'La razón debe tener al menos 10 caracteres.',
            'reason.max' => 'La razón no puede superar 1000 caracteres.',
        ];
    }
}
