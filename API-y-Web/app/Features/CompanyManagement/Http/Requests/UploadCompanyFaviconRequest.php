<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCompanyFaviconRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    public function rules(): array
    {
        return [
            'favicon' => [
                'required',
                'file',
                'max:1024', // 1MB
                'mimes:x-icon,ico,png,jpeg,jpg',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'favicon.required' => 'Favicon file is required',
            'favicon.file' => 'Favicon must be a valid file',
            'favicon.max' => 'Favicon must not exceed 1 MB',
            'favicon.mimes' => 'Favicon must be a valid icon format (ICO, PNG, JPEG)',
        ];
    }
}
