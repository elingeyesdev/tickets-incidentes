<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Policies;

use App\Features\CompanyManagement\Models\Area;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;

/**
 * AreaPolicy - Autorización para gestión de áreas/departamentos
 *
 * Reglas:
 * - Solo COMPANY_ADMIN puede crear/actualizar/eliminar áreas
 * - Solo puede gestionar áreas de SU empresa
 * - USER y AGENT pueden ver áreas (no hay policy view, se maneja en controller)
 */
class AreaPolicy
{
    /**
     * Determinar si el usuario puede crear áreas
     *
     * Solo COMPANY_ADMIN puede crear áreas
     */
    public function create(User $user): bool
    {
        // Debe tener el rol COMPANY_ADMIN
        return JWTHelper::hasRoleFromJWT('COMPANY_ADMIN');
    }

    /**
     * Determinar si el usuario puede actualizar un área
     *
     * Solo COMPANY_ADMIN de la misma empresa puede actualizar
     */
    public function update(User $user, Area $area): bool
    {
        // Debe tener el rol COMPANY_ADMIN
        if (!JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
            return false;
        }

        // Verificar que el área pertenece a la empresa del admin
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $area->company_id === $companyId;
    }

    /**
     * Determinar si el usuario puede eliminar un área
     *
     * Solo COMPANY_ADMIN de la misma empresa puede eliminar
     */
    public function delete(User $user, Area $area): bool
    {
        // Debe tener el rol COMPANY_ADMIN
        if (!JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
            return false;
        }

        // Verificar que el área pertenece a la empresa del admin
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $area->company_id === $companyId;
    }

    /**
     * Determinar si el usuario puede ver un área
     *
     * Todos los usuarios autenticados pueden ver áreas
     * (usado opcionalmente, en la mayoría de casos se maneja en controller)
     */
    public function view(User $user, Area $area): bool
    {
        // Cualquier usuario autenticado puede ver áreas
        return true;
    }

    /**
     * Determinar si el usuario puede ver cualquier área
     *
     * Todos los usuarios autenticados pueden listar áreas
     */
    public function viewAny(User $user): bool
    {
        // Cualquier usuario autenticado puede listar áreas
        return true;
    }
}
