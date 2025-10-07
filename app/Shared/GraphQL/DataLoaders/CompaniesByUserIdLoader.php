<?php

namespace App\Shared\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

/**
 * DataLoader para cargar empresas donde el usuario tiene roles activos
 *
 * Evita N+1 queries al cargar empresas relacionadas a múltiples usuarios.
 * Retorna las empresas donde el usuario tiene roles activos (AGENT, COMPANY_ADMIN).
 *
 * @example
 * ```php
 * // En un resolver de User.companies:
 * public function companies($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(CompaniesByUserIdLoader::class)
 *         ->load($root->id);
 * }
 * ```
 */
class CompaniesByUserIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples user_ids a sus empresas relacionadas en una sola query
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            // Cargar relaciones user_id -> company_id de roles activos
            $userRoles = \App\Features\UserManagement\Models\UserRole::query()
                ->whereIn('user_id', $keys)
                ->where('is_active', true)
                ->whereNotNull('company_id')
                ->get();

            // Obtener IDs únicos de empresas
            $companyIds = $userRoles->pluck('company_id')->unique()->values()->all();

            // Cargar todas las empresas en una sola query
            $companies = \App\Features\CompanyManagement\Models\Company::query()
                ->whereIn('id', $companyIds)
                ->get()
                ->keyBy('id');

            // Agrupar empresas por user_id (un usuario puede tener múltiples empresas)
            $userCompaniesMap = $userRoles->groupBy('user_id')->map(function ($roles) use ($companies) {
                return $roles->map(fn($role) => $companies->get($role->company_id))->filter();
            });

            // Retornar en el mismo orden que los keys (array de empresas por usuario)
            return collect($keys)->map(fn($key) => $userCompaniesMap->get($key, collect()));
        };
    }
}