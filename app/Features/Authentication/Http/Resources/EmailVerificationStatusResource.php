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
            'isVerified' => $this['isVerified'] ?? false,
            'email' => $this['email'] ?? null,
            'verificationSentAt' => $this['verificationSentAt']?->toIso8601String(),
            'canResend' => $this['canResend'] ?? true,
            'resendAvailableAt' => $this['resendAvailableAt']?->toIso8601String(),
            'attemptsRemaining' => $this['attemptsRemaining'] ?? 5,
        ];
    }
}
