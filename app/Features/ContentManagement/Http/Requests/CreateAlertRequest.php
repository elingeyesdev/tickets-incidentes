<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Alert Announcement Request
 *
 * Validates alert announcement creation with:
 * - Basic fields: title, content
 * - Alert-specific metadata: urgency (HIGH/CRITICAL only), alert_type, message, action_required, action_description, started_at, ended_at
 * - Optional publishing: action (draft/publish/schedule)
 */
class CreateAlertRequest extends FormRequest
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
            // Basic fields
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'content' => ['required', 'string', 'min:10'],

            // Alert-specific metadata
            'metadata' => ['required', 'array'],
            'metadata.urgency' => ['required', 'in:HIGH,CRITICAL'],  // ONLY HIGH or CRITICAL
            'metadata.alert_type' => ['required', 'in:security,system,service,compliance'],
            'metadata.message' => ['required', 'string', 'min:10', 'max:500'],
            'metadata.action_required' => ['required', 'boolean'],
            'metadata.action_description' => ['required_if:metadata.action_required,true', 'nullable', 'string', 'max:300'],
            'metadata.started_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
            'metadata.ended_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after:metadata.started_at'],
            'metadata.affected_services' => ['nullable', 'array'],

            // Publishing control
            'is_published' => ['boolean'],
            'action' => ['nullable', 'in:draft,publish,schedule'],
            'scheduled_for' => ['required_if:action,schedule', 'date_format:Y-m-d\TH:i:sP', 'after:now +5 minutes', 'before:now +1 year'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            // Basic fields
            'title.required' => 'The title is required.',
            'title.min' => 'The title must be at least 5 characters.',
            'title.max' => 'The title cannot exceed 200 characters.',

            'content.required' => 'The content is required.',
            'content.min' => 'The content must be at least 10 characters.',

            // Metadata
            'metadata.required' => 'Metadata is required for alert announcements.',
            'metadata.array' => 'Metadata must be an object.',

            // urgency
            'metadata.urgency.required' => 'The urgency level is required.',
            'metadata.urgency.in' => 'The urgency level must be HIGH or CRITICAL.',

            // alert_type
            'metadata.alert_type.required' => 'The alert type is required.',
            'metadata.alert_type.in' => 'The alert type must be: security, system, service, or compliance.',

            // message
            'metadata.message.required' => 'The alert message is required.',
            'metadata.message.min' => 'The alert message must be at least 10 characters.',
            'metadata.message.max' => 'The alert message cannot exceed 500 characters.',

            // action_required
            'metadata.action_required.required' => 'The action_required field is required.',
            'metadata.action_required.boolean' => 'The action_required field must be true or false.',

            // action_description
            'metadata.action_description.required_if' => 'The action description is required when action is required.',
            'metadata.action_description.max' => 'The action description cannot exceed 300 characters.',

            // started_at
            'metadata.started_at.required' => 'The started_at date is required.',
            'metadata.started_at.date_format' => 'The started_at date must be in ISO8601 format.',

            // ended_at
            'metadata.ended_at.date_format' => 'The ended_at date must be in ISO8601 format.',
            'metadata.ended_at.after' => 'The ended_at date must be after started_at.',

            // affected_services
            'metadata.affected_services.array' => 'The affected_services must be an array.',

            // Publishing control
            'is_published.boolean' => 'The is_published field must be true or false.',

            // Action
            'action.in' => 'The action must be: draft, publish, or schedule.',
            'scheduled_for.required_if' => 'The scheduled_for date is required when action is schedule.',
            'scheduled_for.date_format' => 'The scheduled_for date must be in ISO8601 format.',
            'scheduled_for.after' => 'The scheduled_for date must be at least 5 minutes in the future.',
            'scheduled_for.before' => 'The scheduled_for date must be within one year.',
        ];
    }
}
