<?php

namespace App\Shared\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

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
class CompanyByIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples IDs de empresas en una sola query
     *
     * @param array<string> $keys Array de UUIDs de empresas
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        // TODO: Reemplazar con modelo real cuando esté disponible
        // Por ahora retornamos datos mock para testing

        return function () use ($keys): Collection {
            // TEMPORAL: Mock data hasta que tengamos el modelo Company
            // Una vez tengamos el modelo, usar:
            /*
            $companies = \App\Features\CompanyManagement\Models\Company::query()
                ->whereIn('id', $keys)
                ->get()
                ->keyBy('id');

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $companies->get($key));
            */

            // MOCK DATA (remover después)
            $mockCompanies = collect($keys)->mapWithKeys(function ($companyId) {
                return [
                    $companyId => (object) [
                        'id' => $companyId,
                        'company_code' => 'CMP-2025-' . str_pad(rand(1, 999), 5, '0', STR_PAD_LEFT),
                        'name' => 'Company ' . substr($companyId, 0, 8),
                        'legal_name' => 'Company ' . substr($companyId, 0, 8) . ' S.A.',
                        'support_email' => 'support@company-' . substr($companyId, 0, 8) . '.com',
                        'website' => 'https://company-' . substr($companyId, 0, 8) . '.com',
                        'status' => 'active',
                        'primary_color' => '#' . substr(md5($companyId), 0, 6),
                        'secondary_color' => '#' . substr(md5($companyId), 6, 6),
                        'timezone' => 'America/La_Paz',
                        'business_hours' => json_encode([
                            'monday' => ['open' => '08:00', 'close' => '18:00'],
                            'tuesday' => ['open' => '08:00', 'close' => '18:00'],
                            'wednesday' => ['open' => '08:00', 'close' => '18:00'],
                            'thursday' => ['open' => '08:00', 'close' => '18:00'],
                            'friday' => ['open' => '08:00', 'close' => '18:00'],
                        ]),
                        'created_at' => now()->subDays(rand(30, 365)),
                        'updated_at' => now(),
                    ]
                ];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $mockCompanies->get($key));
        };
    }
}