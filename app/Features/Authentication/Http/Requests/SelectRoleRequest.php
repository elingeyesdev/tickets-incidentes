<?php

declare(strict_types=1);

namespace App\Features\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Select Role Request
 *
 * Validates the request to select/change the active role.
 * Used for POST /api/auth/select-role
 */
class SelectRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated (enforced by jwt.require middleware)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_code' => [
                'required',
                'string',
                Rule::in(['PLATFORM_ADMIN', 'COMPANY_ADMIN', 'AGENT', 'USER']),
            ],
            'company_id' => [
                'nullable',
                'uuid',
                // COMPANY_ADMIN and AGENT require company_id
                'required_if:role_code,COMPANY_ADMIN,AGENT',
                // PLATFORM_ADMIN and USER must NOT have company_id
                'prohibited_if:role_code,PLATFORM_ADMIN,USER',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role_code.required' => 'El código de rol es requerido.',
            'role_code.in' => 'El código de rol debe ser uno de: PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER.',
            'company_id.required_if' => 'El ID de empresa es requerido para los roles COMPANY_ADMIN y AGENT.',
            'company_id.prohibited_if' => 'El ID de empresa no debe enviarse para los roles PLATFORM_ADMIN y USER.',
            'company_id.uuid' => 'El ID de empresa debe ser un UUID válido.',
        ];
    }
}
