<?php

namespace App\Shared\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

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
class UserProfileByUserIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples user_ids a sus perfiles en una sola query
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        // TODO: Reemplazar con modelo real cuando esté disponible
        // Por ahora retornamos datos mock para testing

        return function () use ($keys): Collection {
            // TEMPORAL: Mock data hasta que tengamos el modelo UserProfile
            // Una vez tengamos el modelo, usar:
            /*
            $profiles = \App\Features\UserManagement\Models\UserProfile::query()
                ->whereIn('user_id', $keys)
                ->get()
                ->keyBy('user_id');

            // Retornar en el mismo orden que los keys (puede haber nulls)
            return collect($keys)->map(fn($key) => $profiles->get($key));
            */

            // MOCK DATA (remover después)
            $mockProfiles = collect($keys)->mapWithKeys(function ($userId) {
                $firstNames = ['María', 'Juan', 'Carlos', 'Ana', 'Pedro', 'Laura', 'Diego', 'Sofia'];
                $lastNames = ['García', 'Pérez', 'López', 'Martínez', 'Rodríguez', 'González', 'Sánchez'];

                $firstName = $firstNames[crc32($userId) % count($firstNames)];
                $lastName = $lastNames[crc32($userId) % count($lastNames)];

                return [
                    $userId => (object) [
                        'user_id' => $userId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'display_name' => $firstName . ' ' . $lastName,
                        'phone_number' => '+591 7' . rand(0, 9) . rand(100000, 999999),
                        'avatar_url' => 'https://ui-avatars.com/api/?name=' . urlencode($firstName . '+' . $lastName),
                        'theme' => ['light', 'dark'][crc32($userId) % 2],
                        'language' => 'es',
                        'timezone' => 'America/La_Paz',
                        'push_web_notifications' => true,
                        'notifications_tickets' => true,
                        'last_activity_at' => now()->subMinutes(rand(1, 1440)),
                        'created_at' => now()->subDays(rand(30, 365)),
                        'updated_at' => now()->subDays(rand(1, 30)),
                    ]
                ];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $mockProfiles->get($key));
        };
    }
}