<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\DataLoaders;

use App\Features\CompanyManagement\Models\CompanyFollower;
use GraphQL\Deferred;
use Illuminate\Support\Collection;

/**
 * BatchLoader para cargar empresas seguidas por usuarios
 *
 * Implementa el patrón de Lighthouse 6 usando GraphQL\Deferred
 * para prevenir N+1 queries al obtener myFollowedCompanies.
 *
 * Retorna CompanyFollower con Company eager loaded y estadísticas personalizadas.
 *
 * Usado en:
 * - MyFollowedCompaniesQuery
 * - CompanyFollowInfo resolver
 *
 * @example
 * ```php
 * // En MyFollowedCompaniesQuery.php:
 * $loader = app(FollowedCompaniesByUserIdBatchLoader::class);
 * return $loader->load($user->id);
 * ```
 */
class FollowedCompaniesByUserIdLoader
{
    /**
     * Map from user_id to user IDs that need loading
     *
     * @var array<string, string>
     */
    protected array $userIds = [];

    /**
     * Map from user_id to Collection of CompanyFollower models with Company eager loaded
     *
     * @var array<string, \Illuminate\Support\Collection>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading followed companies for a user
     *
     * Returns a Deferred that resolves to a Collection of CompanyFollower.
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

            return $this->results[$userId] ?? collect();
        });
    }

    /**
     * Resolve all queued user IDs to their followed companies in a single batch query
     */
    protected function resolve(): void
    {
        $userIds = array_keys($this->userIds);

        // Batch load all follows with companies in one query
        $follows = CompanyFollower::query()
            ->whereIn('user_id', $userIds)
            ->with('company') // Eager load to prevent N+1
            ->orderBy('followed_at', 'desc')
            ->get();

        // Add user-specific stats to each follow
        $followsWithStats = $follows->map(function ($follow) {
            // TODO: Implement when TicketManagement is ready
            /*
            $myTicketsCount = \App\Features\TicketManagement\Models\Ticket::query()
                ->where('author_id', $follow->user_id)
                ->where('company_id', $follow->company_id)
                ->count();

            $lastTicket = \App\Features\TicketManagement\Models\Ticket::query()
                ->where('author_id', $follow->user_id)
                ->where('company_id', $follow->company_id)
                ->latest()
                ->first();

            $follow->my_tickets_count = $myTicketsCount;
            $follow->last_ticket_created_at = $lastTicket?->created_at;
            */

            // Temporary values until TicketManagement is implemented
            $follow->my_tickets_count = 0;
            $follow->last_ticket_created_at = null;
            $follow->has_unread_announcements = false; // TODO: implement when Announcements is ready

            return $follow;
        });

        // Group by user_id
        $followsByUser = $followsWithStats->groupBy('user_id');

        // Map results back to user IDs
        foreach ($userIds as $userId) {
            $this->results[$userId] = $followsByUser->get($userId, collect());
        }

        $this->hasResolved = true;
    }
}
