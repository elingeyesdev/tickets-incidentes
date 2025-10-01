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
        // TODO: Reemplazar con modelo real cuando esté disponible
        // Por ahora retornamos datos mock para testing

        return function () use ($keys): Collection {
            // TEMPORAL: Mock data hasta que tengamos el modelo User
            // Una vez tengamos el modelo, usar:
            /*
            $users = \App\Features\UserManagement\Models\User::query()
                ->whereIn('id', $keys)
                ->get()
                ->keyBy('id');

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $users->get($key));
            */

            // MOCK DATA (remover después)
            $mockUsers = collect($keys)->mapWithKeys(function ($userId) {
                return [
                    $userId => (object) [
                        'id' => $userId,
                        'user_code' => 'USR-2025-' . str_pad(rand(1, 999), 5, '0', STR_PAD_LEFT),
                        'email' => 'user_' . substr($userId, 0, 8) . '@example.com',
                        'email_verified' => true,
                        'status' => 'active',
                        'auth_provider' => 'local',
                        'created_at' => now()->subDays(rand(1, 365)),
                        'updated_at' => now(),
                    ]
                ];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $mockUsers->get($key));
        };
    }
}