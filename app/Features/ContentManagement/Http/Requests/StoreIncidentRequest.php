<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Incident Request
 *
 * Validation for creating incident announcements.
 * Supports draft, publish, and schedule actions.
 * Allows creating resolved or unresolved incidents.
 */
class StoreIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by middleware (role:COMPANY_ADMIN).
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
            // Required base fields
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'urgency' => ['required', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'is_resolved' => ['required', 'boolean'],
            'started_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],

            // Optional fields
            'ended_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after:started_at'],
            'affected_services' => ['nullable', 'array'],
            'affected_services.*' => ['string'],
            'action' => ['nullable', 'in:draft,publish,schedule'],
            'scheduled_for' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after_or_equal:started_at'],

            // Conditional: if is_resolved=true, these are required
            'resolved_at' => ['required_if:is_resolved,true', 'date_format:Y-m-d\TH:i:sP'],
            'resolution_content' => ['required_if:is_resolved,true', 'string'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.max' => 'The title may not exceed 255 characters.',

            'content.required' => 'The content field is required.',

            'urgency.required' => 'The urgency field is required.',
            'urgency.in' => 'The urgency must be LOW, MEDIUM, HIGH, or CRITICAL.',

            'is_resolved.required' => 'The is resolved field is required.',
            'is_resolved.boolean' => 'The is resolved field must be true or false.',

            'started_at.required' => 'The started at field is required.',
            'started_at.date_format' => 'The started at field must be in ISO8601 format.',

            'ended_at.date_format' => 'The ended at field must be in ISO8601 format.',
            'ended_at.after' => 'The ended at field must be after started at.',

            'affected_services.array' => 'The affected services must be an array.',
            'affected_services.*.string' => 'Each affected service must be a string.',

            'action.in' => 'The action must be draft, publish, or schedule.',

            'scheduled_for.date_format' => 'The scheduled for field must be in ISO8601 format.',
            'scheduled_for.after_or_equal' => 'The scheduled for field must be equal to or after started at.',

            'resolved_at.required_if' => 'The resolved at field is required when incident is resolved.',
            'resolved_at.date_format' => 'The resolved at field must be in ISO8601 format.',

            'resolution_content.required_if' => 'The resolution content is required when incident is resolved.',
        ];
    }
}
