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
        // TODO: Reemplazar con modelos reales cuando estén disponibles
        // Por ahora retornamos datos mock para testing

        return function () use ($keys): Collection {
            // TEMPORAL: Mock data hasta que tengamos los modelos Company y UserRole
            // Una vez tengamos los modelos, usar:
            /*
            // Cargar company_ids de user_roles
            $userRoleCompanies = \App\Features\UserManagement\Models\UserRole::query()
                ->whereIn('user_id', $keys)
                ->where('is_active', true)
                ->whereNotNull('company_id')
                ->pluck('company_id', 'user_id');

            // Cargar todas las empresas necesarias
            $companyIds = $userRoleCompanies->unique()->values()->all();
            $companies = \App\Features\CompanyManagement\Models\Company::query()
                ->whereIn('id', $companyIds)
                ->get()
                ->keyBy('id');

            // Agrupar por user_id
            $userCompanies = collect($keys)->mapWithKeys(function ($userId) use ($userRoleCompanies, $companies) {
                $companyId = $userRoleCompanies->get($userId);
                $company = $companyId ? $companies->get($companyId) : null;

                return [$userId => $company ? collect([$company]) : collect()];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $userCompanies->get($key, collect()));
            */

            // MOCK DATA (remover después)
            $mockUserCompanies = collect($keys)->mapWithKeys(function ($userId) {
                // Simular 0-2 empresas por usuario
                $numCompanies = rand(0, 2);
                $companies = collect();

                for ($i = 0; $i < $numCompanies; $i++) {
                    $companyId = \Illuminate\Support\Str::uuid()->toString();

                    $companies->push((object) [
                        'id' => $companyId,
                        'company_code' => 'CMP-2025-' . str_pad(rand(1, 999), 5, '0', STR_PAD_LEFT),
                        'name' => 'Company ' . substr($companyId, 0, 8),
                        'logo_url' => 'https://ui-avatars.com/api/?name=Company+' . $i,
                        'primary_color' => '#' . substr(md5($companyId), 0, 6),
                        'status' => 'active',
                    ]);
                }

                return [$userId => $companies];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $mockUserCompanies->get($key, collect()));
        };
    }
}