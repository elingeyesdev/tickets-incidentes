<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authenticated users can upload their own avatar
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'file',
                'max:5120', // 5MB en KB
                'image:jpeg,png,gif,webp', // Solo imágenes
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Avatar image is required',
            'avatar.file' => 'Avatar must be a valid file',
            'avatar.max' => 'Avatar must not exceed 5 MB',
            'avatar.image' => 'Avatar must be a valid image (JPEG, PNG, GIF, WebP)',
        ];
    }
}
