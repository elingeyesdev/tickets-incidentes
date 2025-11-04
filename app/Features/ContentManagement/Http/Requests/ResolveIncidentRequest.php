<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Resolve Incident Request
 *
 * Validation for marking incidents as resolved.
 * Requires resolution content and allows updating title and ended_at.
 */
class ResolveIncidentRequest extends FormRequest
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
            'resolution_content' => ['required', 'string'],
            'ended_at' => [
                'nullable',
                'date_format:Y-m-d\TH:i:sP',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return; // nullable, so null is allowed
                    }

                    // Get started_at from the announcement being resolved
                    $announcement = $this->route('announcement');
                    $startedAt = $announcement->metadata['started_at'] ?? null;

                    if ($startedAt) {
                        $start = \Carbon\Carbon::parse($startedAt);
                        $end = \Carbon\Carbon::parse($value);

                        if ($end->lte($start)) {
                            $fail('The ended at field must be a date after started at.');
                        }
                    }
                },
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'resolved_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'resolution_content.required' => 'The resolution content field is required.',
            'resolution_content.string' => 'The resolution content must be a string.',

            'ended_at.date_format' => 'The ended at field must be in ISO8601 format.',
            'ended_at.after' => 'The ended at field must be a date after started at.',

            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not exceed 255 characters.',

            'resolved_at.date_format' => 'The resolved at field must be in ISO8601 format.',
        ];
    }
}
