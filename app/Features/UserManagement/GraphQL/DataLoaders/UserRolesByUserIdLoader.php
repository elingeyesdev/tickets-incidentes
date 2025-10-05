<?php

namespace App\Features\UserManagement\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\UserRole;
use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

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
class UserRolesByUserIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples user_ids a sus roles activos en una sola query
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            // Cargar roles activos con la relación al modelo Role
            $userRoles = UserRole::query()
                ->whereIn('user_id', $keys)
                ->where('is_active', true)
                ->with('role')
                ->get()
                ->groupBy('user_id');

            // Retornar en el mismo orden que los keys (array de roles por usuario)
            return collect($keys)->map(fn($key) => $userRoles->get($key, collect()));
        };
    }
}