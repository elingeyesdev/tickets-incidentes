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
            'isValid' => $this['isValid'] ?? true,
            'canReset' => $this['canReset'] ?? true,
            'email' => $this['email'] ?? null,
            'expiresAt' => $this['expiresAt']?->toIso8601String(),
            'attemptsRemaining' => $this['attemptsRemaining'] ?? 3,
        ];
    }
}
