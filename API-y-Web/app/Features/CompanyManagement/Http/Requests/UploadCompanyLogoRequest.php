<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCompanyLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'max:5120', // 5MB
                'mimes:jpeg,jpg,png,gif,webp,svg',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'Logo file is required',
            'logo.file' => 'Logo must be a valid file',
            'logo.max' => 'Logo must not exceed 5 MB',
            'logo.mimes' => 'Logo must be a valid image (JPEG, PNG, GIF, WebP, SVG)',
        ];
    }
}
