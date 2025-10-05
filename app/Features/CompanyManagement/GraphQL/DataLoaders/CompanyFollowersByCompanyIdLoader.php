<?php

namespace App\Features\CompanyManagement\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

/**
 * DataLoader para verificar si el usuario autenticado sigue empresas
 *
 * Evita N+1 queries al obtener isFollowedByMe en Company.
 * Usado en queries como companies(EXPLORE) donde necesitamos saber si el usuario
 * actual sigue cada empresa de la lista.
 *
 * @example
 * ```php
 * // En un resolver de Company.isFollowedByMe:
 * public function isFollowedByMe($root, array $args, GraphQLContext $context)
 * {
 *     $userId = $context->user()->id;
 *     $follows = $context->dataLoader(CompanyFollowersByCompanyIdLoader::class)
 *         ->load($root->id);
 *
 *     return $follows->contains('user_id', $userId);
 * }
 * ```
 */
class CompanyFollowersByCompanyIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples company_ids a sus followers en una sola query
     *
     * @param array<string> $keys Array de UUIDs de empresas
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            use App\Features\CompanyManagement\Models\CompanyFollower;

            $followers = CompanyFollower::query()
                ->whereIn('company_id', $keys)
                ->get()
                ->groupBy('company_id');

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $followers->get($key, collect()));
        };
    }
}