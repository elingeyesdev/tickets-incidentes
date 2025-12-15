<?php

namespace App\Features\UserManagement\Policies;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\Role as RoleEnum;

/**
 * User Policy
 *
 * Define permisos para operaciones sobre usuarios.
 * Matriz de permisos basada en roles del sistema.
 */
class UserPolicy
{
    /**
     * Determinar si el usuario puede ver la lista de usuarios
     *
     * @param User $authUser Usuario autenticado
     * @return bool
     */
    public function viewAny(User $authUser): bool
    {
        // Solo PLATFORM_ADMIN puede ver todos los usuarios
        // COMPANY_ADMIN puede ver usuarios de su empresa (verificado en el resolver)
        return $authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)
            || $authUser->hasRole(RoleEnum::COMPANY_ADMIN->value);
    }

    /**
     * Determinar si el usuario puede ver un usuario específico
     *
     * @param User $authUser Usuario autenticado
     * @param User $user Usuario a ver
     * @return bool
     */
    public function view(User $authUser, User $user): bool
    {
        // Puede ver su propio perfil
        if ($authUser->id === $user->id) {
            return true;
        }

        // PLATFORM_ADMIN puede ver cualquier usuario
        if ($authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)) {
            return true;
        }

        // COMPANY_ADMIN puede ver usuarios de sus empresas
        if ($authUser->hasRole(RoleEnum::COMPANY_ADMIN->value)) {
            // TODO: Verificar si comparten empresa cuando tengamos CompanyManagement
            return true;
        }

        // AGENT puede ver usuarios básicos de su empresa
        if ($authUser->hasRole(RoleEnum::AGENT->value)) {
            // TODO: Verificar si comparten empresa
            return true;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede crear usuarios
     *
     * @param User $authUser Usuario autenticado
     * @return bool
     */
    public function create(User $authUser): bool
    {
        // Solo PLATFORM_ADMIN puede crear usuarios directamente
        return $authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value);
    }

    /**
     * Determinar si el usuario puede actualizar un usuario
     *
     * @param User $authUser Usuario autenticado
     * @param User $user Usuario a actualizar
     * @return bool
     */
    public function update(User $authUser, User $user): bool
    {
        // Puede actualizar su propio perfil (limitado)
        if ($authUser->id === $user->id) {
            return true;
        }

        // PLATFORM_ADMIN puede actualizar cualquier usuario
        if ($authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)) {
            return true;
        }

        // COMPANY_ADMIN NO puede actualizar usuarios directamente
        // (solo asignar/revocar roles)
        return false;
    }

    /**
     * Determinar si el usuario puede actualizar su propio perfil
     *
     * @param User $authUser Usuario autenticado
     * @return bool
     */
    public function updateOwnProfile(User $authUser): bool
    {
        // Cualquier usuario autenticado puede actualizar su perfil
        return $authUser->isActive();
    }

    /**
     * Determinar si el usuario puede actualizar sus propias preferencias
     *
     * @param User $authUser Usuario autenticado
     * @return bool
     */
    public function updateOwnPreferences(User $authUser): bool
    {
        // Cualquier usuario autenticado puede actualizar preferencias
        return $authUser->isActive();
    }

    /**
     * Determinar si el usuario puede suspender un usuario
     *
     * @param User $authUser Usuario autenticado
     * @param User $user Usuario a suspender
     * @return bool
     */
    public function suspend(User $authUser, User $user): bool
    {
        // No puede suspenderse a sí mismo
        if ($authUser->id === $user->id) {
            return false;
        }

        // Solo PLATFORM_ADMIN puede suspender usuarios
        return $authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value);
    }

    /**
     * Determinar si el usuario puede activar un usuario suspendido
     *
     * @param User $authUser Usuario autenticado
     * @param User $user Usuario a activar
     * @return bool
     */
    public function activate(User $authUser, User $user): bool
    {
        // Solo PLATFORM_ADMIN puede activar usuarios
        return $authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value);
    }

    /**
     * Determinar si el usuario puede eliminar un usuario
     *
     * @param User $authUser Usuario autenticado
     * @param User $user Usuario a eliminar
     * @return bool
     */
    public function delete(User $authUser, User $user): bool
    {
        // No puede eliminarse a sí mismo
        if ($authUser->id === $user->id) {
            return false;
        }

        // Solo PLATFORM_ADMIN puede eliminar usuarios
        return $authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value);
    }

    /**
     * Determinar si el usuario puede ver usuarios de una empresa
     *
     * @param User $authUser Usuario autenticado
     * @param string $companyId ID de la empresa
     * @return bool
     */
    public function viewCompanyUsers(User $authUser, string $companyId): bool
    {
        // PLATFORM_ADMIN puede ver usuarios de cualquier empresa
        if ($authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value)) {
            return true;
        }

        // COMPANY_ADMIN puede ver usuarios de su empresa
        if ($authUser->hasRoleInCompany(RoleEnum::COMPANY_ADMIN->value, $companyId)) {
            return true;
        }

        // AGENT puede ver usuarios de su empresa
        if ($authUser->hasRoleInCompany(RoleEnum::AGENT->value, $companyId)) {
            return true;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede cambiar el email de otro usuario
     *
     * @param User $authUser Usuario autenticado
     * @param User $user Usuario cuyo email se va a cambiar
     * @return bool
     */
    public function changeEmail(User $authUser, User $user): bool
    {
        // Solo el propio usuario puede cambiar su email
        if ($authUser->id === $user->id) {
            return true;
        }

        // PLATFORM_ADMIN puede cambiar email de cualquier usuario
        return $authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value);
    }

    /**
     * Determinar si el usuario puede cambiar el status de otro usuario
     *
     * @param User $authUser Usuario autenticado
     * @param User $user Usuario cuyo status se va a cambiar
     * @return bool
     */
    public function changeStatus(User $authUser, User $user): bool
    {
        // No puede cambiar su propio status
        if ($authUser->id === $user->id) {
            return false;
        }

        // Solo PLATFORM_ADMIN puede cambiar status
        return $authUser->hasRole(RoleEnum::PLATFORM_ADMIN->value);
    }
}