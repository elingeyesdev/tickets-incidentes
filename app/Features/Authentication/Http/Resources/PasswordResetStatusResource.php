<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Password Reset Status Resource
 *
 * Transforma el estado de un token de reset a JSON.
 * Se usa en GET /password-reset/status.
 */
class PasswordResetStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'isValid' => $this['is_valid'] ?? true,
            'canReset' => $this['can_reset'] ?? true,
            'email' => $this['email'] ?? null,
            'expiresAt' => isset($this['expires_at']) && $this['expires_at']
                ? $this['expires_at']->toIso8601String()
                : null,
            'attemptsRemaining' => $this['attempts_remaining'] ?? 3,
        ];
    }
}
