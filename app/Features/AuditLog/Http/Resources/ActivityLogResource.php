<?php

declare(strict_types=1);

namespace App\Features\AuditLog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ActivityLogResource
 *
 * Resource para formatear logs de actividad en respuestas API.
 */
class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'email' => $this->user->email,
                'displayName' => $this->user->displayName,
            ]),
            'action' => $this->action,
            'actionDescription' => $this->action_description,
            'actionCategory' => $this->action_category,
            'entityType' => $this->entity_type,
            'entityId' => $this->entity_id,
            'oldValues' => $this->old_values,
            'newValues' => $this->new_values,
            'metadata' => $this->metadata,
            'ipAddress' => $this->ip_address,
            'userAgent' => $this->user_agent,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
