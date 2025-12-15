<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Area;
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
     * Solo COMPANY_ADMIN con rol activo puede crear áreas
     */
    public function create(User $user): bool
    {
        // El rol activo debe ser COMPANY_ADMIN
        return JWTHelper::getActiveRoleCode() === 'COMPANY_ADMIN';
    }

    /**
     * Determinar si el usuario puede actualizar un área
     *
     * Solo COMPANY_ADMIN con rol activo de la misma empresa puede actualizar
     */
    public function update(User $user, Area $area): bool
    {
        // El rol activo debe ser COMPANY_ADMIN
        if (JWTHelper::getActiveRoleCode() !== 'COMPANY_ADMIN') {
            return false;
        }

        // Verificar que el área pertenece a la empresa del rol activo
        $companyId = JWTHelper::getActiveCompanyId();

        return $companyId && $area->company_id === $companyId;
    }

    /**
     * Determinar si el usuario puede eliminar un área
     *
     * Solo COMPANY_ADMIN con rol activo de la misma empresa puede eliminar
     */
    public function delete(User $user, Area $area): bool
    {
        // El rol activo debe ser COMPANY_ADMIN
        if (JWTHelper::getActiveRoleCode() !== 'COMPANY_ADMIN') {
            return false;
        }

        // Verificar que el área pertenece a la empresa del rol activo
        $companyId = JWTHelper::getActiveCompanyId();

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
