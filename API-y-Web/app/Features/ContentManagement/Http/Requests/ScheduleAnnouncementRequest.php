<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests;

use App\Features\ContentManagement\Rules\ValidScheduleDate;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Schedule Announcement Request
 *
 * Validación para programar un anuncio existente.
 * Solo requiere la fecha de programación válida (5 minutos a 1 año en el futuro).
 */
class ScheduleAnnouncementRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'scheduled_for' => ['required', 'date', new ValidScheduleDate()],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'scheduled_for.required' => 'The scheduled for field is required.',
            'scheduled_for.date' => 'The scheduled for field must be a valid date.',
        ];
    }
}
