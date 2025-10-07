<?php

namespace App\Shared\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

/**
 * DataLoader para cargar usuarios por ID
 *
 * Evita N+1 queries al cargar múltiples usuarios en una sola consulta.
 * Usado en resolvers que necesitan cargar usuarios relacionados (created_by, assigned_to, etc.)
 *
 * @example
 * ```php
 * // En un resolver:
 * public function user($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(UserByIdLoader::class)
 *         ->load($root->user_id);
 * }
 * ```
 */
class UserByIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples IDs de usuarios en una sola query
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            // Cargar usuarios por IDs en una sola query
            $users = \App\Features\UserManagement\Models\User::query()
                ->whereIn('id', $keys)
                ->get()
                ->keyBy('id');

            // Retornar en el mismo orden que los keys (puede haber nulls)
            return collect($keys)->map(fn($key) => $users->get($key));
        };
    }
}