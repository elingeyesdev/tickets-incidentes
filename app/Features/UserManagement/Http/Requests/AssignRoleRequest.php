<?php

namespace App\Features\UserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        return [
            'roleCode' => [
                'required',
                'in:USER,AGENT,COMPANY_ADMIN,PLATFORM_ADMIN',
            ],
            'companyId' => [
                'required_if:roleCode,AGENT,COMPANY_ADMIN',
                'nullable',
                'uuid',
                'exists:business.companies,id',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $roleCode = $this->input('roleCode');
            $companyId = $this->input('companyId');

            $rolesRequiringCompany = ['AGENT', 'COMPANY_ADMIN'];
            $rolesNotRequiringCompany = ['USER', 'PLATFORM_ADMIN'];

            if (in_array($roleCode, $rolesRequiringCompany) && !$companyId) {
                $validator->errors()->add('companyId', "{$roleCode} role requires a company");
            }

            if (in_array($roleCode, $rolesNotRequiringCompany) && $companyId) {
                $validator->errors()->add('companyId', "{$roleCode} role should not have a company");
            }
        });
    }

    public function messages(): array
    {
        return [
            'roleCode.required' => 'Role code is required',
            'roleCode.in' => 'Invalid role code',
            'companyId.required_if' => 'AGENT and COMPANY_ADMIN roles require a company',
            'companyId.uuid' => 'Invalid company ID format',
            'companyId.exists' => 'Company does not exist',
        ];
    }
}
