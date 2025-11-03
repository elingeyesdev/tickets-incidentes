<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Services;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\Role;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * VisibilityService
 *
 * Service responsible for determining visibility permissions for content.
 * Handles complex business rules around who can see what content based on:
 * - User roles (PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER)
 * - Following relationships (user_company_followers)
 * - Content status (DRAFT, PUBLISHED, etc.)
 *
 * Business Rules:
 * 1. PLATFORM_ADMIN can see ALL announcements (always return true)
 * 2. COMPANY_ADMIN can see all announcements from their company (bypass following)
 * 3. END_USER/AGENT can only see PUBLISHED announcements from companies they follow
 * 4. DRAFT announcements are only visible to COMPANY_ADMIN of the company
 */
class VisibilityService
{
    /**
     * Determine if a user can see an announcement.
     *
     * @param User $user The user requesting access
     * @param Announcement $announcement The announcement to check visibility for
     * @return bool True if user can see the announcement, false otherwise
     */
    public function canSeeAnnouncement(User $user, Announcement $announcement): bool
    {
        // 1. PLATFORM_ADMIN can see everything
        if ($this->isPlatformAdmin($user)) {
            return true;
        }

        // 2. DRAFT announcements only visible to COMPANY_ADMIN of the company
        if ($announcement->status === PublicationStatus::DRAFT) {
            return $this->isCompanyAdmin($user, $announcement->company_id);
        }

        // 3. COMPANY_ADMIN can see all announcements from their company
        if ($this->isCompanyAdmin($user, $announcement->company_id)) {
            return true;
        }

        // 4. Regular users can only see PUBLISHED announcements from companies they follow
        if ($announcement->status === PublicationStatus::PUBLISHED) {
            return $this->isFollowingCompany($user, $announcement->company_id);
        }

        // Default: deny access
        return false;
    }

    /**
     * Check if user has PLATFORM_ADMIN role.
     *
     * @param User $user
     * @return bool
     */
    private function isPlatformAdmin(User $user): bool
    {
        return DB::table('auth.user_roles')
            ->where('user_id', $user->id)
            ->where('role_code', Role::PLATFORM_ADMIN)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user is COMPANY_ADMIN for the specific company.
     *
     * @param User $user
     * @param string $companyId
     * @return bool
     */
    private function isCompanyAdmin(User $user, string $companyId): bool
    {
        return DB::table('auth.user_roles')
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->where('role_code', Role::COMPANY_ADMIN)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user is following the company.
     *
     * @param User $user
     * @param string $companyId
     * @return bool
     */
    private function isFollowingCompany(User $user, string $companyId): bool
    {
        return DB::table('business.user_company_followers')
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->exists();
    }
}
