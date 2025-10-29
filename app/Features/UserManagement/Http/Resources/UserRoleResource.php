<?php

namespace App\Features\UserManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'roleCode' => strtoupper($this->role_code),
            'roleName' => $this->role?->role_name ?? 'Unknown',
            'company' => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'logoUrl' => $this->company->logo_url,
            ] : null,
            'isActive' => $this->is_active,
            'assignedAt' => $this->assigned_at?->toIso8601String(),
            'assignedBy' => $this->assignedByUser ? [
                'id' => $this->assignedByUser->id,
                'userCode' => $this->assignedByUser->user_code,
                'email' => $this->assignedByUser->email,
            ] : null,
        ];
    }
}
