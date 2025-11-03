<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Announcement Request
 *
 * Validación para actualizar anuncios existentes.
 * Permite actualizaciones parciales.
 * Solo anuncios en DRAFT o SCHEDULED pueden ser editados.
 */
class UpdateAnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by middleware and policies.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * This method is called before validation runs.
     * We validate that the announcement is editable here.
     */
    protected function prepareForValidation(): void
    {
        // Get the announcement from route parameter
        $announcement = $this->route('id');

        // If announcement is a model instance, check if it's editable
        if ($announcement instanceof Announcement) {
            if (!$announcement->isEditable()) {
                abort(422, 'Cannot edit announcements that are already published or archived.');
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:3', 'max:255'],
            'content' => ['sometimes', 'string', 'min:10', 'max:5000'],
            'urgency' => ['sometimes', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'scheduled_start' => ['sometimes', 'date', 'after:now'],
            'scheduled_end' => [
                'sometimes',
                'date',
                function ($attribute, $value, $fail) {
                    // If both scheduled_start and scheduled_end are provided, validate
                    $scheduledStart = $this->input('scheduled_start');

                    if ($scheduledStart && $value) {
                        $start = \Carbon\Carbon::parse($scheduledStart);
                        $end = \Carbon\Carbon::parse($value);

                        if ($end->lte($start)) {
                            $fail('La fecha de finalización debe ser posterior a la fecha de inicio.');
                        }
                    }
                },
            ],
            'is_emergency' => ['sometimes', 'boolean'],
            'affected_services' => ['sometimes', 'nullable', 'array', 'max:20'],
            'affected_services.*' => ['string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'title.min' => 'El título debe tener al menos 3 caracteres.',
            'title.max' => 'El título no puede superar 255 caracteres.',

            'content.min' => 'El contenido debe tener al menos 10 caracteres.',
            'content.max' => 'El contenido no puede superar 5000 caracteres.',

            'urgency.in' => 'El nivel de urgencia debe ser LOW, MEDIUM, HIGH o CRITICAL.',

            'scheduled_start.date' => 'La fecha de inicio debe ser una fecha válida.',
            'scheduled_start.after' => 'La fecha de inicio debe ser posterior al momento actual.',

            'scheduled_end.date' => 'La fecha de finalización debe ser una fecha válida.',

            'is_emergency.boolean' => 'El campo de emergencia debe ser verdadero o falso.',

            'affected_services.array' => 'Los servicios afectados deben ser un arreglo.',
            'affected_services.max' => 'No se pueden especificar más de 20 servicios afectados.',
            'affected_services.*.string' => 'Cada servicio afectado debe ser una cadena de texto.',
            'affected_services.*.max' => 'Cada servicio afectado no puede superar 100 caracteres.',
        ];
    }
}
