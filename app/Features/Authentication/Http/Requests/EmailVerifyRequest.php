<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Email Verify Request
 *
 * Validación para verificación de email.
 * Acepta:
 * - token: Token de 64 caracteres (link en email)
 * - code: Código de 6 dígitos (entrada manual)
 * 
 * NOTA: Debe proporcionar token O código, no ambos.
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
            // Token O código requerido (pero no ambos - validado en controller)
            'token' => 'nullable|string|min:10',
            'code' => 'nullable|string|size:6',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'token.min' => 'El token de verificación es inválido.',
            'code.size' => 'El código debe tener exactamente 6 dígitos.',
        ];
    }
}

