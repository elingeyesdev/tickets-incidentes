<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Login Request
 *
 * Validación para el login de usuarios.
 * Email y password requeridos, deviceName opcional.
 */
class LoginRequest extends FormRequest
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
            'email' => 'required|email',
            'password' => 'required|min:8',
            'deviceName' => 'nullable|string|max:255',
            'rememberMe' => 'nullable|boolean',
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
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'deviceName.max' => 'El nombre del dispositivo no puede superar 255 caracteres.',
        ];
    }
}
