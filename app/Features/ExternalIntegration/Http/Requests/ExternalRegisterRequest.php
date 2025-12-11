<?php

declare(strict_types=1);

namespace App\Features\ExternalIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para registro externo.
 * 
 * Cuando un usuario no existe en Helpdesk, el widget le pide
 * crear una contraseña y se registra con este endpoint.
 */
class ExternalRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // La autorización se maneja por el middleware service.api-key
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
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email', // No debe existir ya
            ],
            'firstName' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'lastName' => [
                'nullable',
                'string',
                'max:100',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed', // Requiere password_confirmation
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
            'email.required' => 'El email es requerido.',
            'email.email' => 'El email debe ser válido.',
            'email.unique' => 'Este email ya está registrado.',
            'firstName.required' => 'El nombre es requerido.',
            'firstName.min' => 'El nombre debe tener al menos 2 caracteres.',
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'firstName' => 'nombre',
            'lastName' => 'apellido',
            'password' => 'contraseña',
        ];
    }
}
