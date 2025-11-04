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
     */
    protected function prepareForValidation(): void
    {
        // Currently, editability is checked in the service layer
        // to ensure we can return the appropriate HTTP status code (403 vs 422)
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
                            $fail('The end date must be after the start date.');
                        }
                    }
                },
            ],
            'is_emergency' => ['sometimes', 'boolean'],

            // Incident-specific validations
            'started_at' => ['sometimes', 'date_format:Y-m-d\TH:i:sP'],
            'ended_at' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d\TH:i:sP',
                function ($attribute, $value, $fail) {
                    // Get started_at from request or from announcement metadata
                    $startedAt = $this->input('started_at') ?? ($this->route('announcement')?->metadata['started_at'] ?? null);

                    if ($startedAt && $value) {
                        $start = \Carbon\Carbon::parse($startedAt);
                        $end = \Carbon\Carbon::parse($value);

                        if ($end->lte($start)) {
                            $fail('The ended at field must be a date after started at.');
                        }
                    }
                },
            ],
            'is_resolved' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) {
                    $announcement = $this->route('announcement');
                    // Once resolved, cannot change back to unresolved
                    if (isset($announcement->metadata['is_resolved'])
                        && $announcement->metadata['is_resolved'] === true
                        && $value === false) {
                        $fail('Cannot change resolved status back to unresolved');
                    }
                },
            ],
            'resolution_content' => ['sometimes', 'nullable', 'string'],
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

            'started_at.date_format' => 'The started at field must be in ISO8601 format.',
            'ended_at.date_format' => 'The ended at field must be in ISO8601 format.',
            'ended_at.after' => 'The ended at field must be a date after started at.',
            'is_resolved.boolean' => 'The is resolved field must be true or false.',
            'resolution_content.string' => 'The resolution content must be a string.',

            'affected_services.array' => 'Los servicios afectados deben ser un arreglo.',
            'affected_services.max' => 'No se pueden especificar más de 20 servicios afectados.',
            'affected_services.*.string' => 'Cada servicio afectado debe ser una cadena de texto.',
            'affected_services.*.max' => 'Cada servicio afectado no puede superar 100 caracteres.',
        ];
    }
}
