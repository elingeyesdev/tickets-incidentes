<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\UserRole;
use GraphQL\Deferred;
use Illuminate\Support\Facades\DB;

/**
 * BatchLoader para cargar estadísticas de empresas (agents, users)
 *
 * Implementa el patrón de Lighthouse 6 usando GraphQL\Deferred
 * para prevenir N+1 queries al obtener conteos de agentes y usuarios en listas de empresas.
 *
 * Usado en:
 * - Company.activeAgentsCount
 * - Company.totalUsersCount
 * - Model getters: getActiveAgentsCountAttribute(), getTotalUsersCountAttribute()
 *
 * @example
 * ```php
 * // En un resolver field de Company.activeAgentsCount:
 * $loader = app(CompanyStatsBatchLoader::class);
 * $stats = $loader->load($root->id);
 * return $stats['active_agents_count'];
 * ```
 */
class CompanyStatsBatchLoader
{
    /**
     * Map from company_id to company IDs that need loading
     *
     * @var array<string, string>
     */
    protected array $companyIds = [];

    /**
     * Map from company_id to stats array
     *
     * @var array<string, array{active_agents_count: int, total_users_count: int}>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading stats for a company
     *
     * Returns a Deferred that resolves to an array with stats.
     *
     * @param string $companyId Company ID to load stats for
     * @return \GraphQL\Deferred
     */
    public function load(string $companyId): Deferred
    {
        $this->companyIds[$companyId] = $companyId;

        return new Deferred(function () use ($companyId) {
            if (! $this->hasResolved) {
                $this->resolve();
            }

            return $this->results[$companyId] ?? [
                'active_agents_count' => 0,
                'total_users_count' => 0,
            ];
        });
    }

    /**
     * Resolve all queued company IDs to their stats in a single batch query
     */
    protected function resolve(): void
    {
        $companyIds = array_keys($this->companyIds);

        // Batch load active agents count with GROUP BY
        $agentCounts = UserRole::query()
            ->whereIn('company_id', $companyIds)
            ->where('role_code', 'agent')
            ->where('is_active', true)
            ->select('company_id', DB::raw('COUNT(*) as count'))
            ->groupBy('company_id')
            ->get()
            ->pluck('count', 'company_id');

        // Batch load total users count with GROUP BY and DISTINCT
        $userCounts = UserRole::query()
            ->whereIn('company_id', $companyIds)
            ->where('is_active', true)
            ->select('company_id', DB::raw('COUNT(DISTINCT user_id) as count'))
            ->groupBy('company_id')
            ->get()
            ->pluck('count', 'company_id');

        // Map results back to company IDs
        foreach ($companyIds as $companyId) {
            $this->results[$companyId] = [
                'active_agents_count' => (int) ($agentCounts->get($companyId) ?? 0),
                'total_users_count' => (int) ($userCounts->get($companyId) ?? 0),
            ];
        }

        $this->hasResolved = true;
    }
}
