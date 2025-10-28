<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Email Verify Request
 *
 * Validación para verificación de email.
 * Solo requiere el token de verificación.
 */
class EmailVerifyRequest extends FormRequest
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
            'token' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'token.required' => 'El token de verificación es requerido.',
        ];
    }
}
