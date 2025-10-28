<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Email Verification Result Resource
 *
 * Transforma el resultado de verificación de email a JSON.
 * Se usa en POST /email/verify y POST /email/verify/resend.
 */
class EmailVerificationResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => $this['success'] ?? false,
            'message' => $this['message'] ?? '',
            'canResend' => $this['canResend'] ?? true,
            'resendAvailableAt' => $this['resendAvailableAt']?->toIso8601String(),
        ];
    }
}
