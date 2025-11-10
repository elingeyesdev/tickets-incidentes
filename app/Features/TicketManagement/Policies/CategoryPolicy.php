<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Category;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;

/**
 * CategoryPolicy - Autorización para gestión de categorías
 *
 * Reglas:
 * - Solo COMPANY_ADMIN puede crear/actualizar/eliminar categorías
 * - Solo puede gestionar categorías de SU empresa
 * - USER y AGENT pueden ver categorías (no hay policy view, se maneja en controller)
 */
class CategoryPolicy
{
    /**
     * Determinar si el usuario puede crear categorías
     *
     * Solo COMPANY_ADMIN puede crear categorías
     */
    public function create(User $user): bool
    {
        // Debe tener el rol COMPANY_ADMIN
        return $user->hasRole('COMPANY_ADMIN');
    }

    /**
     * Determinar si el usuario puede actualizar una categoría
     *
     * Solo COMPANY_ADMIN de la misma empresa puede actualizar
     */
    public function update(User $user, Category $category): bool
    {
        // Debe tener el rol COMPANY_ADMIN
        if (!$user->hasRole('COMPANY_ADMIN')) {
            return false;
        }

        // Verificar que la categoría pertenece a la empresa del admin
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $category->company_id === $companyId;
    }

    /**
     * Determinar si el usuario puede eliminar una categoría
     *
     * Solo COMPANY_ADMIN de la misma empresa puede eliminar
     */
    public function delete(User $user, Category $category): bool
    {
        // Debe tener el rol COMPANY_ADMIN
        if (!$user->hasRole('COMPANY_ADMIN')) {
            return false;
        }

        // Verificar que la categoría pertenece a la empresa del admin
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $category->company_id === $companyId;
    }

    /**
     * Determinar si el usuario puede ver una categoría
     *
     * Todos los usuarios autenticados pueden ver categorías
     * (usado opcionalmente, en la mayoría de casos se maneja en controller)
     */
    public function view(User $user, Category $category): bool
    {
        // Cualquier usuario autenticado puede ver categorías
        return true;
    }

    /**
     * Determinar si el usuario puede ver cualquier categoría
     *
     * Todos los usuarios autenticados pueden listar categorías
     */
    public function viewAny(User $user): bool
    {
        // Cualquier usuario autenticado puede listar categorías
        return true;
    }
}
