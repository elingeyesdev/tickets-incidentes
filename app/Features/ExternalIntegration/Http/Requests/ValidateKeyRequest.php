<?php

declare(strict_types=1);

namespace App\Features\ExternalIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para validar API Key.
 * 
 * Este endpoint solo verifica que la API Key sea válida
 * y retorna información básica de la empresa.
 */
class ValidateKeyRequest extends FormRequest
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
        // No requiere body, solo el header X-Service-Key que valida el middleware
        return [];
    }
}
