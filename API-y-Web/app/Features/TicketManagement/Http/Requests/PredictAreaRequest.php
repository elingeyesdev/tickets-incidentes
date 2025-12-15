<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Predict Area Request
 *
 * Valida los datos necesarios para predecir el área de un ticket usando IA.
 */
class PredictAreaRequest extends FormRequest
{
    /**
     * Autorización: Solo usuarios con rol USER pueden usar esta funcionalidad
     */
    public function authorize(): bool
    {
        return JWTHelper::hasRoleFromJWT('USER');
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                'uuid',
                function ($attribute, $value, $fail) {
                    $company = \App\Features\CompanyManagement\Models\Company::find($value);
                    if (!$company) {
                        $fail('La compañía seleccionada no existe.');
                        return;
                    }

                    // Verificar que la empresa tiene áreas habilitadas
                    $settings = $company->settings ?? [];
                    if (!isset($settings['areas_enabled']) || !$settings['areas_enabled']) {
                        $fail('La empresa seleccionada no tiene el sistema de áreas habilitado.');
                        return;
                    }

                    // Verificar que la empresa tiene áreas activas
                    $areasCount = \App\Features\CompanyManagement\Models\Area::where('company_id', $value)
                        ->where('is_active', true)
                        ->count();

                    if ($areasCount === 0) {
                        $fail('La empresa seleccionada no tiene áreas disponibles.');
                    }

                    // NOTE: No se requiere que el company_id pertenezca al usuario.
                    // El flujo esperado del frontend es que el usuario primero
                    // seleccione la compañía y luego las categorías/áreas se
                    // obtienen para esa compañía; por tanto aceptamos el
                    // company_id enviado en el body siempre que exista y tenga
                    // áreas activas (verificaciones arriba).
                },
            ],
            'category_name' => 'required|string|max:200',
            'category_description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'La compañía es requerida.',
            'company_id.uuid' => 'El ID de la compañía no es válido.',
            'category_name.required' => 'El nombre de la categoría es requerido.',
            'category_name.max' => 'El nombre de la categoría no puede exceder 200 caracteres.',
            'category_description.max' => 'La descripción de la categoría no puede exceder 500 caracteres.',
        ];
    }
}
