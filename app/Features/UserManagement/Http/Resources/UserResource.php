<?php

namespace App\Features\UserManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userCode' => $this->user_code,
            'email' => $this->email,
            'emailVerified' => $this->email_verified,
            'status' => strtoupper($this->status->name),
            'authProvider' => $this->auth_provider,
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'roleContexts' => $this->transformRoleContexts(),
            'ticketsCount' => $this->tickets_count ?? 0,
            'resolvedTicketsCount' => $this->resolved_tickets_count ?? 0,
            'averageRating' => $this->average_rating,
            'lastLoginAt' => $this->last_login_at?->toIso8601String(),
            'lastActivityAt' => $this->last_activity_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }

    protected function transformRoleContexts(): array
    {
        if (!$this->relationLoaded('userRoles')) {
            return [];
        }

        return $this->userRoles->map(function ($userRole) {
            $roleCode = strtoupper($userRole->role_code);

            return [
                'roleCode' => $roleCode,
                'roleName' => $this->getRoleName($roleCode),
                'company' => $userRole->company ? [
                    'id' => $userRole->company->id,
                    'companyCode' => $userRole->company->company_code,
                    'name' => $userRole->company->name,
                    'logoUrl' => $userRole->company->logo_url,
                ] : null,
                'dashboardPath' => $this->getDashboardPath($roleCode),
            ];
        })->toArray();
    }

    protected function getRoleName(string $roleCode): string
    {
        return match ($roleCode) {
            'USER' => 'Cliente',
            'AGENT' => 'Agente de Soporte',
            'COMPANY_ADMIN' => 'Administrador de Empresa',
            'PLATFORM_ADMIN' => 'Administrador de Plataforma',
            default => 'Unknown',
        };
    }

    protected function getDashboardPath(string $roleCode): string
    {
        return match ($roleCode) {
            'USER' => '/tickets',
            'AGENT' => '/agent/dashboard',
            'COMPANY_ADMIN' => '/empresa/dashboard',
            'PLATFORM_ADMIN' => '/admin/dashboard',
            default => '/dashboard',
        };
    }
}
