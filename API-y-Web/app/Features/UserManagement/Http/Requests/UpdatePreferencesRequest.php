<?php

namespace App\Features\UserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme' => [
                'sometimes',
                'required',
                'in:light,dark',
            ],
            'language' => [
                'sometimes',
                'required',
                'in:es,en',
            ],
            'timezone' => [
                'sometimes',
                'required',
                'string',
                'timezone',
            ],
            'pushWebNotifications' => [
                'sometimes',
                'required',
                'boolean',
            ],
            'notificationsTickets' => [
                'sometimes',
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'theme.in' => 'Theme must be either "light" or "dark"',
            'language.in' => 'Language must be either "es" or "en"',
            'timezone.timezone' => 'Invalid timezone',
        ];
    }
}
