<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Register Request
 *
 * Validación para el registro de nuevos usuarios.
 * Reemplaza el @rules directive de GraphQL.
 */
class RegisterRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'min:8',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols(),
            ],
            'passwordConfirmation' => 'required|min:8|same:password',
            'firstName' => 'required|string|min:2|max:255',
            'lastName' => 'required|string|min:2|max:255',
            'acceptsTerms' => 'required|boolean|accepted',
            'acceptsPrivacyPolicy' => 'required|boolean|accepted',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El email es requerido.',
            'email.email' => 'El email debe ser válido.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'passwordConfirmation.required' => 'Debes confirmar la contraseña.',
            'firstName.required' => 'El nombre es requerido.',
            'firstName.max' => 'El nombre no puede superar 255 caracteres.',
            'lastName.required' => 'El apellido es requerido.',
            'lastName.max' => 'El apellido no puede superar 255 caracteres.',
            'acceptsTerms.required' => 'Debes aceptar los términos de servicio.',
            'acceptsTerms.accepted' => 'Debes aceptar los términos de servicio.',
            'acceptsPrivacyPolicy.required' => 'Debes aceptar la política de privacidad.',
            'acceptsPrivacyPolicy.accepted' => 'Debes aceptar la política de privacidad.',
        ];
    }
}
