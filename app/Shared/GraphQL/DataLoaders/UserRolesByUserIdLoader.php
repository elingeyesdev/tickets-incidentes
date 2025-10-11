<?php

namespace App\Shared\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\UserRole;
use Illuminate\Support\Collection;

/**
 * DataLoader para cargar roles activos de usuarios por user_id
 *
 * Evita N+1 queries al cargar roles de múltiples usuarios en una sola consulta.
 * Relación 1:N entre User y UserRole (un usuario puede tener múltiples roles).
 * Solo carga roles activos (is_active = true).
 *
 * @example
 * ```php
 * // En un resolver de User.activeRoles:
 * public function activeRoles($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(UserRolesByUserIdLoader::class)
 *         ->load($root->id);
 * }
 * ```
 */
class UserRolesByUserIdLoader
{
    /**
     * Resuelve múltiples user_ids a sus roles activos en una sola query
     *
     * Incluye eager loading de:
     * - role: Información del rol (role_code, role_name, default_dashboard)
     * - company: Empresa asociada al rol (nullable para USER y PLATFORM_ADMIN)
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return array<Collection>
     */
    public function __invoke(array $keys): array
    {
        // Cargar roles activos con relaciones necesarias
        $userRoles = UserRole::query()
            ->whereIn('user_id', $keys)
            ->where('is_active', true)
            ->with(['role', 'company']) // Eager load role AND company
            ->get()
            ->groupBy('user_id');

        // Retornar en el mismo orden que los keys (array de roles por usuario)
        return array_map(fn($key) => $userRoles->get($key, collect()), $keys);
    }
}