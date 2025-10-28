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
        // Determine if this is the current session
        // The is_current attribute is set by the controller/resolver
        $isCurrent = false;
        if ($this->resource instanceof \App\Features\Authentication\Models\RefreshToken) {
            $isCurrent = (bool) ($this->resource->getAttribute('is_current') ?? false);
        }

        return [
            'sessionId' => $this->id,
            'deviceName' => $this->device_name,
            'ipAddress' => $this->ip_address,
            'userAgent' => $this->user_agent,
            'lastUsedAt' => $this->last_used_at
                ? $this->last_used_at->toIso8601String()
                : null,
            'expiresAt' => $this->expires_at
                ? $this->expires_at->toIso8601String()
                : null,
            'isCurrent' => $isCurrent,
        ];
    }
}
