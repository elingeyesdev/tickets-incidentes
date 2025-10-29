<?php

namespace App\Features\UserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authenticated users can update their own profile
    }

    public function rules(): array
    {
        return [
            'firstName' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'lastName' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'phoneNumber' => [
                'sometimes',
                'nullable',
                'string',
                'min:10',
                'max:20',
                'regex:/^[\d\s\+\-\(\)]+$/',
            ],
            'avatarUrl' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'firstName.min' => 'First name must be at least 2 characters',
            'firstName.max' => 'First name cannot exceed 100 characters',
            'lastName.min' => 'Last name must be at least 2 characters',
            'lastName.max' => 'Last name cannot exceed 100 characters',
            'phoneNumber.min' => 'Phone number must be at least 10 characters',
            'phoneNumber.max' => 'Phone number cannot exceed 20 characters',
            'phoneNumber.regex' => 'Phone number format is invalid',
            'avatarUrl.url' => 'Avatar URL must be a valid URL',
        ];
    }
}
