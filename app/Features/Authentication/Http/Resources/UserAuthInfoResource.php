<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Auth Info Resource
 *
 * Transforma un usuario a su representación JSON para autenticación.
 * Replicas exactamente la estructura que retorna el resolver GraphQL.
 */
class UserAuthInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Estructura idéntica a lo que retorna UserAuthInfoResolver de GraphQL
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userCode' => $this->user_code,
            'email' => $this->email,
            'emailVerified' => (bool) $this->email_verified,
            'onboardingCompleted' => (bool) $this->onboarding_completed_at,
            'status' => strtoupper($this->status ?? 'ACTIVE'),
            'displayName' => $this->getDisplayName(),
            'avatarUrl' => $this->profile?->avatar_url,
            'theme' => $this->profile?->theme ?? 'light',
            'language' => $this->profile?->language ?? 'es',
            'roleContexts' => $this->getRoleContexts(),
        ];
    }

    /**
     * Get display name from profile or generate from names
     */
    private function getDisplayName(): string
    {
        if ($this->profile) {
            $firstName = $this->profile->first_name ?? '';
            $lastName = $this->profile->last_name ?? '';
            $displayName = trim("$firstName $lastName");
            if ($displayName) {
                return $displayName;
            }
        }
        return $this->email ?? 'User';
    }

    /**
     * Get role contexts (roles asignados al usuario)
     * Replicar la estructura del resolver GraphQL
     */
    private function getRoleContexts(): array
    {
        $roleContexts = [];

        // Obtener todos los roles del usuario con sus empresas asociadas
        $userRoles = $this->userRoles ?? collect();

        foreach ($userRoles as $userRole) {
            // Obtener role_code del modelo UserRole
            $roleCode = strtoupper(trim($userRole->role_code ?? ''));

            if (!$roleCode) {
                continue; // Skip si no hay role_code
            }

            // Mapear dashboard paths según rol (igual que LoginMutation)
            $dashboardPaths = [
                'USER' => '/tickets',
                'AGENT' => '/agent/dashboard',
                'COMPANY_ADMIN' => '/empresa/dashboard',
                'PLATFORM_ADMIN' => '/admin/dashboard',
            ];

            // Mapear nombres legibles de roles
            $roleNames = [
                'USER' => 'Cliente',
                'AGENT' => 'Agente de Soporte',
                'COMPANY_ADMIN' => 'Administrador de Empresa',
                'PLATFORM_ADMIN' => 'Administrador de Plataforma',
            ];

            $context = [
                'roleCode' => $roleCode,
                'roleName' => $roleNames[$roleCode] ?? ($userRole->role?->role_name ?? $roleCode),
                'dashboardPath' => $dashboardPaths[$roleCode] ?? '/dashboard',
            ];

            // Si el rol tiene empresa asociada, incluirla
            if ($userRole->company) {
                $context['company'] = [
                    'id' => $userRole->company->id,
                    'companyCode' => $userRole->company->company_code,
                    'name' => $userRole->company->name,
                ];
            }

            $roleContexts[] = $context;
        }

        return $roleContexts;
    }
}
