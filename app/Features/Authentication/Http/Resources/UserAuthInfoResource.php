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
     *
     * Acceso seguro a propiedades - maneja relaciones no cargadas
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id ?? null,
            'userCode' => $this->user_code ?? null,
            'email' => $this->email ?? null,
            'emailVerified' => (bool) ($this->email_verified ?? false),
            'onboardingCompleted' => (bool) ($this->onboarding_completed_at ?? false),
            'status' => $this->status?->name ?? 'ACTIVE',
            'displayName' => $this->getDisplayName(),
            'avatarUrl' => $this->getAvatarUrl(),
            'theme' => $this->getTheme(),
            'language' => $this->getLanguage(),
        ];

        // Cargar relaciones necesarias si no están ya cargadas
        $this->loadMissing(['userRoles', 'profile']);

        // Ahora que sabemos que las relaciones están cargadas, podemos usarlas
        $data['roleContexts'] = $this->getRoleContexts();


        return $data;
    }

    private function getAvatarUrl(): ?string
    {
        try {
            return $this->profile?->avatar_url;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getTheme(): string
    {
        try {
            return $this->profile?->theme ?? 'light';
        } catch (\Exception $e) {
            return 'light';
        }
    }

    private function getLanguage(): string
    {
        try {
            return $this->profile?->language ?? 'es';
        } catch (\Exception $e) {
            return 'es';
        }
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

            // Si el rol tiene empresa asociada, incluirla; de lo contrario, null
            if ($userRole->company) {
                $context['company'] = [
                    'id' => $userRole->company->id,
                    'companyCode' => $userRole->company->company_code,
                    'name' => $userRole->company->name,
                ];
            } else {
                $context['company'] = null;
            }

            $roleContexts[] = $context;
        }

        return $roleContexts;
    }
}
