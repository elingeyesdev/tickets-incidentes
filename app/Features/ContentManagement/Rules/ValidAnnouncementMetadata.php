<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidAnnouncementMetadata implements Rule
{
    private string $announcementType;
    private array $errors = [];

    public function __construct(string $announcementType)
    {
        $this->announcementType = $announcementType;
    }

    public function passes($attribute, $value): bool
    {
        if (!is_array($value)) {
            $this->errors[] = 'Metadata must be an array';
            return false;
        }

        return match ($this->announcementType) {
            'MAINTENANCE' => $this->validateMaintenance($value),
            'INCIDENT' => $this->validateIncident($value),
            'NEWS' => $this->validateNews($value),
            'ALERT' => $this->validateAlert($value),
            default => false,
        };
    }

    private function validateMaintenance(array $metadata): bool
    {
        // Required fields
        $required = ['urgency', 'scheduled_start', 'scheduled_end', 'is_emergency'];
        foreach ($required as $field) {
            if (!isset($metadata[$field])) {
                $this->errors[] = "The {$field} field is required for MAINTENANCE";
                return false;
            }
        }

        // scheduled_end must be after scheduled_start
        if (isset($metadata['scheduled_start']) && isset($metadata['scheduled_end'])) {
            if (strtotime($metadata['scheduled_end']) <= strtotime($metadata['scheduled_start'])) {
                $this->errors[] = 'scheduled_end must be after scheduled_start';
                return false;
            }
        }

        // urgency must be LOW, MEDIUM, or HIGH (not CRITICAL)
        if (isset($metadata['urgency']) && $metadata['urgency'] === 'CRITICAL') {
            $this->errors[] = 'MAINTENANCE urgency cannot be CRITICAL';
            return false;
        }

        return true;
    }

    private function validateIncident(array $metadata): bool
    {
        // Required fields
        $required = ['urgency', 'is_resolved', 'started_at'];
        foreach ($required as $field) {
            if (!isset($metadata[$field])) {
                $this->errors[] = "The {$field} field is required for INCIDENT";
                return false;
            }
        }

        // Conditional requirements when resolved
        if ($metadata['is_resolved'] === true) {
            if (!isset($metadata['resolved_at'])) {
                $this->errors[] = 'resolved_at is required when is_resolved is true';
                return false;
            }
            if (!isset($metadata['resolution_content'])) {
                $this->errors[] = 'resolution_content is required when is_resolved is true';
                return false;
            }
        }

        return true;
    }

    private function validateNews(array $metadata): bool
    {
        // Required fields
        $required = ['news_type', 'target_audience', 'summary'];
        foreach ($required as $field) {
            if (!isset($metadata[$field])) {
                $this->errors[] = "The {$field} field is required for NEWS";
                return false;
            }
        }

        // target_audience must be array
        if (!is_array($metadata['target_audience'])) {
            $this->errors[] = 'target_audience must be an array';
            return false;
        }

        // target_audience must have at least 1 item
        if (empty($metadata['target_audience'])) {
            $this->errors[] = 'target_audience must have at least one item';
            return false;
        }

        return true;
    }

    private function validateAlert(array $metadata): bool
    {
        // Required fields
        $required = ['urgency', 'alert_type', 'message', 'action_required', 'started_at'];
        foreach ($required as $field) {
            if (!isset($metadata[$field])) {
                $this->errors[] = "The {$field} field is required for ALERT";
                return false;
            }
        }

        // urgency must be HIGH or CRITICAL only
        if (isset($metadata['urgency']) && !in_array($metadata['urgency'], ['HIGH', 'CRITICAL'])) {
            $this->errors[] = 'ALERT urgency must be HIGH or CRITICAL';
            return false;
        }

        // Conditional requirement
        if ($metadata['action_required'] === true && !isset($metadata['action_description'])) {
            $this->errors[] = 'action_description is required when action_required is true';
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return implode('; ', $this->errors);
    }
}
