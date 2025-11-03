<?php

declare(strict_types=1);

namespace Tests\Unit\ContentManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Services\VisibilityService;
use App\Features\UserManagement\Models\Role;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for VisibilityService
 *
 * Tests the visibility logic for announcements based on:
 * - User roles (PLATFORM_ADMIN, COMPANY_ADMIN, END_USER, AGENT)
 * - Following relationships (user_company_followers)
 * - Announcement status (DRAFT, PUBLISHED)
 *
 * Total: 5 tests
 */
class VisibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private VisibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VisibilityService::class);
    }

    #[Test]
    public function user_can_see_announcement_when_following_company(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $announcement = Announcement::factory()->published()->create([
            'company_id' => $company->id,
        ]);

        // Create following relationship
        DB::table('business.user_company_followers')->insert([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'followed_at' => now(),
        ]);

        // Act
        $canSee = $this->service->canSeeAnnouncement($user, $announcement);

        // Assert
        $this->assertTrue($canSee);
    }

    #[Test]
    public function user_cannot_see_announcement_when_not_following(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $announcement = Announcement::factory()->published()->create([
            'company_id' => $company->id,
        ]);

        // Verify user is NOT following the company (no entry in user_company_followers)
        $followExists = DB::table('business.user_company_followers')
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->exists();

        $this->assertFalse($followExists, 'User should not be following the company');

        // Act
        $canSee = $this->service->canSeeAnnouncement($user, $announcement);

        // Assert
        $this->assertFalse($canSee);
    }

    #[Test]
    public function company_admin_can_always_see_own_company_announcements(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $companyAdmin = $this->makeUserWithRole(Role::COMPANY_ADMIN, $company);
        $announcement = Announcement::factory()->published()->create([
            'company_id' => $company->id,
        ]);

        // Verify user is NOT following the company (no entry in user_company_followers)
        $followExists = DB::table('business.user_company_followers')
            ->where('user_id', $companyAdmin->id)
            ->where('company_id', $company->id)
            ->exists();

        $this->assertFalse($followExists, 'Company admin should not need to follow their own company');

        // Act
        $canSee = $this->service->canSeeAnnouncement($companyAdmin, $announcement);

        // Assert
        $this->assertTrue($canSee, 'Company admin should see their own company announcements without following');
    }

    #[Test]
    public function platform_admin_can_see_any_announcement(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $platformAdmin = $this->makeUserWithRole(Role::PLATFORM_ADMIN);
        $announcement = Announcement::factory()->published()->create([
            'company_id' => $company->id,
        ]);

        // Verify user is NOT following the company (no entry in user_company_followers)
        $followExists = DB::table('business.user_company_followers')
            ->where('user_id', $platformAdmin->id)
            ->where('company_id', $company->id)
            ->exists();

        $this->assertFalse($followExists, 'Platform admin should not need to follow any company');

        // Act
        $canSee = $this->service->canSeeAnnouncement($platformAdmin, $announcement);

        // Assert
        $this->assertTrue($canSee, 'Platform admin should see all announcements from any company');
    }

    #[Test]
    public function draft_announcement_only_visible_to_company_admin(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $companyAdmin = $this->makeUserWithRole(Role::COMPANY_ADMIN, $company);
        $endUser = User::factory()->create();

        // Create DRAFT announcement
        $draftAnnouncement = Announcement::factory()->create([
            'company_id' => $company->id,
            'status' => PublicationStatus::DRAFT,
            'published_at' => null,
        ]);

        // Create following relationship for END_USER
        DB::table('business.user_company_followers')->insert([
            'user_id' => $endUser->id,
            'company_id' => $company->id,
            'followed_at' => now(),
        ]);

        // Act - Test END_USER (even if following)
        $endUserCanSee = $this->service->canSeeAnnouncement($endUser, $draftAnnouncement);

        // Act - Test COMPANY_ADMIN of same company
        $companyAdminCanSee = $this->service->canSeeAnnouncement($companyAdmin, $draftAnnouncement);

        // Assert
        $this->assertFalse($endUserCanSee, 'END_USER should NOT see DRAFT announcements even if following');
        $this->assertTrue($companyAdminCanSee, 'COMPANY_ADMIN should see DRAFT announcements from their company');
    }

    /**
     * Helper method to create a user with a specific role
     *
     * @param string $roleCode Role code (PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER)
     * @param Company|null $company Company for company-scoped roles
     * @return User
     */
    private function makeUserWithRole(string $roleCode, ?Company $company = null): User
    {
        $user = User::factory()->create();

        // Verify role exists in auth.roles table
        $roleExists = DB::table('auth.roles')
            ->where('role_code', $roleCode)
            ->exists();

        if (!$roleExists) {
            $this->fail("Role with code '{$roleCode}' not found. Make sure seeders are run.");
        }

        // Insert user_role relationship
        DB::table('auth.user_roles')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'user_id' => $user->id,
            'role_code' => $roleCode,
            'company_id' => $company?->id,
            'is_active' => true,
            'assigned_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
