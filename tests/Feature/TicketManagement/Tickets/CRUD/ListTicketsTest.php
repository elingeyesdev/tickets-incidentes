<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Tickets\CRUD;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Listing Tickets
 *
 * Tests the endpoint GET /api/tickets
 *
 * Coverage:
 * - Authentication (unauthenticated)
 * - Permissions by role (USER sees own tickets, AGENT sees company tickets, COMPANY_ADMIN restrictions)
 * - Filtering by status (open, pending, resolved, closed)
 * - Filtering by category_id
 * - Filtering by owner_agent_id (including "me" resolver)
 * - Filtering by created_by_user_id
 * - Search in title and description
 * - Date range filtering (created_after, created_before)
 * - Sorting (created_at desc default, updated_at asc)
 * - Pagination
 * - Related data inclusion (creator, agent, category, counts)
 * - USER can view own tickets regardless of following companies
 *
 * Expected Status Codes:
 * - 200: Tickets retrieved successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (if applicable)
 * - 422: Validation errors (invalid filters)
 *
 * Database Schema: ticketing.tickets
 * - id: UUID
 * - ticket_code: VARCHAR(50)
 * - company_id: UUID
 * - category_id: UUID
 * - created_by_user_id: UUID
 * - owner_agent_id: UUID (nullable)
 * - title: VARCHAR(255)
 * - description: TEXT
 * - status: ENUM (open, pending, resolved, closed)
 * - created_at: TIMESTAMPTZ
 * - updated_at: TIMESTAMPTZ
 */
class ListTicketsTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Autenticación (Test 1) ====================

    /**
     * Test #1: Unauthenticated user cannot list tickets
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_list_tickets(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Act - No authenticateWithJWT() call
        $response = $this->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(401);
    }

    // ==================== GROUP 2: Permisos (Tests 2-5) ====================

    /**
     * Test #2: User can list own tickets
     *
     * Verifies that USER role can list their own created tickets.
     *
     * Expected: 200 OK with user's tickets
     */
    #[Test]
    public function user_can_list_own_tickets(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create 3 tickets for this user
        Ticket::factory()->count(3)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.created_by_user_id', $user->id);
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #3: User cannot see other users tickets
     *
     * Verifies that USER role cannot see tickets created by other users.
     *
     * Expected: User B does not see User A's tickets
     */
    #[Test]
    public function user_cannot_see_other_users_tickets(): void
    {
        // Arrange
        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // User A creates a ticket
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'title' => 'Ticket de Usuario A',
            'status' => 'open',
        ]);

        // Act - User B tries to list tickets
        $response = $this->authenticateWithJWT($userB)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data'); // Should not see User A's ticket

        // Verify User A's ticket is NOT in the response
        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertNotContains('Ticket de Usuario A', $titles);

        // If there were tickets, we would check last_response_author_type
        // But since count is 0, we skip that assertion here
    }

    /**
     * Test #4: Agent can list all company tickets
     *
     * Verifies that AGENT role can list all tickets in their company,
     * regardless of who created them.
     *
     * Expected: Agent sees all 3 tickets in their company
     */
    #[Test]
    public function agent_can_list_all_company_tickets(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign agent to this company (via role)
        $agent->assignRole('AGENT', $company->id);

        // Create tickets by different users in the same company
        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();
        $userC = User::factory()->withRole('USER')->create();

        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userB->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userC->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data'); // Agent should see all 3 tickets
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #5: Agent cannot see other company tickets
     *
     * Verifies that AGENT role cannot see tickets from companies
     * they are not assigned to.
     *
     * Expected: Agent does not see tickets from other company
     */
    #[Test]
    public function agent_cannot_see_other_company_tickets(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        // Assign agent only to Company A
        $agent->assignRole('AGENT', $companyA->id);

        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // Create ticket in Company B
        $userB = User::factory()->withRole('USER')->create();
        Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $userB->id,
            'title' => 'Ticket en Empresa B',
            'status' => 'open',
        ]);

        // Act - Agent from Company A tries to list Company B tickets
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets?company_id={$companyB->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data'); // Should not see Company B's ticket

        // If there were tickets, we would check last_response_author_type
        // But since count is 0, we skip that assertion here
    }

    // ==================== GROUP 3: Filtros por Status (Tests 6-9) ====================

    /**
     * Test #6: Filter by status 'open' works
     *
     * Verifies filtering tickets by status = 'open'.
     *
     * Expected: Only 'open' tickets are returned
     */
    #[Test]
    public function filter_by_status_open_works(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets with different statuses
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'resolved',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&status=open");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'open');
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #7: Filter by status 'pending' works
     *
     * Verifies filtering tickets by status = 'pending'.
     *
     * Expected: Only 'pending' tickets are returned
     */
    #[Test]
    public function filter_by_status_pending_works(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets with different statuses
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&status=pending");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'pending');
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #8: Filter by status 'resolved' works
     *
     * Verifies filtering tickets by status = 'resolved'.
     *
     * Expected: Only 'resolved' tickets are returned
     */
    #[Test]
    public function filter_by_status_resolved_works(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets with different statuses
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'resolved',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&status=resolved");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'resolved');
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #9: Filter by status 'closed' works
     *
     * Verifies filtering tickets by status = 'closed'.
     *
     * Expected: Only 'closed' tickets are returned
     */
    #[Test]
    public function filter_by_status_closed_works(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets with different statuses
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'resolved',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'closed',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&status=closed");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'closed');
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #10: Filter by category works
     *
     * Verifies filtering tickets by category_id.
     *
     * Expected: Only tickets from specified category are returned
     */
    #[Test]
    public function filter_by_category_works(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $categoryA = Category::factory()->create([
            'company_id' => $company->id,
            'name' => 'Soporte Técnico',
            'is_active' => true,
        ]);
        $categoryB = Category::factory()->create([
            'company_id' => $company->id,
            'name' => 'Ventas',
            'is_active' => true,
        ]);

        // Create tickets in different categories
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryA->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryA->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act - Filter by Category A
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&category_id={$categoryA->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data'); // 2 tickets in Category A
        $response->assertJsonPath('data.0.category_id', $categoryA->id);
        $response->assertJsonPath('data.1.category_id', $categoryA->id);
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    // ==================== GROUP 4: Filtros por Agent (Tests 11-12) ====================

    /**
     * Test #11: Filter by owner_agent_id works
     *
     * Verifies filtering tickets by owner_agent_id.
     *
     * Expected: Only tickets assigned to specified agent are returned
     */
    #[Test]
    public function filter_by_owner_agent_id_works(): void
    {
        // Arrange
        $agentA = User::factory()->withRole('AGENT')->create();
        $agentB = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign agents to company
        $agentA->assignRole('AGENT', $company->id);
        $agentB->assignRole('AGENT', $company->id);

        // Create tickets assigned to different agents
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => User::factory()->withRole('USER')->create()->id,
            'owner_agent_id' => $agentA->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => User::factory()->withRole('USER')->create()->id,
            'owner_agent_id' => $agentB->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => User::factory()->withRole('USER')->create()->id,
            'owner_agent_id' => $agentA->id,
            'status' => 'open',
        ]);

        // Act - Filter by Agent A
        $response = $this->authenticateWithJWT($agentA)
            ->getJson("/api/tickets?company_id={$company->id}&owner_agent_id={$agentA->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data'); // 2 tickets assigned to Agent A
        $response->assertJsonPath('data.0.owner_agent_id', $agentA->id);
        $response->assertJsonPath('data.1.owner_agent_id', $agentA->id);
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #12: Filter owner_agent_id='me' resolves to authenticated user
     *
     * Verifies that passing owner_agent_id=me resolves to the authenticated user's ID.
     *
     * Expected: Returns tickets assigned to the authenticated agent
     */
    #[Test]
    public function filter_owner_agent_id_me_resolves_to_authenticated_user(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign agent to company
        $agent->assignRole('AGENT', $company->id);

        // Create tickets assigned to this agent and others
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => User::factory()->withRole('USER')->create()->id,
            'owner_agent_id' => $agent->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => User::factory()->withRole('USER')->create()->id,
            'owner_agent_id' => User::factory()->withRole('AGENT')->create()->id, // Different agent
            'status' => 'open',
        ]);

        // Act - Use "me" instead of agent ID
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets?company_id={$company->id}&owner_agent_id=me");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data'); // Only tickets assigned to this agent
        $response->assertJsonPath('data.0.owner_agent_id', $agent->id);
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #13: Filter by created_by_user_id works
     *
     * Verifies filtering tickets by created_by_user_id.
     *
     * Expected: Only tickets created by specified user are returned
     */
    #[Test]
    public function filter_by_created_by_user_id(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $agent->assignRole('AGENT', $company->id);

        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();

        // Create tickets by different users
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userB->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'status' => 'open',
        ]);

        // Act - Filter by User A (as agent)
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets?company_id={$company->id}&created_by_user_id={$userA->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data'); // 2 tickets created by User A
        $response->assertJsonPath('data.0.created_by_user_id', $userA->id);
        $response->assertJsonPath('data.1.created_by_user_id', $userA->id);
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    // ==================== GROUP 5: Búsqueda (Tests 14-15) ====================

    /**
     * Test #14: Search in title works
     *
     * Verifies searching tickets by text in title.
     *
     * Expected: Returns tickets with matching title
     */
    #[Test]
    public function search_in_title_works(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets with different titles
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Error al exportar reporte mensual',
            'description' => 'Descripción genérica del problema',
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Problema con login de usuario',
            'description' => 'Descripción genérica del problema',
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'No se puede exportar datos de clientes',
            'description' => 'Descripción genérica del problema',
            'status' => 'open',
        ]);

        // Act - Search for "exportar"
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&search=exportar");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data'); // 2 tickets with "exportar" in title
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #15: Search in description works
     *
     * Verifies searching tickets by text in description.
     *
     * Expected: Returns tickets with matching description
     */
    #[Test]
    public function search_in_description_works(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets with different descriptions
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Problema 1',
            'description' => 'El sistema muestra error 500 cuando intento guardar',
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Problema 2',
            'description' => 'No puedo acceder a la página de configuración',
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Problema 3',
            'description' => 'Recibo error 404 al intentar ver mis reportes',
            'status' => 'open',
        ]);

        // Act - Search for "error"
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&search=error");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data'); // 2 tickets with "error" in description
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    // ==================== GROUP 6: Filtros de Fecha (Test 16) ====================

    /**
     * Test #16: Filter by date range works
     *
     * Verifies filtering tickets by created_after and created_before.
     *
     * Expected: Only tickets within date range are returned
     */
    #[Test]
    public function filter_by_date_range(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets with different dates
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'created_at' => '2025-10-15 10:00:00',
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'created_at' => '2025-11-05 10:00:00',
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'created_at' => '2025-11-15 10:00:00',
            'status' => 'open',
        ]);

        // Act - Filter for November 1-10
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&created_after=2025-11-01&created_before=2025-11-10");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data'); // Only the November 5 ticket
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    // ==================== GROUP 7: Ordenamiento (Tests 17-18) ====================

    /**
     * Test #17: Sort by created_at desc (default)
     *
     * Verifies default sorting is by created_at descending (newest first).
     *
     * Expected: Tickets ordered by created_at DESC
     */
    #[Test]
    public function sort_by_created_at_desc_default(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets at different times
        $ticket1 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Primer ticket',
            'created_at' => '2025-11-01 10:00:00',
            'status' => 'open',
        ]);
        $ticket2 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Segundo ticket',
            'created_at' => '2025-11-05 10:00:00',
            'status' => 'open',
        ]);
        $ticket3 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Tercer ticket',
            'created_at' => '2025-11-10 10:00:00',
            'status' => 'open',
        ]);

        // Act - No sort parameter (should default to created_at desc)
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        // Verify order: newest first (ticket3, ticket2, ticket1)
        $response->assertJsonPath('data.0.title', 'Tercer ticket');
        $response->assertJsonPath('data.1.title', 'Segundo ticket');
        $response->assertJsonPath('data.2.title', 'Primer ticket');
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #18: Sort by updated_at asc
     *
     * Verifies sorting by updated_at ascending.
     *
     * Expected: Tickets ordered by updated_at ASC
     */
    #[Test]
    public function sort_by_updated_at_asc(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets with different update times
        $ticket1 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket actualizado hace 1 día',
            'updated_at' => '2025-11-09 10:00:00',
            'status' => 'open',
        ]);
        $ticket2 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket actualizado hace 3 días',
            'updated_at' => '2025-11-07 10:00:00',
            'status' => 'open',
        ]);
        $ticket3 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket actualizado hoy',
            'updated_at' => '2025-11-10 10:00:00',
            'status' => 'open',
        ]);

        // Act - Sort by updated_at ascending
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&sort=updated_at");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        // Verify order: oldest update first (ticket2, ticket1, ticket3)
        $response->assertJsonPath('data.0.title', 'Ticket actualizado hace 3 días');
        $response->assertJsonPath('data.1.title', 'Ticket actualizado hace 1 día');
        $response->assertJsonPath('data.2.title', 'Ticket actualizado hoy');
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    // ==================== GROUP 8: Paginación (Test 19) ====================

    /**
     * Test #19: Pagination works
     *
     * Verifies that pagination works correctly with page and per_page parameters.
     *
     * Expected: Correct page of results returned
     */
    #[Test]
    public function pagination_works(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create 25 tickets
        Ticket::factory()->count(25)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act - Request page 2 with 20 per page
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&page=2&per_page=20");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data'); // 5 tickets on page 2 (25 total - 20 on page 1 = 5 on page 2)
        $response->assertJsonPath('meta.current_page', 2);
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    // ==================== GROUP 9: Datos Relacionados (Test 20) ====================

    /**
     * Test #20: Includes related data in list
     *
     * Verifies that the list response includes related data:
     * - Creator info (created_by_user)
     * - Agent info (owner_agent)
     * - Category info
     * - Response counts
     *
     * Expected: Response includes related data
     */
    #[Test]
    public function includes_related_data_in_list(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $agent->assignRole('AGENT', $company->id);

        // Create ticket with agent assigned
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'owner_agent_id' => $agent->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'ticket_code',
                    'title',
                    'status',
                    'last_response_author_type',
                    'created_by_user' => ['id', 'name', 'email'],
                    'owner_agent' => ['id', 'name', 'email'],
                    'category' => ['id', 'name'],
                    'responses_count',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
    }

    // ==================== GROUP 10: Permisos Especiales (Test 21) ====================

    /**
     * Test #21: User can view own tickets regardless of following
     *
     * Verifies that USER role can view their own tickets in any company,
     * even if they don't "follow" that company.
     *
     * Expected: User sees their tickets in company they don't follow
     */
    #[Test]
    public function user_can_view_own_tickets_regardless_of_following(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets for user in this company (without following relationship)
        Ticket::factory()->count(2)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act - User requests their tickets (no following restriction)
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data'); // Should see both tickets
    }

    // ==================== GROUP 11: Filtros Avanzados (Tests 22-26) ====================

    /**
     * Test #22: Filter by last_response_author_type 'none'
     *
     * Verifies filtering tickets where no responses have been made yet.
     *
     * Expected: Only tickets with last_response_author_type='none' are returned
     */
    #[Test]
    public function filter_by_last_response_author_type_none(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $company = Company::factory()->create();

        // Create tickets con last_response_author_type = 'none'
        Ticket::factory(2)->for($company)->for($user, 'creator')->create(['last_response_author_type' => 'none']);

        // Create tickets con otros valores
        Ticket::factory()->for($company)->for($user, 'creator')->create(['last_response_author_type' => 'user']);
        Ticket::factory()->for($company)->for($user, 'creator')->create(['last_response_author_type' => 'agent']);

        $response = $this->actingAs($user)->getJson('/api/tickets?company_id='.$company->id.'&last_response_author_type=none');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }

    /**
     * Test #23: Filter by last_response_author_type 'user'
     *
     * Verifies filtering tickets where the last response was made by a user.
     *
     * Expected: Only tickets with last_response_author_type='user' are returned
     */
    #[Test]
    public function filter_by_last_response_author_type_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $company = Company::factory()->create();

        Ticket::factory()->for($company)->for($user, 'creator')->create(['last_response_author_type' => 'none']);
        Ticket::factory(2)->for($company)->for($user, 'creator')->create(['last_response_author_type' => 'user']);
        Ticket::factory()->for($company)->for($user, 'creator')->create(['last_response_author_type' => 'agent']);

        $response = $this->actingAs($user)->getJson('/api/tickets?company_id='.$company->id.'&last_response_author_type=user');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('data.0.last_response_author_type', 'user');
    }

    /**
     * Test #24: Filter by last_response_author_type 'agent'
     *
     * Verifies filtering tickets where the last response was made by an agent.
     *
     * Expected: Only tickets with last_response_author_type='agent' are returned
     */
    #[Test]
    public function filter_by_last_response_author_type_agent(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $company = Company::factory()->create();

        Ticket::factory()->for($company)->for($user, 'creator')->create(['last_response_author_type' => 'none']);
        Ticket::factory()->for($company)->for($user, 'creator')->create(['last_response_author_type' => 'user']);
        Ticket::factory(2)->for($company)->for($user, 'creator')->create(['last_response_author_type' => 'agent']);

        $response = $this->actingAs($user)->getJson('/api/tickets?company_id='.$company->id.'&last_response_author_type=agent');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('data.0.last_response_author_type', 'agent');
    }

    /**
     * Test #25: Filter by owner_agent_id 'null' literal
     *
     * Verifies filtering tickets that have no assigned owner agent using the literal string 'null'.
     *
     * Expected: Only tickets with owner_agent_id=null are returned
     */
    #[Test]
    public function filter_by_owner_agent_id_null_literal(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $agent = User::factory()->create(['role' => 'agent']);
        $company = Company::factory()->create();

        // Create tickets sin owner
        Ticket::factory(2)->for($company)->for($user, 'creator')->create(['owner_agent_id' => null]);

        // Create tickets con owner
        Ticket::factory()->for($company)->for($user, 'creator')->create(['owner_agent_id' => $agent->id]);

        $response = $this->actingAs($user)->getJson('/api/tickets?company_id='.$company->id.'&owner_agent_id=null');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('data.0.owner_agent_id', null);
    }

    /**
     * Test #26: Combine filters owner_null and last_response_author_type_none
     *
     * Verifies combining multiple filters: owner_agent_id=null AND last_response_author_type=none.
     *
     * Expected: Only tickets matching BOTH conditions are returned
     */
    #[Test]
    public function combine_filters_owner_null_and_last_response_author_type_none(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $agent = User::factory()->create(['role' => 'agent']);
        $company = Company::factory()->create();

        // Tickets que coinciden con AMBOS filtros
        Ticket::factory(2)->for($company)->for($user, 'creator')->create([
            'owner_agent_id' => null,
            'last_response_author_type' => 'none'
        ]);

        // Tickets con owner pero last_response_author_type=none
        Ticket::factory()->for($company)->for($user, 'creator')->create([
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'none'
        ]);

        // Tickets sin owner pero last_response_author_type=user
        Ticket::factory()->for($company)->for($user, 'creator')->create([
            'owner_agent_id' => null,
            'last_response_author_type' => 'user'
        ]);

        $response = $this->actingAs($user)->getJson(
            '/api/tickets?company_id='.$company->id.'&owner_agent_id=null&last_response_author_type=none'
        );

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('data.0.owner_agent_id', null);
        $response->assertJsonPath('data.0.last_response_author_type', 'none');
    }
}
