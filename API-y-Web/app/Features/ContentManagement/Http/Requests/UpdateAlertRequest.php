<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Alert Announcement Request
 *
 * Validates partial updates to alert announcements.
 * All fields are optional (sometimes) but must meet the same constraints when provided.
 * Special validation: action_required cannot change from true to false.
 */
class UpdateAlertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by controller logic (COMPANY_ADMIN, DRAFT/SCHEDULED only).
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
            // Basic fields - optional updates
            'title' => ['sometimes', 'string', 'min:5', 'max:200'],
            'content' => ['sometimes', 'string', 'min:10'],

            // Alert-specific metadata - optional updates
            'metadata' => ['sometimes', 'array'],
            'metadata.urgency' => ['sometimes', 'in:HIGH,CRITICAL'],  // Still only HIGH or CRITICAL
            'metadata.alert_type' => ['sometimes', 'in:security,system,service,compliance'],
            'metadata.message' => ['sometimes', 'string', 'min:10', 'max:500'],
            'metadata.action_required' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) {
                    // Get the announcement from the route parameter
                    $announcement = $this->route('announcement');

                    if (!$announcement) {
                        return; // Can't validate if no announcement
                    }

                    // Get current action_required value
                    $currentActionRequired = $announcement->metadata['action_required'] ?? false;

                    // Prevent changing from true to false
                    if ($currentActionRequired === true && $value === false) {
                        $fail('Cannot change action_required from true to false.');
                    }
                },
            ],
            'metadata.action_description' => ['required_if:metadata.action_required,true', 'nullable', 'string', 'max:300'],
            'metadata.started_at' => ['sometimes', 'date_format:Y-m-d\TH:i:sP'],
            'metadata.ended_at' => ['sometimes', 'nullable', 'date_format:Y-m-d\TH:i:sP', 'after:metadata.started_at'],
            'metadata.affected_services' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            // Basic fields
            'title.min' => 'The title must be at least 5 characters.',
            'title.max' => 'The title cannot exceed 200 characters.',

            'content.min' => 'The content must be at least 10 characters.',

            // Metadata
            'metadata.array' => 'Metadata must be an object.',

            // urgency
            'metadata.urgency.in' => 'The urgency level must be HIGH or CRITICAL.',

            // alert_type
            'metadata.alert_type.in' => 'The alert type must be: security, system, service, or compliance.',

            // message
            'metadata.message.min' => 'The alert message must be at least 10 characters.',
            'metadata.message.max' => 'The alert message cannot exceed 500 characters.',

            // action_required
            'metadata.action_required.boolean' => 'The action_required field must be true or false.',

            // action_description
            'metadata.action_description.required_if' => 'The action description is required when action is required.',
            'metadata.action_description.max' => 'The action description cannot exceed 300 characters.',

            // started_at
            'metadata.started_at.date_format' => 'The started_at date must be in ISO8601 format.',

            // ended_at
            'metadata.ended_at.date_format' => 'The ended_at date must be in ISO8601 format.',
            'metadata.ended_at.after' => 'The ended_at date must be after started_at.',

            // affected_services
            'metadata.affected_services.array' => 'The affected_services must be an array.',
        ];
    }
}
