<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Auth Info Resource
 *
 * Transforma un usuario a su representación JSON para autenticación.
 * Incluye datos básicos y perfil.
 */
class UserAuthInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'status' => $this->status ?? 'active',
            'emailVerifiedAt' => $this->email_verified_at
                ? $this->email_verified_at->toIso8601String()
                : null,
            'onboardingCompletedAt' => $this->onboarding_completed_at
                ? $this->onboarding_completed_at->toIso8601String()
                : null,
            'profile' => $this->profile ? [
                'firstName' => $this->profile->first_name,
                'lastName' => $this->profile->last_name,
                'phoneNumber' => $this->profile->phone_number,
                'avatarUrl' => $this->profile->avatar_url,
            ] : null,
        ];
    }
}
