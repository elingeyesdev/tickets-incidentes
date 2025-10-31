<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Password Reset Confirm Request
 *
 * Validación para confirmación de reset de contraseña.
 * Requiere token O code (no ambos), password, passwordConfirmation.
 */
class PasswordResetConfirmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Endpoint público
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'token' => 'nullable|string|required_without:code|prohibits:code',
            'code' => 'nullable|string|required_without:token|prohibits:token',
            'password' => [
                'required',
                'min:8',
                'same:passwordConfirmation',  // Usa camelCase (no 'confirmed' que espera snake_case)
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols(),
            ],
            'passwordConfirmation' => 'required|min:8',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'token.size' => 'El token debe tener 32 caracteres.',
            'token.required_without' => 'Debes proporcionar un token o código.',
            'token.prohibits' => 'Solo puede proporcionar uno de los dos: token o código.',
            'code.regex' => 'El código debe ser de 6 dígitos.',
            'code.required_without' => 'Debes proporcionar un token o código.',
            'code.prohibits' => 'Solo puede proporcionar uno de los dos: código o token.',
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'passwordConfirmation.required' => 'Debes confirmar la contraseña.',
        ];
    }
}
