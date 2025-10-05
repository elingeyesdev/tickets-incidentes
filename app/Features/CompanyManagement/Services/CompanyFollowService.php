<?php

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Events\CompanyFollowed;
use App\Features\CompanyManagement\Events\CompanyUnfollowed;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyFollowService
{
    /**
     * Maximum companies a user can follow.
     */
    const MAX_FOLLOWS = 50;

    /**
     * Follow a company.
     */
    public function follow(User $user, Company $company): CompanyFollower
    {
        // Check if already following
        if ($this->isFollowing($user, $company)) {
            throw new \Exception('You are already following this company');
        }

        // Check limit
        if ($this->getFollowedCount($user) >= self::MAX_FOLLOWS) {
            throw new \Exception(sprintf(
                'You have reached the maximum number of companies you can follow (%d)',
                self::MAX_FOLLOWS
            ));
        }

        return DB::transaction(function () use ($user, $company) {
            // Create follow record
            $follower = CompanyFollower::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);

            // Fire event
            event(new CompanyFollowed($user, $company));

            return $follower;
        });
    }

    /**
     * Unfollow a company.
     */
    public function unfollow(User $user, Company $company): bool
    {
        // Check if following
        if (!$this->isFollowing($user, $company)) {
            throw new \Exception('You are not following this company');
        }

        return DB::transaction(function () use ($user, $company) {
            // Delete follow record
            $deleted = CompanyFollower::where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->delete();

            // Fire event
            event(new CompanyUnfollowed($user, $company));

            return $deleted > 0;
        });
    }

    /**
     * Check if user is following a company.
     */
    public function isFollowing(User $user, Company $company): bool
    {
        return CompanyFollower::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->exists();
    }

    /**
     * Get companies followed by user.
     */
    public function getFollowedCompanies(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return CompanyFollower::where('user_id', $user->id)
            ->with('company')
            ->orderBy('followed_at', 'desc')
            ->get()
            ->pluck('company');
    }

    /**
     * Get follower records with metadata (for myFollowedCompanies query).
     */
    public function getFollowedWithMetadata(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return CompanyFollower::where('user_id', $user->id)
            ->with('company')
            ->orderBy('followed_at', 'desc')
            ->get();
    }

    /**
     * Get count of companies user is following.
     */
    public function getFollowedCount(User $user): int
    {
        return CompanyFollower::where('user_id', $user->id)->count();
    }

    /**
     * Get followers of a company.
     */
    public function getFollowers(Company $company): \Illuminate\Database\Eloquent\Collection
    {
        return CompanyFollower::where('company_id', $company->id)
            ->with('user')
            ->orderBy('followed_at', 'desc')
            ->get()
            ->pluck('user');
    }

    /**
     * Get followers count of a company.
     */
    public function getFollowersCount(Company $company): int
    {
        return CompanyFollower::where('company_id', $company->id)->count();
    }
}
