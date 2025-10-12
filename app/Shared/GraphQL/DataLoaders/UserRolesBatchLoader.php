<?php declare(strict_types=1);

namespace App\Shared\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\UserRole;
use GraphQL\Deferred;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * BatchLoader para cargar roles activos de usuarios
 *
 * Implementa el patrón de Lighthouse 6 usando GraphQL\Deferred
 * para prevenir N+1 queries al cargar roles de múltiples usuarios.
 *
 * @see \Nuwave\Lighthouse\Execution\BatchLoader\RelationBatchLoader
 */
class UserRolesBatchLoader
{
    /**
     * Map from user_id to User model instances that need roles loading
     *
     * @var array<string, \App\Features\UserManagement\Models\User>
     */
    protected array $users = [];

    /**
     * Map from user_id to loaded roles collections
     *
     * @var array<string, \Illuminate\Support\Collection>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading roles for a user
     *
     * Returns a Deferred that resolves to a Collection of UserRole when executed.
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

            return $this->results[$userId] ?? collect();
        });
    }

    /**
     * Resolve all queued roles in a single batch query
     */
    protected function resolve(): void
    {
        $userIds = array_keys($this->users);

        // Batch load all active roles with relationships in one query
        $userRoles = UserRole::query()
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->with(['role', 'company']) // Eager load role AND company
            ->get()
            ->groupBy('user_id');

        // Map results back to user IDs
        foreach ($userIds as $userId) {
            $this->results[$userId] = $userRoles->get($userId, collect());
        }

        $this->hasResolved = true;
    }
}
