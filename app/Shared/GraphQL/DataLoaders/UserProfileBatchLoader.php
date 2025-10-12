<?php declare(strict_types=1);

namespace App\Shared\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\UserProfile;
use GraphQL\Deferred;
use Illuminate\Database\Eloquent\Model;

/**
 * BatchLoader para cargar perfiles de usuarios
 *
 * Implementa el patrón de Lighthouse 6 usando GraphQL\Deferred
 * para prevenir N+1 queries al cargar perfiles de múltiples usuarios.
 *
 * @see \Nuwave\Lighthouse\Execution\BatchLoader\RelationBatchLoader
 */
class UserProfileBatchLoader
{
    /**
     * Map from user_id to User model instances that need profile loading
     *
     * @var array<string, \App\Features\UserManagement\Models\User>
     */
    protected array $users = [];

    /**
     * Map from user_id to loaded profiles
     *
     * @var array<string, \App\Features\UserManagement\Models\UserProfile|null>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading a profile for a user
     *
     * Returns a Deferred that resolves to the UserProfile when executed.
     *
     * @param \App\Features\UserManagement\Models\User $user
     * @return \GraphQL\Deferred
     */
    public function load(Model $user): Deferred
    {
        $userId = $user->id;
        $this->users[$userId] = $user;

        return new Deferred(function () use ($userId) {
            if (! $this->hasResolved) {
                $this->resolve();
            }

            return $this->results[$userId] ?? null;
        });
    }

    /**
     * Resolve all queued profiles in a single batch query
     */
    protected function resolve(): void
    {
        $userIds = array_keys($this->users);

        // Batch load all profiles in one query
        $profiles = UserProfile::query()
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        // Map results back to user IDs
        foreach ($userIds as $userId) {
            $this->results[$userId] = $profiles->get($userId);
        }

        $this->hasResolved = true;
    }
}
