<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests;

use App\Features\ContentManagement\Rules\ValidScheduleDate;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create News Announcement Request
 *
 * Validates news announcement creation with:
 * - Basic fields: title, body (stored as content)
 * - News-specific metadata: news_type, target_audience, summary, call_to_action
 * - Optional publishing: action (draft/publish/schedule)
 */
class CreateNewsRequest extends FormRequest
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
            'body' => ['required', 'string', 'min:10'],

            // News-specific metadata
            'metadata' => ['required', 'array'],
            'metadata.news_type' => ['required', 'in:feature_release,policy_update,general_update'],
            'metadata.target_audience' => [
                'required',
                'array',
                'min:1',
                'max:5',
                function ($attribute, $value, $fail) {
                    // Ensure it's an array before iterating
                    if (!is_array($value)) {
                        return; // 'array' rule will handle this
                    }

                    $validAudiences = ['users', 'agents', 'admins'];
                    foreach ($value as $audience) {
                        if (!in_array($audience, $validAudiences)) {
                            $fail('The target audience contains invalid values. Valid values are: users, agents, admins.');
                            return;
                        }
                    }
                },
            ],
            'metadata.target_audience.*' => ['in:users,agents,admins'],
            'metadata.summary' => ['required', 'string', 'min:10', 'max:500'],

            // Optional call to action (with conditional validation)
            'metadata.call_to_action' => ['nullable', 'array'],
            'metadata.call_to_action.text' => ['required_with:metadata.call_to_action', 'string'],
            'metadata.call_to_action.url' => ['required_with:metadata.call_to_action', 'url', 'starts_with:https'],

            // Publishing control
            'is_published' => ['boolean'],
            'started_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after:now +5 minutes'],
            'ended_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after:started_at', 'before:now +1 year'],

            // Action control
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

            'body.required' => 'The body content is required.',
            'body.min' => 'The body must be at least 10 characters.',

            // Metadata
            'metadata.required' => 'Metadata is required for news announcements.',
            'metadata.array' => 'Metadata must be an object.',

            // news_type
            'metadata.news_type.required' => 'The news type is required.',
            'metadata.news_type.in' => 'The news type must be: feature_release, policy_update, or general_update.',

            // target_audience
            'metadata.target_audience.required' => 'The target audience is required.',
            'metadata.target_audience.array' => 'The target audience must be an array.',
            'metadata.target_audience.min' => 'At least one target audience must be specified.',
            'metadata.target_audience.max' => 'No more than 5 target audiences can be specified.',
            'metadata.target_audience.*.in' => 'Each target audience must be: users, agents, or admins.',

            // summary
            'metadata.summary.required' => 'The summary is required.',
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

            // Action
            'action.in' => 'The action must be: draft, publish, or schedule.',
            'scheduled_for.required_if' => 'The scheduled_for date is required when action is schedule.',
            'scheduled_for.date_format' => 'The scheduled_for date must be in ISO8601 format.',
            'scheduled_for.after' => 'The scheduled_for date must be at least 5 minutes in the future.',
            'scheduled_for.before' => 'The scheduled_for date must be within one year.',
        ];
    }
}
