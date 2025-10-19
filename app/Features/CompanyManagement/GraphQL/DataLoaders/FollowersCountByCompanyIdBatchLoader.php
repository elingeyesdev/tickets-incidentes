<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\DataLoaders;

use App\Features\CompanyManagement\Models\CompanyFollower;
use GraphQL\Deferred;
use Illuminate\Support\Facades\DB;

/**
 * BatchLoader para cargar conteo de followers por empresa
 *
 * Implementa el patrón de Lighthouse 6 usando GraphQL\Deferred
 * para prevenir N+1 queries al obtener followersCount en listas de empresas.
 *
 * Usado en:
 * - Company.followersCount (schema GraphQL)
 * - CompanyForFollowing.followersCount (schema GraphQL)
 * - Model getter getFollowersCountAttribute()
 *
 * @example
 * ```php
 * // En un resolver field de Company.followersCount:
 * $loader = app(FollowersCountByCompanyIdBatchLoader::class);
 * return $loader->load($root->id);
 * ```
 */
class FollowersCountByCompanyIdBatchLoader
{
    /**
     * Map from company_id to company IDs that need loading
     *
     * @var array<string, string>
     */
    protected array $companyIds = [];

    /**
     * Map from company_id to follower count
     *
     * @var array<string, int>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading follower count for a company
     *
     * Returns a Deferred that resolves to an integer count.
     *
     * @param string $companyId Company ID to load follower count for
     * @return \GraphQL\Deferred
     */
    public function load(string $companyId): Deferred
    {
        $this->companyIds[$companyId] = $companyId;

        return new Deferred(function () use ($companyId) {
            if (! $this->hasResolved) {
                $this->resolve();
            }

            return $this->results[$companyId] ?? 0;
        });
    }

    /**
     * Resolve all queued company IDs to their follower counts in a single batch query
     */
    protected function resolve(): void
    {
        $companyIds = array_keys($this->companyIds);

        // Batch load all counts with GROUP BY in one query
        $counts = CompanyFollower::query()
            ->whereIn('company_id', $companyIds)
            ->select('company_id', DB::raw('COUNT(*) as count'))
            ->groupBy('company_id')
            ->get()
            ->pluck('count', 'company_id');

        // Map results back to company IDs
        foreach ($companyIds as $companyId) {
            $this->results[$companyId] = (int) ($counts->get($companyId) ?? 0);
        }

        $this->hasResolved = true;
    }
}
