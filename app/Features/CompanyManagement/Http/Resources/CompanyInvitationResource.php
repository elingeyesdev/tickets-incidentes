<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyInvitationResource
 *
 * Transforms CompanyInvitation model for API responses.
 */
class CompanyInvitationResource extends JsonResource
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
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'role_code' => $this->role_code,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'message' => $this->message,
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'logo_url' => $this->company->logo_url,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                $profile = $this->user->profile;
                return [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'display_name' => $profile
                        ? trim("{$profile->first_name} {$profile->last_name}") ?: $this->user->email
                        : $this->user->email,
                    'avatar_url' => $profile?->avatar_url,
                ];
            }),

            'inviter' => $this->whenLoaded('inviter', function () {
                $profile = $this->inviter->profile;
                return [
                    'id' => $this->inviter->id,
                    'email' => $this->inviter->email,
                    'display_name' => $profile
                        ? trim("{$profile->first_name} {$profile->last_name}") ?: $this->inviter->email
                        : $this->inviter->email,
                ];
            }),
        ];
    }
}
