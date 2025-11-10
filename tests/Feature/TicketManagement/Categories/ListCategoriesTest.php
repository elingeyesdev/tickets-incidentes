<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Categories;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Listing Ticket Categories
 *
 * Tests the endpoint GET /api/tickets/categories?company_id={uuid}&is_active={bool}
 *
 * Coverage:
 * - Authentication (unauthenticated, USER, AGENT, COMPANY_ADMIN)
 * - Listing categories for a company
 * - Filtering by is_active status
 * - Listing shows all companies' categories
 * - AGENT can list their own company's categories
 * - Include active tickets count per category
 * - Unauthenticated access prevention
 *
 * Expected Status Codes:
 * - 200: Categories listed successfully
 * - 401: Unauthenticated
 * - 200: User can access any company's categories
 *
 * Query Parameters:
 * - company_id: UUID (required) - Filter categories by company
 * - is_active: boolean (optional) - Filter by active/inactive status
 *
 * Response Structure:
 * - data: Array of categories
 *   - id: UUID
 *   - company_id: UUID
 *   - name: string
 *   - description: string|null
 *   - is_active: boolean
 *   - active_tickets_count: integer (tickets with status != CLOSED)
 *   - created_at: ISO8601
 *
 * Business Rules:
 * - USER: Can list categories from any company
 * - AGENT: Can only list categories from their own company
 * - COMPANY_ADMIN: Can list categories from their own company
 * - active_tickets_count excludes CLOSED tickets
 */
class ListCategoriesTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication & Basic Listing (Tests 1, 6) ====================

    /**
     * Test #6: Unauthenticated user cannot list categories
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_list(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Act - No authenticateWithJWT() call
        $response = $this->getJson("/api/tickets/categories?company_id={$companyId}");

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test #1: User can list categories of any company
     *
     * Verifies that a USER can list categories from any company, regardless of following status.
     * Following is for information/UI priority, NOT access control.
     *
     * Expected: 200 OK with array of categories
     */
    #[Test]
    public function user_can_list_categories_of_company(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Create categories for this company
        $category1Response = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', ['name' => 'Soporte Técnico']);
        $category2Response = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', ['name' => 'Ventas']);

        // Create a USER (who does NOT need to follow this company)
        $user = User::factory()->withRole('USER')->create();
        // No following relationship needed - users can list categories from any company

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/categories?company_id={$companyId}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['name' => 'Soporte Técnico']);
        $response->assertJsonFragment(['name' => 'Ventas']);
    }

    // ==================== GROUP 2: Filtering (Test 2) ====================

    /**
     * Test #2: Filters by is_active status
     *
     * Verifies that the is_active query parameter correctly filters categories.
     * - ?is_active=true should return only active categories
     * - ?is_active=false should return only inactive categories
     * - No parameter should return all categories
     *
     * Expected: 200 OK with filtered results
     */
    #[Test]
    public function filters_by_is_active_status(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Create active category
        $activeResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', ['name' => 'Categoría Activa']);
        $activeCategoryId = $activeResponse->json('data.id');

        // Create inactive category
        $inactiveResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', ['name' => 'Categoría Inactiva']);
        $inactiveCategoryId = $inactiveResponse->json('data.id');

        // Deactivate second category
        $this->authenticateWithJWT($admin)
            ->putJson("/api/tickets/categories/{$inactiveCategoryId}", ['is_active' => false]);

        // Act & Assert - Filter by is_active=true
        $response = $this->authenticateWithJWT($admin)
            ->getJson("/api/tickets/categories?company_id={$companyId}&is_active=true");
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'Categoría Activa']);
        $response->assertJsonMissing(['name' => 'Categoría Inactiva']);

        // Act & Assert - Filter by is_active=false
        $response = $this->authenticateWithJWT($admin)
            ->getJson("/api/tickets/categories?company_id={$companyId}&is_active=false");
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'Categoría Inactiva']);
        $response->assertJsonMissing(['name' => 'Categoría Activa']);

        // Act & Assert - No filter (should return all)
        $response = $this->authenticateWithJWT($admin)
            ->getJson("/api/tickets/categories?company_id={$companyId}");
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    // ==================== GROUP 3: Company Isolation & Following (Test 3) ====================

    /**
     * Test #3: User can list categories of any company
     *
     * Verifies that a USER can list categories from ANY company, regardless of following status.
     * Following is for information/UI priority, NOT access control.
     *
     * Expected: 200 OK with categories
     */
    #[Test]
    public function user_can_list_categories_of_any_company(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Create category for this company
        $categoryResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', ['name' => 'Categoría Accesible']);
        $categoryId = $categoryResponse->json('data.id');

        // Create a USER who does NOT follow this company
        $user = User::factory()->withRole('USER')->create();

        // Act - User lists categories from ANY company (no following required)
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/categories?company_id={$companyId}");

        // Assert - Should succeed (200, not 403)
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $categoryId,
            'name' => 'Categoría Accesible',
        ]);
    }

    // ==================== GROUP 4: Agent Access (Test 4) ====================

    /**
     * Test #4: Agent can list own company categories
     *
     * Verifies that a user with AGENT role can list categories from their own company.
     * AGENT should have access to their company's categories without needing to "follow".
     *
     * Expected: 200 OK with categories from agent's company
     */
    #[Test]
    public function agent_can_list_own_company_categories(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Create categories for this company
        $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', ['name' => 'Soporte']);
        $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', ['name' => 'Facturación']);

        // Create an AGENT for the same company
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $companyId);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets/categories?company_id={$companyId}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['name' => 'Soporte']);
        $response->assertJsonFragment(['name' => 'Facturación']);
    }

    // ==================== GROUP 5: Tickets Count (Test 5) ====================

    /**
     * Test #5: Includes active tickets count per category
     *
     * Verifies that the response includes active_tickets_count for each category.
     * Active tickets are those with status != CLOSED.
     *
     * Expected: 200 OK with active_tickets_count field
     * Count: Should only include OPEN, PENDING, IN_PROGRESS, RESOLVED tickets
     * Count: Should exclude CLOSED tickets
     */
    #[Test]
    public function includes_active_tickets_count_per_category(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Create category
        $categoryResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', ['name' => 'Soporte con Tickets']);
        $categoryId = $categoryResponse->json('data.id');

        // Create tickets with different statuses
        $openTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'open',
        ]);
        $pendingTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'pending',
        ]);
        $inProgressTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'IN_PROGRESS',
        ]);
        $closedTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'closed',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson("/api/tickets/categories?company_id={$companyId}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');

        // Find the category in response
        $categories = $response->json('data');
        $category = collect($categories)->firstWhere('id', $categoryId);

        $this->assertNotNull($category, 'Category should be in response');
        $this->assertArrayHasKey('active_tickets_count', $category);

        // Should count 3 active tickets (OPEN, PENDING, IN_PROGRESS)
        // Should NOT count CLOSED ticket
        $this->assertEquals(3, $category['active_tickets_count']);
    }
}
