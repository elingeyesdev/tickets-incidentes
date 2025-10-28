<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Session Info Resource
 *
 * Transforma una sesión (RefreshToken) a su representación JSON.
 * Se usa en GET /auth/sessions.
 */
class SessionInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'sessionId' => $this->id,
            'deviceName' => $this->device_name,
            'ipAddress' => $this->ip_address,
            'userAgent' => $this->user_agent,
            'lastUsedAt' => $this->last_used_at?->toIso8601String(),
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'isCurrent' => $this->isCurrent ?? false,
        ];
    }
}
