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
 * Feature Tests for List Announcements - CAPA 3E
 *
 * Tests endpoint: GET /api/announcements
 *
 * Coverage:
 * - 4 real roles (PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER) - NO END_USER
 * - Visibility rules per role (VisibilityService)
 * - Query filters: status, type, search, sort, pagination, date ranges
 * - Following companies relationship (business.user_company_followers)
 * - Authorization and access control
 *
 * CRITICAL RULES:
 * ❌ NEVER use Announcement::factory() - use HTTP helpers instead
 * ❌ NEVER use transactions - use RefreshDatabaseWithoutTransactions trait
 * ✅ ALWAYS use User::factory()->withRole() for PLATFORM_ADMIN, AGENT, USER
 * ✅ ALWAYS use createCompanyAdmin() helper for COMPANY_ADMIN
 * ✅ ALWAYS use HTTP helpers for announcements: createMaintenanceAnnouncementViaHttp(), etc.
 * ✅ ALWAYS use authenticateWithJWT($user) for auth
 * ✅ ALWAYS use Role enum: Role::COMPANY_ADMIN->value
 * ✅ ALWAYS verify visibility with VisibilityService rules
 */
class ListAnnouncementsTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test 1: PLATFORM_ADMIN can list ALL announcements from ALL companies
     *
     * Business Rule:
     * - PLATFORM_ADMIN sees EVERYTHING (read-only)
     * - Sees all statuses: DRAFT, SCHEDULED, PUBLISHED, ARCHIVED
     * - Sees all companies without restriction
     */
    #[Test]
    public function platform_admin_can_list_all_announcements(): void
    {
        // Arrange - Create PLATFORM_ADMIN
        $platformAdmin = User::factory()->withRole(Role::PLATFORM_ADMIN->value)->create();

        // Create 2 companies with different admins
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        $adminB = $this->createCompanyAdmin();
        $companyB = Company::where('admin_user_id', $adminB->id)->first();

        // Create announcements in Company A (DRAFT, PUBLISHED, SCHEDULED)
        $this->createMaintenanceAnnouncementViaHttp($adminA, ['title' => 'Company A - Draft'], 'draft');
        $this->createMaintenanceAnnouncementViaHttp($adminA, ['title' => 'Company A - Published'], 'publish');
        $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'Company A - Scheduled'],
            'schedule',
            now()->addDay()->toIso8601String()
        );

        // Create announcements in Company B (PUBLISHED only)
        $this->createMaintenanceAnnouncementViaHttp($adminB, ['title' => 'Company B - Published'], 'publish');

        // Act - PLATFORM_ADMIN lists all announcements
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson('/api/announcements');

        // Assert - Should see ALL 4 announcements from both companies
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertGreaterThanOrEqual(4, count($data), 'PLATFORM_ADMIN should see at least 4 announcements');

        $titles = collect($data)->pluck('title')->toArray();
        $this->assertContains('Company A - Draft', $titles);
        $this->assertContains('Company A - Published', $titles);
        $this->assertContains('Company A - Scheduled', $titles);
        $this->assertContains('Company B - Published', $titles);
    }

    /**
     * Test 2: COMPANY_ADMIN can see all announcements of OWN company only
     *
     * Business Rule:
     * - COMPANY_ADMIN sees ALL statuses but ONLY from their company
     * - Cannot see announcements from other companies
     */
    #[Test]
    public function company_admin_can_see_all_announcements_of_own_company(): void
    {
        // Arrange - Create COMPANY_ADMIN of company A
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        // Create announcements in company A (all statuses)
        $this->createMaintenanceAnnouncementViaHttp($adminA, ['title' => 'A - Draft'], 'draft');
        $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'A - Scheduled'],
            'schedule',
            now()->addDay()->toIso8601String()
        );
        $this->createMaintenanceAnnouncementViaHttp($adminA, ['title' => 'A - Published'], 'publish');

        // Archive one announcement manually
        $archived = Announcement::where('title', 'A - Published')->first();
        $archived->update(['status' => PublicationStatus::ARCHIVED->value]);

        // Create announcements in company B (should NOT see these)
        $adminB = $this->createCompanyAdmin();
        $this->createMaintenanceAnnouncementViaHttp($adminB, ['title' => 'B - Published'], 'publish');

        // Act
        $response = $this->authenticateWithJWT($adminA)
            ->getJson('/api/announcements');

        // Assert - Should see ONLY 3 from company A (DRAFT, SCHEDULED, ARCHIVED), NOT company B
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(3, count($data), 'Should see exactly 3 announcements from own company');

        $titles = collect($data)->pluck('title')->toArray();
        $this->assertContains('A - Draft', $titles);
        $this->assertContains('A - Scheduled', $titles);
        $this->assertContains('A - Published', $titles);
        $this->assertNotContains('B - Published', $titles, 'Should NOT see other company announcements');
    }

    /**
     * Test 3: COMPANY_ADMIN cannot see announcements from other companies
     *
     * Business Rule:
     * - Attempting to access other companies should result in 403 or empty results
     */
    #[Test]
    public function company_admin_cannot_see_announcements_from_other_companies(): void
    {
        // Arrange - Create COMPANY_ADMIN of company A
        $adminA = $this->createCompanyAdmin();

        // Create announcements in company B
        $adminB = $this->createCompanyAdmin();
        $companyB = Company::where('admin_user_id', $adminB->id)->first();
        $this->createMaintenanceAnnouncementViaHttp($adminB, ['title' => 'B - Published'], 'publish');

        // Act - Try to filter by company B (if endpoint supports it)
        // NOTE: The controller should ignore or reject company_id filter for non-admins
        $response = $this->authenticateWithJWT($adminA)
            ->getJson("/api/announcements?company_id={$companyB->id}");

        // Assert - Should either be 403 or return empty results (depending on implementation)
        // For now, we expect the filter to be ignored and return only own company
        $response->assertStatus(200);
        $data = $response->json('data');

        // Should NOT contain any announcements from company B
        $titles = collect($data)->pluck('title')->toArray();
        $this->assertNotContains('B - Published', $titles);
    }

    /**
     * Test 4: AGENT can list PUBLISHED announcements from followed companies
     *
     * Business Rule:
     * - AGENT sees ONLY PUBLISHED announcements
     * - ONLY from companies they follow (business.user_company_followers)
     * - Cannot see DRAFT, SCHEDULED, or ARCHIVED
     */
    #[Test]
    public function agent_can_list_published_from_followed_companies(): void
    {
        // Arrange - Create AGENT in company A
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        $agent = User::factory()->withRole(Role::AGENT->value, $companyA->id)->create();

        // Create company B
        $adminB = $this->createCompanyAdmin();
        $companyB = Company::where('admin_user_id', $adminB->id)->first();

        // AGENT follows company A and B
        CompanyFollower::create(['user_id' => $agent->id, 'company_id' => $companyA->id]);
        CompanyFollower::create(['user_id' => $agent->id, 'company_id' => $companyB->id]);

        // Create announcements in company A
        $this->createMaintenanceAnnouncementViaHttp($adminA, ['title' => 'A - Draft'], 'draft');
        $this->createMaintenanceAnnouncementViaHttp($adminA, ['title' => 'A - Published'], 'publish');

        // Create announcements in company B
        $this->createMaintenanceAnnouncementViaHttp($adminB, ['title' => 'B - Published'], 'publish');

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->getJson('/api/announcements');

        // Assert - Should see ONLY PUBLISHED from A and B, NOT DRAFT
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(2, count($data), 'AGENT should see 2 PUBLISHED announcements');

        $titles = collect($data)->pluck('title')->toArray();
        $this->assertContains('A - Published', $titles);
        $this->assertContains('B - Published', $titles);
        $this->assertNotContains('A - Draft', $titles, 'AGENT should NOT see DRAFT');
    }

    /**
     * Test 5: USER can list PUBLISHED announcements from followed companies
     *
     * Business Rule:
     * - USER sees ONLY PUBLISHED announcements
     * - ONLY from companies they follow
     * - Same visibility as AGENT
     */
    #[Test]
    public function user_can_list_published_from_followed_companies(): void
    {
        // Arrange - Create USER
        $user = User::factory()->withRole(Role::USER->value)->create();

        // Create companies
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        $adminB = $this->createCompanyAdmin();
        $companyB = Company::where('admin_user_id', $adminB->id)->first();

        // USER follows company A and B
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $companyA->id]);
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $companyB->id]);

        // Create announcements
        $this->createMaintenanceAnnouncementViaHttp($adminA, ['title' => 'A - Published'], 'publish');
        $this->createMaintenanceAnnouncementViaHttp($adminB, ['title' => 'B - Published'], 'publish');

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/announcements');

        // Assert - Should see PUBLISHED from followed companies
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(2, count($data), 'USER should see 2 PUBLISHED announcements');

        $titles = collect($data)->pluck('title')->toArray();
        $this->assertContains('A - Published', $titles);
        $this->assertContains('B - Published', $titles);
    }

    /**
     * Test 6: USER cannot see announcements from non-followed companies
     *
     * Business Rule:
     * - If USER doesn't follow a company, they cannot see its announcements
     */
    #[Test]
    public function user_cannot_see_announcements_from_non_followed_companies(): void
    {
        // Arrange - Create USER (does NOT follow any company)
        $user = User::factory()->withRole(Role::USER->value)->create();

        // Create company C (not followed)
        $adminC = $this->createCompanyAdmin();
        $this->createMaintenanceAnnouncementViaHttp($adminC, ['title' => 'C - Published'], 'publish');

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/announcements');

        // Assert - Should return empty array (no followed companies)
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEmpty($data, 'USER should see no announcements if not following any company');
    }

    /**
     * Test 7: AGENT cannot see DRAFT or SCHEDULED announcements
     *
     * Business Rule:
     * - AGENT sees ONLY PUBLISHED status
     * - DRAFT, SCHEDULED, ARCHIVED are hidden
     */
    #[Test]
    public function agent_cannot_see_draft_or_scheduled_announcements(): void
    {
        // Arrange - Create AGENT
        $adminA = $this->createCompanyAdmin();
        $companyA = Company::where('admin_user_id', $adminA->id)->first();

        $agent = User::factory()->withRole(Role::AGENT->value, $companyA->id)->create();
        CompanyFollower::create(['user_id' => $agent->id, 'company_id' => $companyA->id]);

        // Create non-PUBLISHED announcements
        $this->createMaintenanceAnnouncementViaHttp($adminA, ['title' => 'A - Draft'], 'draft');
        $this->createMaintenanceAnnouncementViaHttp(
            $adminA,
            ['title' => 'A - Scheduled'],
            'schedule',
            now()->addDay()->toIso8601String()
        );

        // Create and archive one
        $archived = $this->createMaintenanceAnnouncementViaHttp($adminA, ['title' => 'A - Archived'], 'publish');
        $archived->update(['status' => PublicationStatus::ARCHIVED->value]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->getJson('/api/announcements');

        // Assert - Should return empty (AGENT only sees PUBLISHED)
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEmpty($data, 'AGENT should NOT see DRAFT, SCHEDULED, or ARCHIVED');
    }

    /**
     * Test 8: Filter by status works for COMPANY_ADMIN
     *
     * Business Rule:
     * - Query parameter ?status=published should filter by status
     */
    #[Test]
    public function filter_by_status_works(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create 2 DRAFT, 2 PUBLISHED, 1 SCHEDULED
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Draft 1'], 'draft');
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Draft 2'], 'draft');
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Published 1'], 'publish');
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Published 2'], 'publish');
        $this->createMaintenanceAnnouncementViaHttp(
            $admin,
            ['title' => 'Scheduled 1'],
            'schedule',
            now()->addDay()->toIso8601String()
        );

        // Act - Filter by PUBLISHED
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements?status=published');

        // Assert - Should return only 2 PUBLISHED
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(2, count($data), 'Should return exactly 2 PUBLISHED announcements');

        foreach ($data as $announcement) {
            $this->assertEquals('PUBLISHED', $announcement['status']);
        }
    }

    /**
     * Test 9: Filter by type works for COMPANY_ADMIN
     *
     * Business Rule:
     * - Query parameter ?type=MAINTENANCE should filter by announcement type
     */
    #[Test]
    public function filter_by_type_works(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create 2 MAINTENANCE, 1 INCIDENT, 1 NEWS, 1 ALERT
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Maintenance 1'], 'publish');
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Maintenance 2'], 'publish');
        $this->createIncidentAnnouncementViaHttp($admin, ['title' => 'Incident 1'], 'publish');
        $this->createNewsAnnouncementViaHttp($admin, ['title' => 'News 1'], 'publish');
        $this->createAlertAnnouncementViaHttp($admin, ['title' => 'Alert 1'], 'publish');

        // Act - Filter by MAINTENANCE
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements?type=MAINTENANCE');

        // Assert - Should return only 2 MAINTENANCE
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(2, count($data), 'Should return exactly 2 MAINTENANCE announcements');

        foreach ($data as $announcement) {
            $this->assertEquals('MAINTENANCE', $announcement['type']);
        }
    }

    /**
     * Test 10: Filter by multiple criteria works
     *
     * Business Rule:
     * - Multiple query parameters should combine (AND logic)
     */
    #[Test]
    public function filter_by_multiple_criteria(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create mix of announcements
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Maintenance Draft'], 'draft');
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Maintenance Published'], 'publish');
        $this->createIncidentAnnouncementViaHttp($admin, ['title' => 'Incident Published'], 'publish');

        // Act - Filter by status=published AND type=INCIDENT
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements?status=published&type=INCIDENT');

        // Assert - Should return only 1 (PUBLISHED + INCIDENT)
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(1, count($data), 'Should return exactly 1 announcement matching both criteria');
        $this->assertEquals('Incident Published', $data[0]['title']);
        $this->assertEquals('PUBLISHED', $data[0]['status']);
        $this->assertEquals('INCIDENT', $data[0]['type']);
    }

    /**
     * Test 11: Search by title works
     *
     * Business Rule:
     * - Query parameter ?search=keyword should search in title (case-insensitive)
     */
    #[Test]
    public function search_by_title_works(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create announcements with specific titles
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Mantenimiento DB'], 'publish');
        $this->createIncidentAnnouncementViaHttp($admin, ['title' => 'Incidente Login'], 'publish');
        $this->createNewsAnnouncementViaHttp($admin, ['title' => 'Nueva Feature'], 'publish');

        // Act - Search for "mantenimiento"
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements?search=mantenimiento');

        // Assert - Should return only "Mantenimiento DB"
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(1, count($data), 'Should find 1 announcement with "mantenimiento" in title');
        $this->assertStringContainsStringIgnoringCase('mantenimiento', $data[0]['title']);
    }

    /**
     * Test 12: Search by content works
     *
     * Business Rule:
     * - Search should also match content field, not just title
     */
    #[Test]
    public function search_by_content_works(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create announcements with specific content
        $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Standard Maintenance',
            'content' => 'This is a contenido_único that should be searchable',
        ], 'publish');
        $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Another Maintenance',
            'content' => 'Regular content here',
        ], 'publish');

        // Act - Search for unique content
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements?search=contenido_único');

        // Assert - Should find the announcement with unique content
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertGreaterThanOrEqual(1, count($data), 'Should find at least 1 announcement with unique content');

        // Verify at least one result matches
        $found = false;
        foreach ($data as $announcement) {
            if (stripos($announcement['content'] ?? '', 'contenido_único') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Search should match content field');
    }

    /**
     * Test 13: Sort by published_at DESC is default
     *
     * Business Rule:
     * - Default sorting should be -published_at (most recent first)
     * - Announcements without published_at should come last
     */
    #[Test]
    public function sort_by_published_at_desc_default(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create 3 announcements with different published_at times
        $old = $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Old'], 'publish');
        sleep(1);
        $middle = $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Middle'], 'publish');
        sleep(1);
        $recent = $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Recent'], 'publish');

        // Act - Get without sort parameter (should default to -published_at)
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements');

        // Assert - Should be ordered by published_at DESC
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertGreaterThanOrEqual(3, count($data));

        // Find our announcements in the results
        $titles = collect($data)->pluck('title')->toArray();
        $recentIndex = array_search('Recent', $titles);
        $middleIndex = array_search('Middle', $titles);
        $oldIndex = array_search('Old', $titles);

        // Recent should come before Middle, Middle before Old
        $this->assertLessThan($middleIndex, $recentIndex, 'Recent should appear before Middle');
        $this->assertLessThan($oldIndex, $middleIndex, 'Middle should appear before Old');
    }

    /**
     * Test 14: Pagination works correctly
     *
     * Business Rule:
     * - Query parameters ?per_page=10&page=2 should paginate results
     * - Default per_page is 20, max is 100
     */
    #[Test]
    public function pagination_works_correctly(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create 25 announcements
        for ($i = 1; $i <= 25; $i++) {
            $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => "Announcement {$i}"], 'publish');
        }

        // Act - Request page 2 with 10 items per page
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements?per_page=10&page=2');

        // Assert - Should return correct pagination structure
        $response->assertStatus(200);

        $data = $response->json('data');
        $meta = $response->json('meta');

        $this->assertEquals(10, count($data), 'Should return 10 items on page 2');
        $this->assertEquals(25, $meta['total'], 'Total should be 25');
        $this->assertEquals(2, $meta['current_page'], 'Current page should be 2');
        $this->assertEquals(3, $meta['last_page'], 'Last page should be 3 (25/10 = 2.5 → 3)');
    }

    /**
     * Test 15: Unauthenticated user cannot list announcements
     *
     * Business Rule:
     * - Endpoint requires authentication
     * - Should return 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_list_announcements(): void
    {
        // Arrange - Create some announcements
        $admin = $this->createCompanyAdmin();
        $this->createMaintenanceAnnouncementViaHttp($admin, ['title' => 'Test'], 'publish');

        // Act - Try to access without token
        $response = $this->getJson('/api/announcements');

        // Assert - Should be 401 Unauthorized
        $response->assertStatus(401);
    }
}
