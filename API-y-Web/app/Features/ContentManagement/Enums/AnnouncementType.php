<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Enums;

enum AnnouncementType: string
{
    case MAINTENANCE = 'MAINTENANCE';
    case INCIDENT = 'INCIDENT';
    case NEWS = 'NEWS';
    case ALERT = 'ALERT';

    public function metadataSchema(): array
    {
        return match($this) {
            self::MAINTENANCE => [
                'required' => ['urgency', 'scheduled_start', 'scheduled_end', 'is_emergency'],
                'optional' => ['actual_start', 'actual_end', 'affected_services'],
            ],
            self::INCIDENT => [
                'required' => ['urgency', 'is_resolved', 'started_at'],
                'optional' => ['resolved_at', 'resolution_content', 'ended_at', 'affected_services'],
            ],
            self::NEWS => [
                'required' => ['news_type', 'target_audience', 'summary'],
                'optional' => ['call_to_action'],
            ],
            self::ALERT => [
                'required' => ['urgency', 'alert_type', 'message', 'action_required', 'started_at'],
                'optional' => ['action_description', 'affected_services', 'ended_at'],
            ],
        };
    }
}
