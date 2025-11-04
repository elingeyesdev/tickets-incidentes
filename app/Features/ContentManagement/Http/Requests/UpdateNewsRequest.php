<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update News Announcement Request
 *
 * Validates partial updates to news announcements.
 * All fields use 'sometimes' to allow partial updates.
 * Metadata fields are merged intelligently in the controller.
 */
class UpdateNewsRequest extends FormRequest
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
            // Basic fields (optional for partial updates)
            'title' => ['sometimes', 'string', 'min:5', 'max:200'],
            'body' => ['sometimes', 'string', 'min:10'],

            // News-specific metadata (optional for partial updates)
            'metadata' => ['sometimes', 'array'],
            'metadata.news_type' => ['sometimes', 'in:feature_release,policy_update,general_update'],
            'metadata.target_audience' => ['sometimes', 'array', 'min:1', 'max:5'],
            'metadata.target_audience.*' => ['in:users,agents,admins'],
            'metadata.summary' => ['sometimes', 'string', 'min:10', 'max:500'],

            // Optional call to action (can be added, updated, or removed)
            'metadata.call_to_action' => ['nullable', 'array'],
            'metadata.call_to_action.text' => ['required_with:metadata.call_to_action', 'string'],
            'metadata.call_to_action.url' => ['required_with:metadata.call_to_action', 'url', 'starts_with:https'],

            // Publishing control (optional)
            'is_published' => ['sometimes', 'boolean'],
            'started_at' => ['sometimes', 'nullable', 'date_format:Y-m-d\TH:i:sP', 'after:now +5 minutes'],
            'ended_at' => ['sometimes', 'nullable', 'date_format:Y-m-d\TH:i:sP', 'after:started_at', 'before:now +1 year'],
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

            'body.min' => 'The body must be at least 10 characters.',

            // Metadata
            'metadata.array' => 'Metadata must be an object.',

            // news_type
            'metadata.news_type.in' => 'The news type must be: feature_release, policy_update, or general_update.',

            // target_audience
            'metadata.target_audience.array' => 'The target audience must be an array.',
            'metadata.target_audience.min' => 'At least one target audience must be specified.',
            'metadata.target_audience.max' => 'No more than 5 target audiences can be specified.',
            'metadata.target_audience.*.in' => 'Each target audience must be: users, agents, or admins.',

            // summary
            'metadata.summary.min' => 'The summary must be at least 10 characters.',
            'metadata.summary.max' => 'The summary cannot exceed 500 characters.',

            // call_to_action
            'metadata.call_to_action.array' => 'The call to action must be an object.',
            'metadata.call_to_action.text.required_with' => 'Call to action text is required when providing a call to action.',
            'metadata.call_to_action.url.required_with' => 'Call to action URL is required when providing a call to action.',
            'metadata.call_to_action.url.url' => 'Call to action URL must be a valid URL.',
            'metadata.call_to_action.url.starts_with' => 'Call to action URL must start with https.',

            // Publishing control
            'is_published.boolean' => 'The is_published field must be true or false.',
            'started_at.date_format' => 'The started_at date must be in ISO8601 format.',
            'started_at.after' => 'The started_at date must be at least 5 minutes in the future.',
            'ended_at.date_format' => 'The ended_at date must be in ISO8601 format.',
            'ended_at.after' => 'The ended_at date must be after started_at.',
            'ended_at.before' => 'The ended_at date must be within one year.',
        ];
    }
}
