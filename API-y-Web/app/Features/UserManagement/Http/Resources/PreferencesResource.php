<?php

namespace App\Features\UserManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreferencesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'theme' => $this->theme,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'pushWebNotifications' => $this->push_web_notifications,
            'notificationsTickets' => $this->notifications_tickets,
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
