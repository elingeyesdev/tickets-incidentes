<?php

namespace App\Features\UserManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'displayName' => $this->display_name,
            'phoneNumber' => $this->phone_number,
            'avatarUrl' => $this->avatar_url,
            'theme' => $this->theme,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'pushWebNotifications' => $this->push_web_notifications,
            'notificationsTickets' => $this->notifications_tickets,
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
