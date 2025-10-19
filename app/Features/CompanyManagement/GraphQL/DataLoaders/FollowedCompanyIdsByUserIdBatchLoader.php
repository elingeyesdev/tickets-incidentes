<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\DataLoaders;

use App\Features\CompanyManagement\Models\CompanyFollower;
use GraphQL\Deferred;

/**
 * BatchLoader para cargar IDs de empresas seguidas por usuarios
 *
 * Implementa el patrón de Lighthouse 6 usando GraphQL\Deferred
 * para prevenir N+1 queries al verificar isFollowedByMe en listas de empresas.
 *
 * Usado en:
 * - CompaniesQuery (contexto EXPLORE) - campo isFollowedByMe
 * - CompanyQuery - campo isFollowedByMe
 * - CompanyForFollowing.isFollowedByMe
 *
 * @example
 * ```php
 * // En CompaniesQuery.php (contexto EXPLORE):
 * $loader = app(FollowedCompanyIdsByUserIdBatchLoader::class);
 * $followedIds = $loader->load($user->id);
 *
 * foreach ($companies as $company) {
 *     $company->isFollowedByMe = in_array($company->id, $followedIds);
 * }
 * ```
 */
class FollowedCompanyIdsByUserIdBatchLoader
{
    /**
     * Map from user_id to user IDs that need loading
     *
     * @var array<string, string>
     */
    protected array $userIds = [];

    /**
     * Map from user_id to array of company IDs
     *
     * @var array<string, array<string>>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading followed company IDs for a user
     *
     * Returns a Deferred that resolves to an array of company IDs.
     *
     * @param string $userId User ID to load followed companies for
     * @return \GraphQL\Deferred
     */
    public function load(string $userId): Deferred
    {
        $this->userIds[$userId] = $userId;

        return new Deferred(function () use ($userId) {
            if (! $this->hasResolved) {
                $this->resolve();
            }

            return $this->results[$userId] ?? [];
        });
    }

    /**
     * Resolve all queued user IDs to their followed company IDs in a single batch query
     */
    protected function resolve(): void
    {
        $userIds = array_keys($this->userIds);

        // Batch load all follows in one query
        $follows = CompanyFollower::query()
            ->whereIn('user_id', $userIds)
            ->select(['user_id', 'company_id'])
            ->get()
            ->groupBy('user_id');

        // Map results back to user IDs
        foreach ($userIds as $userId) {
            $userFollows = $follows->get($userId);
            $this->results[$userId] = $userFollows
                ? $userFollows->pluck('company_id')->toArray()
                : [];
        }

        $this->hasResolved = true;
    }
}
