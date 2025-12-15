<?php

namespace App\Features\UserManagement\Policies;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Features\UserManagement\Models\Role;
use App\Shared\Enums\Role as RoleEnum;

/**
 * UserRole Policy
 *
 * Define permisos para asignación y revocación de roles.
 * Control multi-tenant de roles con contexto empresarial.
 */
class UserRolePolicy
{
    /**
     * Determinar si el usuario puede asignar roles
     *
     * @param User $authUser Usuario autenticado
     * @param User $targetUser Usuario al que se asignará el rol
     * @param Role $role Rol a asignar
     * @param string|null $companyId Empresa contexto (si aplica)
     * @return bool
     */
    public function assign(User $authUser, User $targetUser, Role $role, ?string $companyId = null): bool
    {
        // No puede asignarse roles a sí mismo
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        // PLATFORM_ADMIN puede asignar cualquier rol
        if ($authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)) {
            return true;
        }

        // COMPANY_ADMIN puede asignar roles dentro de su empresa
        if ($authUser->hasRole(RoleEnum::COMPANY_ADMIN->value)) {
            // Solo puede asignar roles que requieren empresa
            if (!$role->requiresCompany()) {
                return false;
            }

            // Debe ser de su empresa
            if (!$companyId || !$authUser->hasRoleInCompany(RoleEnum::COMPANY_ADMIN->value, $companyId)) {
                return false;
            }

            // No puede asignar rol de PLATFORM_ADMIN
            if ($role->name === RoleEnum::PLATFORM_ADMIN->value) {
                return false;
            }

            // No puede asignar rol de COMPANY_ADMIN
            if ($role->name === RoleEnum::COMPANY_ADMIN->value) {
                return false;
            }

            // Puede asignar AGENT y USER
            return true;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede revocar un rol
     *
     * @param User $authUser Usuario autenticado
     * @param UserRole $userRole Rol a revocar
     * @return bool
     */
    public function revoke(User $authUser, UserRole $userRole): bool
    {
        // No puede revocarse roles a sí mismo
        if ($authUser->id === $userRole->user_id) {
            return false;
        }

        // PLATFORM_ADMIN puede revocar cualquier rol
        if ($authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)) {
            return true;
        }

        // COMPANY_ADMIN puede revocar roles de su empresa
        if ($authUser->hasRole(RoleEnum::COMPANY_ADMIN->value)) {
            // Solo puede revocar roles que tienen contexto de empresa
            if (!$userRole->company_id) {
                return false;
            }

            // Debe ser de su empresa
            if (!$authUser->hasRoleInCompany(RoleEnum::COMPANY_ADMIN->value, $userRole->company_id)) {
                return false;
            }

            // Cargar el rol para verificar
            $userRole->loadMissing('role');

            // No puede revocar PLATFORM_ADMIN
            if ($userRole->role->name === RoleEnum::PLATFORM_ADMIN->value) {
                return false;
            }

            // No puede revocar COMPANY_ADMIN
            if ($userRole->role->name === RoleEnum::COMPANY_ADMIN->value) {
                return false;
            }

            // Puede revocar AGENT y USER de su empresa
            return true;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede actualizar un rol
     *
     * @param User $authUser Usuario autenticado
     * @param UserRole $userRole Rol a actualizar
     * @return bool
     */
    public function update(User $authUser, UserRole $userRole): bool
    {
        // No puede actualizar sus propios roles
        if ($authUser->id === $userRole->user_id) {
            return false;
        }

        // PLATFORM_ADMIN puede actualizar cualquier rol
        if ($authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)) {
            return true;
        }

        // COMPANY_ADMIN puede actualizar roles de su empresa
        if ($authUser->hasRole(RoleEnum::COMPANY_ADMIN->value)) {
            // Solo puede actualizar roles que tienen contexto de empresa
            if (!$userRole->company_id) {
                return false;
            }

            // Debe ser de su empresa
            if (!$authUser->hasRoleInCompany(RoleEnum::COMPANY_ADMIN->value, $userRole->company_id)) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede ver los roles asignados
     *
     * @param User $authUser Usuario autenticado
     * @param User $targetUser Usuario cuyos roles se quieren ver
     * @return bool
     */
    public function viewRoles(User $authUser, User $targetUser): bool
    {
        // Puede ver sus propios roles
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        // PLATFORM_ADMIN puede ver roles de cualquier usuario
        if ($authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)) {
            return true;
        }

        // COMPANY_ADMIN puede ver roles de usuarios de su empresa
        if ($authUser->hasRole(RoleEnum::COMPANY_ADMIN->value)) {
            // TODO: Verificar si el targetUser tiene roles en alguna empresa del authUser
            return true;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede ver roles disponibles
     *
     * @param User $authUser Usuario autenticado
     * @return bool
     */
    public function viewAvailableRoles(User $authUser): bool
    {
        // Cualquier usuario autenticado puede ver roles disponibles
        // (el filtrado de cuáles puede asignar se hace en el resolver)
        return $authUser->isActive();
    }

    /**
     * Determinar si el usuario puede asignar un rol específico
     *
     * @param User $authUser Usuario autenticado
     * @param string $roleName Nombre del rol
     * @return bool
     */
    public function canAssignRole(User $authUser, string $roleName): bool
    {
        // PLATFORM_ADMIN puede asignar cualquier rol
        if ($authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)) {
            return true;
        }

        // COMPANY_ADMIN puede asignar solo AGENT y USER
        if ($authUser->hasRole(RoleEnum::COMPANY_ADMIN->value)) {
            return in_array($roleName, [
                RoleEnum::AGENT->value,
                RoleEnum::USER->value,
            ]);
        }

        return false;
    }

    /**
     * Determinar si el usuario puede cambiar roles de una empresa
     *
     * @param User $authUser Usuario autenticado
     * @param string $companyId ID de la empresa
     * @return bool
     */
    public function manageCompanyRoles(User $authUser, string $companyId): bool
    {
        // PLATFORM_ADMIN puede gestionar roles de cualquier empresa
        if ($authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)) {
            return true;
        }

        // COMPANY_ADMIN puede gestionar roles de su empresa
        if ($authUser->hasRoleInCompany(RoleEnum::COMPANY_ADMIN->value, $companyId)) {
            return true;
        }

        return false;
    }
}