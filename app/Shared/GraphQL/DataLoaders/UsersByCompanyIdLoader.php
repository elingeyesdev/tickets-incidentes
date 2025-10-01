<?php

namespace App\Shared\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

/**
 * DataLoader para cargar usuarios de una empresa específica
 *
 * Evita N+1 queries al cargar usuarios de múltiples empresas en una sola consulta.
 * Retorna usuarios con roles activos en la empresa (AGENT, COMPANY_ADMIN).
 *
 * @example
 * ```php
 * // En un resolver de Company.users:
 * public function users($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(UsersByCompanyIdLoader::class)
 *         ->load($root->id);
 * }
 * ```
 */
class UsersByCompanyIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples company_ids a sus usuarios relacionados en una sola query
     *
     * @param array<string> $keys Array de UUIDs de empresas
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        // TODO: Reemplazar con modelos reales cuando estén disponibles
        // Por ahora retornamos datos mock para testing

        return function () use ($keys): Collection {
            // TEMPORAL: Mock data hasta que tengamos los modelos User y UserRole
            // Una vez tengamos los modelos, usar:
            /*
            // Cargar user_ids que tienen roles en estas empresas
            $companyUserIds = \App\Features\UserManagement\Models\UserRole::query()
                ->whereIn('company_id', $keys)
                ->where('is_active', true)
                ->get()
                ->groupBy('company_id')
                ->map(fn($roles) => $roles->pluck('user_id')->unique());

            // Cargar todos los usuarios necesarios
            $allUserIds = $companyUserIds->flatten()->unique()->values()->all();
            $users = \App\Features\UserManagement\Models\User::query()
                ->whereIn('id', $allUserIds)
                ->get()
                ->keyBy('id');

            // Agrupar por company_id
            $companyUsers = collect($keys)->mapWithKeys(function ($companyId) use ($companyUserIds, $users) {
                $userIds = $companyUserIds->get($companyId, collect());
                $companyUserList = $userIds->map(fn($userId) => $users->get($userId))->filter();

                return [$companyId => $companyUserList];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $companyUsers->get($key, collect()));
            */

            // MOCK DATA (remover después)
            $mockCompanyUsers = collect($keys)->mapWithKeys(function ($companyId) {
                // Simular 3-10 usuarios por empresa
                $numUsers = rand(3, 10);
                $users = collect();

                for ($i = 0; $i < $numUsers; $i++) {
                    $userId = \Illuminate\Support\Str::uuid()->toString();
                    $firstNames = ['María', 'Juan', 'Carlos', 'Ana', 'Pedro', 'Laura', 'Diego', 'Sofia'];
                    $lastNames = ['García', 'Pérez', 'López', 'Martínez', 'Rodríguez', 'González', 'Sánchez'];

                    $firstName = $firstNames[crc32($userId) % count($firstNames)];
                    $lastName = $lastNames[crc32($userId) % count($lastNames)];

                    $users->push((object) [
                        'id' => $userId,
                        'user_code' => 'USR-2025-' . str_pad(rand(1, 999), 5, '0', STR_PAD_LEFT),
                        'email' => strtolower($firstName . '.' . $lastName . '@company.com'),
                        'email_verified' => true,
                        'status' => 'active',
                        'profile' => (object) [
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'display_name' => $firstName . ' ' . $lastName,
                            'avatar_url' => 'https://ui-avatars.com/api/?name=' . urlencode($firstName . '+' . $lastName),
                        ],
                        'created_at' => now()->subDays(rand(30, 365)),
                    ]);
                }

                return [$companyId => $users];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $mockCompanyUsers->get($key, collect()));
        };
    }
}