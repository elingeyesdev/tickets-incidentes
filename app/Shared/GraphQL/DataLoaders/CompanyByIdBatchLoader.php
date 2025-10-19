<?php declare(strict_types=1);

namespace App\Shared\GraphQL\DataLoaders;

use App\Features\CompanyManagement\Models\Company;
use GraphQL\Deferred;
use Illuminate\Database\Eloquent\Model;

/**
 * BatchLoader para cargar empresas por ID
 *
 * Implementa el patrón de Lighthouse 6 usando GraphQL\Deferred
 * para prevenir N+1 queries al cargar empresas en múltiples contextos.
 *
 * @see \Nuwave\Lighthouse\Execution\BatchLoader\RelationBatchLoader
 */
class CompanyByIdBatchLoader
{
    /**
     * Map from company_id to Company model instances that need loading
     *
     * @var array<string, \App\Features\CompanyManagement\Models\Company>
     */
    protected array $companies = [];

    /**
     * Map from company_id to loaded companies
     *
     * @var array<string, \App\Features\CompanyManagement\Models\Company|null>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading a company by ID
     *
     * Returns a Deferred that resolves to the Company when executed.
     *
     * @param string $companyId Company ID to load
     * @return \GraphQL\Deferred
     */
    public function load(string $companyId): Deferred
    {
        $this->companies[$companyId] = $companyId;

        return new Deferred(function () use ($companyId) {
            if (! $this->hasResolved) {
                $this->resolve();
            }

            return $this->results[$companyId] ?? null;
        });
    }

    /**
     * Resolve all queued companies in a single batch query
     */
    protected function resolve(): void
    {
        $companyIds = array_keys($this->companies);

        // Batch load all companies in one query
        $companies = Company::query()
            ->whereIn('id', $companyIds)
            ->get()
            ->keyBy('id');

        // Map results back to company IDs
        foreach ($companyIds as $companyId) {
            $this->results[$companyId] = $companies->get($companyId);
        }

        $this->hasResolved = true;
    }
}
