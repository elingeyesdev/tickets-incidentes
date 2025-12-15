<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests;

use App\Features\ContentManagement\Rules\ValidScheduleDate;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Maintenance Request
 *
 * Validación para crear anuncios de mantenimiento.
 * Valida título, contenido, urgencia, fechas programadas, servicios afectados y acción.
 */
class StoreMaintenanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by middleware (jwt.require + role:COMPANY_ADMIN).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'content' => ['required', 'string', 'min:10', 'max:5000'],
            'urgency' => ['required', 'in:LOW,MEDIUM,HIGH'], // NOT CRITICAL for maintenance
            'scheduled_start' => ['required', 'date', 'after:now'],
            'scheduled_end' => ['required', 'date', 'after:scheduled_start'],
            'is_emergency' => ['required', 'boolean'],
            'affected_services' => ['nullable', 'array', 'max:20'],
            'affected_services.*' => ['string', 'max:100'],
            'action' => ['nullable', 'in:draft,publish,schedule'],
            'scheduled_for' => ['required_if:action,schedule', 'date', new ValidScheduleDate()],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es requerido.',
            'title.min' => 'El título debe tener al menos 3 caracteres.',
            'title.max' => 'El título no puede superar 255 caracteres.',

            'content.required' => 'El contenido es requerido.',
            'content.min' => 'El contenido debe tener al menos 10 caracteres.',
            'content.max' => 'El contenido no puede superar 5000 caracteres.',

            'urgency.required' => 'El nivel de urgencia es requerido.',
            'urgency.in' => 'El nivel de urgencia debe ser LOW, MEDIUM o HIGH.',

            'scheduled_start.required' => 'La fecha de inicio programado es requerida.',
            'scheduled_start.date' => 'La fecha de inicio debe ser una fecha válida.',
            'scheduled_start.after' => 'La fecha de inicio debe ser posterior al momento actual.',

            'scheduled_end.required' => 'La fecha de finalización programada es requerida.',
            'scheduled_end.date' => 'La fecha de finalización debe ser una fecha válida.',
            'scheduled_end.after' => 'La fecha de finalización debe ser posterior a la fecha de inicio.',

            'is_emergency.required' => 'El campo de emergencia es requerido.',
            'is_emergency.boolean' => 'El campo de emergencia debe ser verdadero o falso.',

            'affected_services.array' => 'Los servicios afectados deben ser un arreglo.',
            'affected_services.max' => 'No se pueden especificar más de 20 servicios afectados.',
            'affected_services.*.string' => 'Cada servicio afectado debe ser una cadena de texto.',
            'affected_services.*.max' => 'Cada servicio afectado no puede superar 100 caracteres.',

            'action.in' => 'La acción debe ser draft, publish o schedule.',

            'scheduled_for.required_if' => 'La fecha de programación es requerida cuando la acción es schedule.',
            'scheduled_for.date' => 'La fecha de programación debe ser una fecha válida.',
        ];
    }
}
