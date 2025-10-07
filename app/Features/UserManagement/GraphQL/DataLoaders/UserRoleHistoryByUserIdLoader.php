<?php

namespace App\Features\UserManagement\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

/**
 * DataLoader para cargar historial COMPLETO de roles de usuarios (activos + inactivos)
 *
 * Evita N+1 queries al obtener roleHistory en User.
 * A diferencia de UserRolesByUserIdLoader (solo activos), este retorna TODOS los roles.
 *
 * @example
 * ```php
 * // En un resolver de User.roleHistory:
 * public function roleHistory($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(UserRoleHistoryByUserIdLoader::class)
 *         ->load($root->id);
 * }
 * ```
 */
class UserRoleHistoryByUserIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples user_ids a su historial completo de roles en una sola query
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            // Cargar historial completo de roles (activos + revocados) por user_id
            $userRoles = \App\Features\UserManagement\Models\UserRole::query()
                ->whereIn('user_id', $keys)
                ->with(['role', 'company', 'assignedBy', 'revokedBy'])
                ->orderBy('assigned_at', 'desc')
                ->get()
                ->groupBy('user_id');

            // Retornar en el mismo orden que los keys (array de roles por usuario)
            return collect($keys)->map(fn($key) => $userRoles->get($key, collect()));
        };
    }
}