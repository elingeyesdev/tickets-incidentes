<?php declare(strict_types=1);

namespace App\Shared\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\User;
use GraphQL\Deferred;

/**
 * BatchLoader para cargar usuarios por ID
 *
 * Implementa el patrón de Lighthouse 6 usando GraphQL\Deferred
 * para prevenir N+1 queries al cargar usuarios en múltiples contextos.
 *
 * Usado en:
 * - Company.admin (admin_user_id)
 * - CompanyRequest.reviewer (reviewed_by_user_id)
 * - Ticket.author, assignee, etc. (futuro)
 *
 * @example
 * ```php
 * // En un resolver:
 * $loader = app(\App\Shared\GraphQL\DataLoaders\UserByIdBatchLoader::class);
 * return $loader->load($root->user_id);
 * ```
 */
class UserByIdLoader
{
    /**
     * Map from user_id to user IDs that need loading
     *
     * @var array<string, string>
     */
    protected array $users = [];

    /**
     * Map from user_id to loaded User models
     *
     * @var array<string, \App\Features\UserManagement\Models\User|null>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading a user by ID
     *
     * Returns a Deferred that resolves to the User when executed.
     *
     * @param string $userId User ID to load
     * @return \GraphQL\Deferred
     */
    public function load(string $userId): Deferred
    {
        $this->users[$userId] = $userId;

        return new Deferred(function () use ($userId) {
            if (! $this->hasResolved) {
                $this->resolve();
            }

            return $this->results[$userId] ?? null;
        });
    }

    /**
     * Resolve all queued users in a single batch query
     *
     * Eagerly loads user profiles to prevent additional N+1
     */
    protected function resolve(): void
    {
        $userIds = array_keys($this->users);

        // Batch load all users with profiles in one query
        $users = User::query()
            ->whereIn('id', $userIds)
            ->with('profile') // Eager load profiles to prevent N+1
            ->get()
            ->keyBy('id');

        // Map results back to user IDs
        foreach ($userIds as $userId) {
            $this->results[$userId] = $users->get($userId);
        }

        $this->hasResolved = true;
    }
}