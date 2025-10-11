<?php

namespace App\Shared\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\UserProfile;

/**
 * DataLoader para cargar perfiles de usuarios por user_id
 *
 * Evita N+1 queries al cargar perfiles de múltiples usuarios en una sola consulta.
 * Relación 1:1 entre User y UserProfile.
 *
 * @example
 * ```php
 * // En un resolver de User.profile:
 * public function profile($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(UserProfileByUserIdLoader::class)
 *         ->load($root->id);
 * }
 * ```
 */
class UserProfileByUserIdLoader
{
    /**
     * Resuelve múltiples user_ids a sus perfiles en una sola query
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return array<UserProfile|null>
     */
    public function __invoke(array $keys): array
    {
        // Cargar perfiles por user_id en una sola query
        $profiles = UserProfile::query()
            ->whereIn('user_id', $keys)
            ->get()
            ->keyBy('user_id');

        // Retornar en el mismo orden que los keys (puede haber nulls)
        return array_map(fn($key) => $profiles->get($key), $keys);
    }
}