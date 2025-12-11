<?php

declare(strict_types=1);

namespace App\Features\ExternalIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para login externo (automático, sin contraseña).
 * 
 * Este tipo de login es "trusted" porque viene de un proyecto
 * que ya autenticó al usuario con su propia autenticación.
 * La confianza se establece mediante la API Key válida.
 */
class ExternalLoginRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
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
        ];
    }
}
