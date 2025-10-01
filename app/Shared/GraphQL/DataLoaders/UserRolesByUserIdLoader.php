<?php

namespace App\Shared\GraphQL\DataLoaders;

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
        // TODO: Reemplazar con modelo real cuando esté disponible
        // Por ahora retornamos datos mock para testing

        return function () use ($keys): Collection {
            // TEMPORAL: Mock data hasta que tengamos el modelo UserRole
            // Una vez tengamos el modelo, usar:
            /*
            $userRoles = \App\Features\UserManagement\Models\UserRole::query()
                ->whereIn('user_id', $keys)
                ->where('is_active', true)
                ->get()
                ->groupBy('user_id');

            // Retornar en el mismo orden que los keys (array de roles por usuario)
            return collect($keys)->map(fn($key) => $userRoles->get($key, collect()));
            */

            // MOCK DATA (remover después)
            $roleTypes = ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'];
            $roleNames = [
                'USER' => 'Cliente',
                'AGENT' => 'Agente de Soporte',
                'COMPANY_ADMIN' => 'Administrador de Empresa',
                'PLATFORM_ADMIN' => 'Administrador de Plataforma'
            ];

            $mockUserRoles = collect($keys)->mapWithKeys(function ($userId) use ($roleTypes, $roleNames) {
                // Simular 1-3 roles por usuario
                $numRoles = rand(1, 3);
                $userRoles = collect();

                for ($i = 0; $i < $numRoles; $i++) {
                    $roleCode = $roleTypes[crc32($userId . $i) % count($roleTypes)];

                    $userRoles->push((object) [
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'user_id' => $userId,
                        'role_code' => $roleCode,
                        'role_name' => $roleNames[$roleCode],
                        'company_id' => in_array($roleCode, ['AGENT', 'COMPANY_ADMIN'])
                            ? \Illuminate\Support\Str::uuid()->toString()
                            : null,
                        'is_active' => true,
                        'assigned_at' => now()->subDays(rand(1, 180)),
                        'created_at' => now()->subDays(rand(1, 365)),
                        'updated_at' => now()->subDays(rand(1, 30)),
                    ]);
                }

                return [$userId => $userRoles];
            });

            // Retornar en el mismo orden que los keys (array de roles por usuario)
            return collect($keys)->map(fn($key) => $mockUserRoles->get($key, collect()));
        };
    }
}