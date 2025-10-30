<?php

namespace App\Features\UserManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserMinimalResource
 *
 * Propósito: Referencias rápidas a usuarios en nested resources
 * Campos: 4 campos básicos (id, user_code, email, name)
 * Eager loading: Requiere 'profile' relation para name
 */
class UserMinimalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_code' => $this->user_code,
            'email' => $this->email,
            'name' => $this->when(
                $this->relationLoaded('profile'),
                fn() => $this->profile
                    ? $this->profile->first_name . ' ' . $this->profile->last_name
                    : $this->email
            ),
        ];
    }
}
