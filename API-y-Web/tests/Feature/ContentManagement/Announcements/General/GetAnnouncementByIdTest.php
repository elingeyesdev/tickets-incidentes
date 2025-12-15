<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\General;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Get Announcement By ID - CAPA 3E
 *
 * Tests endpoint: GET /api/announcements/{id}
 *
 * Coverage:
 * - 4 real roles (PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER)
 * - Visibility rules per role (VisibilityService)
 * - Authorization and access control based on:
 *   - User role
 *   - Company ownership
 *   - Following companies relationship
 *   - Announcement status (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED)
 *
 * CRITICAL RULES:
 * ❌ NEVER use Announcement::factory() - use HTTP helpers instead
 * ❌ NEVER use transactions - use RefreshDatabaseWithoutTransactions trait
 * ✅ ALWAYS use User::factory()->withRole() for PLATFORM_ADMIN, AGENT, USER
 * ✅ ALWAYS use createCompanyAdmin() helper for COMPANY_ADMIN
 * ✅ ALWAYS use HTTP helpers for announcements: createMaintenanceAnnouncementViaHttp(), etc.
 * ✅ ALWAYS use authenticateWithJWT($user) for auth
 * ✅ ALWAYS use Role enum: Role::AGENT->value
 * ✅ ALWAYS verify visibility with VisibilityService rules
 *
 * Visibility Rules:
 * - PLATFORM_ADMIN: Can view ANY announcement from ANY company (all statuses)
 * - COMPANY_ADMIN: Can view ANY status from OWN company only
 * - AGENT: Can view ONLY PUBLISHED from followed companies
 * - USER: Can view ONLY PUBLISHED from followed companies
 */
class GetAnnouncementByIdTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test 1: AGENT can view PUBLISHED announcement from followed company
     *
     * Business Rule:
     * - AGENT can view PUBLISHED announcements
     * - Only from companies they follow (business.user_company_followers)
     * - Status must be PUBLISHED
     *
     * Expected: 200 OK with announcement data
     */
    #[Test]
    public function agent_can_view_published_announcement_from_followed_company(): void
    {
        // Arrange - Create company A with COMPANY_ADMIN
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        // Create AGENT in company A (agents are always part of a company)
        $agent = User::factory()->withRole(Role::AGENT->value, $companyA->id)->create();

        // Agent follows company A (business rule: agents follow their company)
        CompanyFollower::create(['user_id' => $agent->id, 'company_id' => $companyA->id]);

        // Create PUBLISHED announcement in company A via HTTP helper
        $announcement = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Published Maintenance for Agent'],
            'publish'
        );

        // Act - AGENT tries to view the PUBLISHED announcement
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/announcements/{$announcement->id}");

        // Assert - AGENT should see the PUBLISHED announcement
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $announcement->id)
            ->assertJsonPath('data.title', 'Published Maintenance for Agent')
            ->assertJsonPath('data.status', 'PUBLISHED')
            ->assertJsonPath('data.type', 'MAINTENANCE');
    }

    /**
     * Test 2: AGENT cannot view DRAFT announcement from own company
     *
     * Business Rule:
     * - AGENT can ONLY view PUBLISHED announcements
     * - DRAFT announcements are hidden from AGENT (even from own company)
     * - Only COMPANY_ADMIN and PLATFORM_ADMIN can see DRAFT
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function agent_cannot_view_draft_announcement_from_own_company(): void
    {
        // Arrange - Create company A with COMPANY_ADMIN
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        // Create AGENT in company A
        $agent = User::factory()->withRole(Role::AGENT->value, $companyA->id)->create();
        CompanyFollower::create(['user_id' => $agent->id, 'company_id' => $companyA->id]);

        // Create DRAFT announcement in company A
        $draftAnnouncement = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Draft Maintenance'],
            'draft'
        );

        // Act - AGENT tries to view the DRAFT announcement
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/announcements/{$draftAnnouncement->id}");

        // Assert - AGENT should be forbidden (403) because status is DRAFT
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions',
            ]);
    }

    /**
     * Test 3: AGENT cannot view announcement from non-followed company
     *
     * Business Rule:
     * - AGENT can only view announcements from companies they follow
     * - Even if announcement is PUBLISHED, must follow the company
     * - Following relationship is stored in business.user_company_followers
     *
     * Expected: 403 Forbidden (NotFollowingCompanyException)
     */
    #[Test]
    public function agent_cannot_view_announcement_from_non_followed_company(): void
    {
        // Arrange - Create company A and company B
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        $adminB = $this->createCompanyAdmin();
        $companyB = Company::where('admin_user_id', $adminB->id)->first();

        // Create AGENT in company A (does NOT follow company B)
        $agent = User::factory()->withRole(Role::AGENT->value, $companyA->id)->create();
        CompanyFollower::create(['user_id' => $agent->id, 'company_id' => $companyA->id]);
        // NOTE: Agent does NOT follow company B

        // Create PUBLISHED announcement in company B
        $announcementB = $this->createMaintenanceAnnouncementViaHttp(
            $adminB,
            ['title' => 'Company B Announcement'],
            'publish'
        );

        // Act - AGENT tries to view announcement from non-followed company B
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/announcements/{$announcementB->id}");

        // Assert - AGENT should be forbidden (403) - not following company
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions',
            ]);
    }

    /**
     * Test 4: USER can view PUBLISHED announcement from followed company
     *
     * Business Rule:
     * - USER has same visibility as AGENT
     * - Can view ONLY PUBLISHED announcements
     * - Only from companies they follow
     *
     * Expected: 200 OK with announcement data
     */
    #[Test]
    public function user_can_view_published_announcement_from_followed_company(): void
    {
        // Arrange - Create company A
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        // Create USER (not tied to any company)
        $user = User::factory()->withRole(Role::USER->value)->create();

        // USER follows company A
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $companyA->id]);

        // Create PUBLISHED announcement in company A
        $announcement = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Published for USER'],
            'publish'
        );

        // Act - USER tries to view the PUBLISHED announcement
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/announcements/{$announcement->id}");

        // Assert - USER should see the PUBLISHED announcement
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $announcement->id)
            ->assertJsonPath('data.title', 'Published for USER')
            ->assertJsonPath('data.status', 'PUBLISHED');
    }

    /**
     * Test 5: USER cannot view DRAFT announcement
     *
     * Business Rule:
     * - USER can ONLY view PUBLISHED announcements
     * - DRAFT, SCHEDULED, ARCHIVED are hidden from USER
     * - Even if USER follows the company
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_view_draft_announcement(): void
    {
        // Arrange - Create company A
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        // Create USER that follows company A
        $user = User::factory()->withRole(Role::USER->value)->create();
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $companyA->id]);

        // Create DRAFT announcement in company A
        $draftAnnouncement = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Draft for USER'],
            'draft'
        );

        // Act - USER tries to view the DRAFT announcement
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/announcements/{$draftAnnouncement->id}");

        // Assert - USER should be forbidden (403) because status is DRAFT
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions',
            ]);
    }

    /**
     * Test 6: COMPANY_ADMIN can view any status announcement from own company
     *
     * Business Rule:
     * - COMPANY_ADMIN can view ALL statuses (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED)
     * - But ONLY from their own company
     * - No restriction on status for own company
     *
     * Expected: 200 OK for all statuses
     */
    #[Test]
    public function company_admin_can_view_any_status_announcement_from_own_company(): void
    {
        // Arrange - Create COMPANY_ADMIN of company A
        $adminA = $this->createCompanyAdmin();

        // Create announcements in all statuses
        $draft = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Draft Announcement'],
            'draft'
        );

        $scheduled = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Scheduled Announcement'],
            'schedule',
            now()->addDay()->toIso8601String()
        );

        $published = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Published Announcement'],
            'publish'
        );

        // Manually create ARCHIVED (by updating PUBLISHED)
        $archived = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Archived Announcement'],
            'publish'
        );
        $archived->update(['status' => PublicationStatus::ARCHIVED->value]);

        // Act & Assert - COMPANY_ADMIN should view all 4 statuses

        // 1. DRAFT
        $response = $this->authenticateWithJWT($adminA)
            ->getJson("/api/announcements/{$draft->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'DRAFT');

        // 2. SCHEDULED
        $response = $this->authenticateWithJWT($adminA)
            ->getJson("/api/announcements/{$scheduled->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'SCHEDULED');

        // 3. PUBLISHED
        $response = $this->authenticateWithJWT($adminA)
            ->getJson("/api/announcements/{$published->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'PUBLISHED');

        // 4. ARCHIVED
        $response = $this->authenticateWithJWT($adminA)
            ->getJson("/api/announcements/{$archived->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'ARCHIVED');
    }

    /**
     * Test 7: COMPANY_ADMIN cannot view announcement from other company
     *
     * Business Rule:
     * - COMPANY_ADMIN can ONLY view announcements from their own company
     * - Cannot view announcements from other companies (even if PUBLISHED)
     * - Company ownership is checked via company_id
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function company_admin_cannot_view_announcement_from_other_company(): void
    {
        // Arrange - Create COMPANY_ADMIN of company A
        $adminA = $this->createCompanyAdmin();

        // Create COMPANY_ADMIN of company B
        $adminB = $this->createCompanyAdmin();

        // Create PUBLISHED announcement in company B
        $announcementB = $this->createMaintenanceAnnouncementViaHttp(
            $adminB,
            ['title' => 'Company B Announcement'],
            'publish'
        );

        // Act - Admin A tries to view announcement from company B
        $response = $this->authenticateWithJWT($adminA)
            ->getJson("/api/announcements/{$announcementB->id}");

        // Assert - Should be forbidden (403) - different company
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions',
            ]);
    }

    /**
     * Test 8: PLATFORM_ADMIN can view any announcement from any company
     *
     * Business Rule:
     * - PLATFORM_ADMIN has global read-only access
     * - Can view ALL statuses (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED)
     * - Can view from ANY company (no restriction)
     * - No need to follow companies
     *
     * Expected: 200 OK for all combinations
     */
    #[Test]
    public function platform_admin_can_view_any_announcement_from_any_company(): void
    {
        // Arrange - Create PLATFORM_ADMIN
        $platformAdmin = User::factory()->withRole(Role::PLATFORM_ADMIN->value)->create();

        // Create company A and company B
        $adminA = $this->createCompanyAdmin();
        $adminB = $this->createCompanyAdmin();

        // Create announcements in different statuses across companies
        $draftA = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Company A - Draft'],
            'draft'
        );

        $publishedA = $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Company A - Published'],
            'publish'
        );

        $scheduledB = $this->createMaintenanceAnnouncementViaHttp(
            $adminB,
            ['title' => 'Company B - Scheduled'],
            'schedule',
            now()->addDay()->toIso8601String()
        );

        $publishedB = $this->createMaintenanceAnnouncementViaHttp(
            $adminB,
            ['title' => 'Company B - Published'],
            'publish'
        );

        // Manually create ARCHIVED in company B
        $archivedB = $this->createMaintenanceAnnouncementViaHttp(
            $adminB,
            ['title' => 'Company B - Archived'],
            'publish'
        );
        $archivedB->update(['status' => PublicationStatus::ARCHIVED->value]);

        // Act & Assert - PLATFORM_ADMIN should view ALL announcements

        // 1. DRAFT from Company A
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson("/api/announcements/{$draftA->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Company A - Draft')
            ->assertJsonPath('data.status', 'DRAFT');

        // 2. PUBLISHED from Company A
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson("/api/announcements/{$publishedA->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Company A - Published');

        // 3. SCHEDULED from Company B
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson("/api/announcements/{$scheduledB->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Company B - Scheduled')
            ->assertJsonPath('data.status', 'SCHEDULED');

        // 4. PUBLISHED from Company B
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson("/api/announcements/{$publishedB->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Company B - Published');

        // 5. ARCHIVED from Company B
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson("/api/announcements/{$archivedB->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Company B - Archived')
            ->assertJsonPath('data.status', 'ARCHIVED');
    }
}
