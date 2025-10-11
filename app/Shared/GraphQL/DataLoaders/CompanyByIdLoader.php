<?php

namespace App\Shared\GraphQL\DataLoaders;

use App\Features\CompanyManagement\Models\Company;

/**
 * DataLoader para cargar empresas por ID
 *
 * Evita N+1 queries al cargar múltiples empresas en una sola consulta.
 * Usado en contextos de roles, tickets, y cualquier relación con empresas.
 *
 * @example
 * ```php
 * // En un resolver:
 * public function company($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(CompanyByIdLoader::class)
 *         ->load($root->company_id);
 * }
 * ```
 */
class CompanyByIdLoader
{
    /**
     * Resuelve múltiples IDs de empresas en una sola query
     *
     * @param array<string> $keys Array de UUIDs de empresas
     * @return array<Company|null>
     */
    public function __invoke(array $keys): array
    {
        // Cargar empresas por IDs en una sola query
        $companies = Company::query()
            ->whereIn('id', $keys)
            ->get()
            ->keyBy('id');

        // Retornar en el mismo orden que los keys (puede haber nulls)
        return array_map(fn($key) => $companies->get($key), $keys);
    }
}