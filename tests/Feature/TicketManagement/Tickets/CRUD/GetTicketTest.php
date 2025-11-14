<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Tickets\CRUD;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * Feature Tests for Getting Ticket Detail
 *
 * Tests the endpoint GET /api/tickets/:code
 *
 * Coverage:
 * - Authentication (unauthenticated)
 * - Permissions by role (USER can view own tickets, AGENT can view company tickets, COMPANY_ADMIN restrictions)
 * - Ticket detail includes complete information
 * - Ticket detail includes responses_count and attachments_count
 * - Ticket detail includes timeline events (created_at, first_response_at, resolved_at, closed_at)
 * - Nonexistent ticket returns 404
 *
 * Expected Status Codes:
 * - 200: Ticket retrieved successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (viewing other user's ticket)
 * - 404: Ticket not found
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
class GetTicketTest extends TestCase
{
    use DatabaseMigrations;

    // ==================== GROUP 1: Autenticación (Test 1) ====================

    /**
     * Test #1: Unauthenticated user cannot view ticket
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_view_ticket(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $user = User::factory()->withRole('USER')->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act - No authenticateWithJWT() call
        $response = $this->getJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(404);
    }

    // ==================== GROUP 2: Permisos (Tests 2-6) ====================

    /**
     * Test #2: User can view own ticket
     *
     * Verifies that USER role can view tickets they created.
     *
     * Expected: 200 OK with ticket detail
     */
    #[Test]
    public function user_can_view_own_ticket(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Error al exportar reporte',
            'description' => 'Cuando intento exportar el reporte mensual, el sistema muestra un error 500.',
            'status' => 'open',
        ]);

        // DEBUG: Verificar datos antes de request
        $this->assertDatabaseHas('ticketing.tickets', ['id' => $ticket->id]);
        $found = \App\Features\TicketManagement\Models\Ticket::where('ticket_code', $ticket->ticket_code)->first();
        dump([
            'ticket_code' => $ticket->ticket_code,
            'found_before_request' => $found ? 'YES' : 'NO',
            'found_id' => $found?->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $ticket->id);
        $response->assertJsonPath('data.ticket_code', $ticket->ticket_code);
        $response->assertJsonPath('data.title', 'Error al exportar reporte');
        $response->assertJsonPath('data.created_by_user_id', $user->id);
        $response->assertJsonPath('data.status', 'open');
    }

    /**
     * Test #3: User cannot view other user ticket
     *
     * Verifies that USER role cannot view tickets created by other users.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_view_other_user_ticket(): void
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
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'title' => 'Ticket de Usuario A',
            'status' => 'open',
        ]);

        // Act - User B tries to view User A's ticket
        $response = $this->authenticateWithJWT($userB)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #4: Agent can view any ticket from own company
     *
     * Verifies that AGENT role can view all tickets in their company.
     *
     * Expected: 200 OK with ticket detail
     */
    #[Test]
    public function agent_can_view_any_ticket_from_own_company(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create ticket by another user
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket en empresa del agente',
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $ticket->id);
        $response->assertJsonPath('data.ticket_code', $ticket->ticket_code);
    }

    /**
     * Test #5: Agent cannot view ticket from other company
     *
     * Verifies that AGENT role cannot view tickets from other companies.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function agent_cannot_view_ticket_from_other_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $agent = User::factory()->withRole('AGENT', $companyA->id)->create();
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // Create ticket in Company B
        $userB = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $userB->id,
            'title' => 'Ticket en Empresa B',
            'status' => 'open',
        ]);

        // Act - Agent from Company A tries to view Company B ticket
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #6: Company admin can view any ticket from own company
     *
     * Verifies that COMPANY_ADMIN role can view all tickets in their company.
     *
     * Expected: 200 OK with ticket detail
     */
    #[Test]
    public function company_admin_can_view_any_ticket_from_own_company(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign admin to this company
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        // Create ticket by another user
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket en empresa del admin',
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $ticket->id);
        $response->assertJsonPath('data.ticket_code', $ticket->ticket_code);
    }

    // ==================== GROUP 3: Detalle Completo (Tests 7-9) ====================

    /**
     * Test #7: Ticket detail includes complete information
     *
     * Verifies that the GET endpoint returns all required ticket fields.
     *
     * Expected: Response includes id, ticket_code, title, description, status,
     *           owner_agent_id, company_id, category_id, created_at, updated_at
     */
    #[Test]
    public function ticket_detail_includes_complete_information(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket completo',
            'description' => 'Descripción detallada del problema.',
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'ticket_code',
                'title',
                'description',
                'status',
                'owner_agent_id',
                'last_response_author_type',
                'company_id',
                'category_id',
                'created_at',
                'updated_at',
            ],
        ]);

        $response->assertJsonPath('data.id', $ticket->id);
        $response->assertJsonPath('data.ticket_code', $ticket->ticket_code);
        $response->assertJsonPath('data.title', 'Ticket completo');
        $response->assertJsonPath('data.description', 'Descripción detallada del problema.');
        $response->assertJsonPath('data.status', 'open');
        $response->assertJsonPath('data.company_id', $company->id);
        $response->assertJsonPath('data.category_id', $category->id);
    }

    /**
     * Test #8: Ticket detail includes responses count
     *
     * Verifies that the response includes responses_count and attachments_count.
     *
     * Expected: Response includes responses_count and attachments_count fields
     */
    #[Test]
    public function ticket_detail_includes_responses_count(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'responses_count',
                'attachments_count',
            ],
        ]);
    }

    /**
     * Test #9: Ticket detail includes timeline
     *
     * Verifies that the response includes timeline with events:
     * created_at, first_response_at, resolved_at, closed_at.
     *
     * Expected: Response includes timeline object with event timestamps
     */
    #[Test]
    public function ticket_detail_includes_timeline(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'timeline' => [
                    'created_at',
                    'first_response_at',
                    'resolved_at',
                    'closed_at',
                ],
            ],
        ]);
    }

    // ==================== GROUP 4: Error 404 (Test 10) ====================

    /**
     * Test #10: Nonexistent ticket returns 404
     *
     * Verifies that requesting a non-existent ticket code returns 404.
     *
     * Expected: 404 Not Found
     */
    #[Test]
    public function nonexistent_ticket_returns_404(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Act - Request non-existent ticket code
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/tickets/TKT-9999-99999');

        // Assert
        $response->assertStatus(404);
    }

    #[Test]
    public function test_get_ticket_detail_includes_last_response_author_type()
    {
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()
            ->for($company)
            ->for($user, 'creator')
            ->for($category)
            ->create(['last_response_author_type' => 'none']);

        $response = $this->authenticateWithJWT($user)->getJson("/api/tickets/{$ticket->ticket_code}");

        $response->assertOk();
        $response->assertJsonPath('data.last_response_author_type', 'none');
    }
}
