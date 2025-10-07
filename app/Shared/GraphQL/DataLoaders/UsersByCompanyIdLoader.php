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
        return function () use ($keys): Collection {
            // Cargar relaciones company_id -> user_id de roles activos
            $userRoles = \App\Features\UserManagement\Models\UserRole::query()
                ->whereIn('company_id', $keys)
                ->where('is_active', true)
                ->get();

            // Obtener IDs únicos de usuarios
            $userIds = $userRoles->pluck('user_id')->unique()->values()->all();

            // Cargar todos los usuarios en una sola query
            $users = \App\Features\UserManagement\Models\User::query()
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy('id');

            // Agrupar usuarios por company_id (una empresa puede tener múltiples usuarios)
            $companyUsersMap = $userRoles->groupBy('company_id')->map(function ($roles) use ($users) {
                return $roles->map(fn($role) => $users->get($role->user_id))->filter()->unique('id')->values();
            });

            // Retornar en el mismo orden que los keys (array de usuarios por empresa)
            return collect($keys)->map(fn($key) => $companyUsersMap->get($key, collect()));
        };
    }
}