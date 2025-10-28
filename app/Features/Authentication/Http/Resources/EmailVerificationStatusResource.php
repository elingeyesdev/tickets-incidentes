<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Email Verification Status Resource
 *
 * Transforma el estado de verificación de email a JSON.
 * Se usa en GET /email/status.
 */
class EmailVerificationStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'isVerified' => $this['is_verified'] ?? false,
            'email' => $this['email'] ?? null,
            'verificationSentAt' => isset($this['verified_at']) && $this['verified_at']
                ? $this['verified_at']->toIso8601String()
                : null,
            'canResend' => $this['can_resend'] ?? true,
            'resendAvailableAt' => isset($this['resend_available_at']) && $this['resend_available_at']
                ? $this['resend_available_at']->toIso8601String()
                : null,
            'attemptsRemaining' => $this['attempts_remaining'] ?? 5,
        ];
    }
}
