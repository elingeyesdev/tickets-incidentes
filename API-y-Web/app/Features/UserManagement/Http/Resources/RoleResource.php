<?php

namespace App\Features\UserManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->role_code,
            'name' => $this->role_name,
            'description' => $this->description,
            'requiresCompany' => $this->requiresCompany(),
            'defaultDashboard' => $this->getDefaultDashboard(),
            'isSystemRole' => $this->is_system,
        ];
    }

    protected function getDefaultDashboard(): string
    {
        return match ($this->role_code) {
            'PLATFORM_ADMIN' => '/admin/dashboard',
            'COMPANY_ADMIN' => '/empresa/dashboard',
            'AGENT' => '/agent/dashboard',
            'USER' => '/tickets',
            default => '/dashboard',
        };
    }
}
