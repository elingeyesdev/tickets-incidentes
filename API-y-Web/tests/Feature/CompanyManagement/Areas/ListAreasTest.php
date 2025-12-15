<?php

declare(strict_types=1);

namespace Tests\Feature\CompanyManagement\Areas;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Listing Areas
 *
 * Tests the endpoint GET /api/areas
 *
 * Coverage:
 * - Authentication (any authenticated user can list)
 * - Pagination (default 15, max 100)
 * - Filtering by company_id (required)
 * - Filtering by is_active (optional)
 * - active_tickets_count included
 * - Response format with meta and links
 *
 * Expected Status Codes:
 * - 200: Areas listed successfully
 * - 401: Unauthenticated
 * - 422: Validation errors (missing company_id)
 *
 * Database Schema: business.areas
 * Query params: company_id (required), is_active (optional), per_page, page
 */
class ListAreasTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication (Tests 1-4) ====================

    /**
     * Test #1: Unauthenticated user cannot list areas
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_list_areas(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Act - No authenticateWithJWT() call
        $response = $this->getJson("/api/areas?company_id={$company->id}");

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test #2: Any authenticated user can list areas
     *
     * Verifies that USER, AGENT, COMPANY_ADMIN, and PLATFORM_ADMIN can all list areas.
     *
     * Expected: 200 OK for all roles
     */
    #[Test]
    public function any_authenticated_user_can_list_areas(): void
    {
        // Arrange
        $company = Company::factory()->create();
        Area::factory()->create(['company_id' => $company->id, 'name' => 'Test Area']);

        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);
        $companyAdmin = $this->createCompanyAdmin();
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        // Act & Assert - USER
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}");
        $response->assertStatus(200);

        // Act & Assert - AGENT
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/areas?company_id={$company->id}");
        $response->assertStatus(200);

        // Act & Assert - COMPANY_ADMIN
        $response = $this->authenticateWithJWT($companyAdmin)
            ->getJson("/api/areas?company_id={$company->id}");
        $response->assertStatus(200);

        // Act & Assert - PLATFORM_ADMIN
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson("/api/areas?company_id={$company->id}");
        $response->assertStatus(200);
    }

    // ==================== GROUP 2: Required Parameters (Test 3) ====================

    /**
     * Test #3: company_id query param is required
     *
     * Verifies that listing areas without company_id fails validation.
     *
     * Expected: 422 Unprocessable Entity
     */
    #[Test]
    public function company_id_query_param_is_required(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Act - Missing company_id
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/areas');

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['company_id']);
    }

    // ==================== GROUP 3: Pagination (Tests 4-6) ====================

    /**
     * Test #4: Default pagination is 15 items per page
     *
     * Verifies that without per_page param, default is 15.
     *
     * Expected: 200 with meta.per_page = 15
     */
    #[Test]
    public function default_pagination_is_15_items_per_page(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Create 20 areas
        Area::factory()->count(20)->create(['company_id' => $company->id]);

        // Act - No per_page param
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 15);
        $response->assertJsonPath('meta.total', 20);
        $response->assertJsonCount(15, 'data');
    }

    /**
     * Test #5: per_page param works (1-100 range)
     *
     * Verifies that per_page can be customized between 1 and 100.
     *
     * Expected: 200 with correct per_page
     */
    #[Test]
    public function per_page_param_works_within_range(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        Area::factory()->count(10)->create(['company_id' => $company->id]);

        // Act - per_page = 5
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}&per_page=5");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 5);
        $response->assertJsonCount(5, 'data');

        // Act - per_page = 100
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}&per_page=100");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 100);
    }

    /**
     * Test #6: per_page validation (max 100)
     *
     * Verifies that per_page > 100 fails validation.
     *
     * Expected: 422 for per_page = 101
     */
    #[Test]
    public function per_page_validates_maximum_100(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Act - per_page = 101 (exceeds max)
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}&per_page=101");

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['per_page']);
    }

    // ==================== GROUP 4: Filtering (Tests 7-9) ====================

    /**
     * Test #7: Filter by is_active = true
     *
     * Verifies that is_active filter works correctly.
     *
     * Expected: 200 with only active areas
     */
    #[Test]
    public function filter_by_is_active_true(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        Area::factory()->count(3)->create(['company_id' => $company->id, 'is_active' => true]);
        Area::factory()->count(2)->create(['company_id' => $company->id, 'is_active' => false]);

        // Act - Filter active only
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}&is_active=true");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test #8: Filter by is_active = false
     *
     * Verifies that is_active=false returns only inactive areas.
     *
     * Expected: 200 with only inactive areas
     */
    #[Test]
    public function filter_by_is_active_false(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        Area::factory()->count(3)->create(['company_id' => $company->id, 'is_active' => true]);
        Area::factory()->count(2)->create(['company_id' => $company->id, 'is_active' => false]);

        // Act - Filter inactive only
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}&is_active=false");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonCount(2, 'data');
    }

    /**
     * Test #9: Without is_active filter returns all areas
     *
     * Verifies that omitting is_active returns both active and inactive.
     *
     * Expected: 200 with all areas
     */
    #[Test]
    public function without_is_active_filter_returns_all_areas(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        Area::factory()->count(3)->create(['company_id' => $company->id, 'is_active' => true]);
        Area::factory()->count(2)->create(['company_id' => $company->id, 'is_active' => false]);

        // Act - No filter
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 5);
        $response->assertJsonCount(5, 'data');
    }

    // ==================== GROUP 5: active_tickets_count (Test 10) ====================

    /**
     * Test #10: Response includes active_tickets_count
     *
     * Verifies that each area includes a count of active tickets (OPEN, PENDING, RESOLVED).
     *
     * Expected: 200 with active_tickets_count field
     */
    #[Test]
    public function response_includes_active_tickets_count(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $area1 = Area::factory()->create(['company_id' => $company->id, 'name' => 'Area 1']);
        $area2 = Area::factory()->create(['company_id' => $company->id, 'name' => 'Area 2']);

        // Area 1: 2 active tickets (OPEN, PENDING)
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'area_id' => $area1->id,
            'status' => 'open',
            'created_by_user_id' => $user->id,
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'area_id' => $area1->id,
            'status' => 'pending',
            'created_by_user_id' => $user->id,
        ]);

        // Area 1: 1 closed ticket (should NOT count)
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'area_id' => $area1->id,
            'status' => 'closed',
            'created_by_user_id' => $user->id,
        ]);

        // Area 2: 0 tickets

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);

        $data = $response->json('data');

        // Find area1 and area2 in response
        $area1Data = collect($data)->firstWhere('id', $area1->id);
        $area2Data = collect($data)->firstWhere('id', $area2->id);

        $this->assertEquals(2, $area1Data['active_tickets_count']);
        $this->assertEquals(0, $area2Data['active_tickets_count']);
    }

    // ==================== GROUP 6: Response Format (Test 11) ====================

    /**
     * Test #11: Response format includes meta and links
     *
     * Verifies correct pagination response structure.
     *
     * Expected: 200 with success, data, meta, links
     */
    #[Test]
    public function response_format_includes_meta_and_links(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        Area::factory()->count(5)->create(['company_id' => $company->id]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'company_id',
                    'name',
                    'description',
                    'is_active',
                    'created_at',
                    'active_tickets_count',
                ],
            ],
            'meta' => [
                'current_page',
                'from',
                'to',
                'last_page',
                'per_page',
                'total',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);

        $response->assertJsonPath('success', true);
    }

    // ==================== GROUP 7: Company Isolation (Test 12) ====================

    /**
     * Test #12: Areas are isolated by company_id
     *
     * Verifies that listing areas for Company A does not return areas from Company B.
     *
     * Expected: 200 with only Company A areas
     */
    #[Test]
    public function areas_are_isolated_by_company_id(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        Area::factory()->count(3)->create(['company_id' => $companyA->id]);
        Area::factory()->count(2)->create(['company_id' => $companyB->id]);

        // Act - List areas for Company A
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/areas?company_id={$companyA->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonCount(3, 'data');
    }
}
