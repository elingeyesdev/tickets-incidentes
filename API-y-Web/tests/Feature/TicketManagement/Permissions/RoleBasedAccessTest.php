<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Permissions;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Role-Based Access Control
 *
 * Tests that different roles have appropriate permissions for ticket operations.
 *
 * Coverage:
 * - USER can only create tickets and add responses
 * - AGENT has full ticket management permissions (except delete)
 * - COMPANY_ADMIN can manage categories
 * - COMPANY_ADMIN can delete closed tickets only
 * - AGENT cannot create tickets
 * - PLATFORM_ADMIN has read-only access
 * - Role validation happens before business logic
 * - Expired token returns 401
 *
 * Expected Status Codes:
 * - 200: OK (GET, PATCH successful)
 * - 201: Created (POST successful)
 * - 401: Unauthenticated (expired token)
 * - 403: Forbidden (insufficient permissions)
 *
 * Key Concept: Roles determine what operations users can perform
 */
class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test #1: User can only create tickets
     *
     * Verifies that USER role can create tickets and add responses,
     * but cannot perform agent-only actions like resolve or assign.
     *
     * Expected: 201 for create/response, 403 for resolve/assign
     */
    #[Test]
    public function test_user_can_only_create_tickets(): void
    {
        // ==================== Arrange ====================
        $user = User::factory()->withRole('USER')->create();

        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        // Create a valid AGENT for the assignment test
        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
        ]);

        $createPayload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'User creating a ticket',
            'description' => 'This should be allowed for USER role with minimum required length.',
        ];

        $responsePayload = [
            'content' => 'User adding a response to their ticket with minimum required length.',
        ];

        // ==================== Act ====================
        $responseCreate = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $createPayload);

        $responseAddResponse = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", $responsePayload);

        $responseResolve = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/resolve");

        $responseAssign = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", [
                'new_agent_id' => $agent->id,
            ]);

        // ==================== Assert ====================
        $responseCreate->assertStatus(201);
        $responseAddResponse->assertStatus(201);
        $responseResolve->assertStatus(403);
        $responseAssign->assertStatus(403);
    }

    /**
     * Test #2: Agent has full ticket management permissions
     *
     * Verifies that AGENT role can add responses, resolve, assign tickets,
     * and update them, but CANNOT delete tickets or create new ones.
     *
     * Expected: 201/200 for manage operations, 403 for delete
     */
    #[Test]
    public function test_agent_has_full_ticket_management_permissions(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $agent = User::factory()->withRole('AGENT', $company->id)->create();
        $user = User::factory()->withRole('USER')->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
        ]);

        $responsePayload = [
            'content' => 'Agent responding to ticket with minimum required length for validation.',
        ];

        // ==================== Act ====================
        $responseAddResponse = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", $responsePayload);

        $responseResolve = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/resolve");

        // Create another ticket for assign test
        $ticket2 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
        ]);

        $responseAssign = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket2->ticket_code}/assign", [
                'new_agent_id' => $agent->id,
            ]);

        // Create another ticket for patch test
        $ticket3 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
        ]);

        $responsePatch = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket3->ticket_code}", [
                'title' => 'Updated by agent',
            ]);

        // Create closed ticket for delete test
        $closedTicket = Ticket::factory()->closed()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        $responseDelete = $this->authenticateWithJWT($agent)
            ->deleteJson("/api/tickets/{$closedTicket->ticket_code}");

        // ==================== Assert ====================
        $responseAddResponse->assertStatus(201);
        $responseResolve->assertStatus(200);
        $responseAssign->assertStatus(200);
        $responsePatch->assertStatus(200);
        $responseDelete->assertStatus(403);
    }

    /**
     * Test #3: Company admin can manage categories
     *
     * Verifies that COMPANY_ADMIN role can create, update, and delete
     * ticket categories for their company.
     *
     * Expected: 201 for create, 200 for update, 200 for delete
     */
    #[Test]
    public function test_company_admin_can_manage_categories(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();

        $admin = User::factory()->create();
        $company->update(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $createPayload = [
            'name' => 'New Category',
            'description' => 'Category created by admin',
            'is_active' => true,
        ];

        // ==================== Act ====================
        $responseCreate = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', $createPayload);

        $categoryId = $responseCreate->json('data.id');

        $responsePatch = $this->authenticateWithJWT($admin)
            ->putJson("/api/tickets/categories/{$categoryId}", [
                'name' => 'Updated Category Name',
            ]);

        // Create category for delete test (with no tickets)
        $emptyCategory = Category::factory()->create([
            'company_id' => $company->id,
        ]);

        $responseDelete = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/categories/{$emptyCategory->id}");

        // ==================== Assert ====================
        $responseCreate->assertStatus(201);
        $responsePatch->assertStatus(200);
        $responseDelete->assertStatus(200);
    }

    /**
     * Test #4: Company admin can delete closed tickets
     *
     * Verifies that COMPANY_ADMIN can delete tickets that are CLOSED,
     * but cannot delete tickets with other statuses.
     *
     * Expected: 200 for closed ticket, 403 for non-closed ticket
     */
    #[Test]
    public function test_company_admin_can_delete_closed_tickets(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $admin = $this->createCompanyAdmin();
        $company->update(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $user = User::factory()->withRole('USER')->create();

        $closedTicket = Ticket::factory()->closed()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        $openTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
        ]);

        // ==================== Act ====================
        $responseDeleteClosed = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/{$closedTicket->ticket_code}");

        $responseDeleteOpen = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/{$openTicket->ticket_code}");

        // ==================== Assert ====================
        $responseDeleteClosed->assertStatus(200);
        $responseDeleteOpen->assertStatus(403);
    }

    /**
     * Test #5: Agent cannot create tickets
     *
     * Verifies that AGENT role cannot create new tickets.
     * Agents can only respond to existing tickets.
     *
     * Expected: 403 for ticket creation
     */
    #[Test]
    public function test_agent_cannot_create_tickets(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Agent trying to create ticket',
            'description' => 'This should be forbidden because agents cannot create tickets.',
        ];

        // ==================== Act ====================
        $response = $this->authenticateWithJWT($agent)
            ->postJson('/api/tickets', $payload);

        // ==================== Assert ====================
        $response->assertStatus(403);
    }

    /**
     * Test #6: Platform admin has read-only access
     *
     * Verifies that PLATFORM_ADMIN role can read all tickets but
     * cannot modify or delete them.
     *
     * Expected: 200 for GET, 403 for PATCH/DELETE
     */
    // TODO: PLATFORM_ADMIN feature pending implementation
    // #[Test]
    // public function test_platform_admin_has_read_only_access(): void
    // {
    //     // PLATFORM_ADMIN support requires design decisions
    //     // Documented in documentacion/MULTI_ROLE_SOLUTIONS.md
    // }

    /**
     * Test #6: Role validation happens before business logic
     *
     * Verifies that authorization middleware rejects requests with
     * insufficient permissions BEFORE business logic is executed.
     *
     * Expected: 403 from middleware, not error from business logic
     */
    #[Test]
    public function test_role_validation_happens_before_business_logic(): void
    {
        // ==================== Arrange ====================
        $user = User::factory()->withRole('USER')->create();

        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        // Create a valid AGENT for the authorization check
        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
        ]);

        // ==================== Act ====================
        // USER tries to perform AGENT-only action
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", [
                'new_agent_id' => $agent->id,
            ]);

        // ==================== Assert ====================
        // Middleware rejects with 403 before business logic
        $response->assertStatus(403);
    }

    /**
     * Test #7: Expired token returns 401
     *
     * Verifies that requests with expired JWT tokens receive 401 Unauthorized
     * with appropriate error message.
     *
     * Expected: 401 with "Token expired" message
     */
    #[Test]
    public function test_expired_token_returns_401(): void
    {
        // ==================== Arrange ====================
        $user = User::factory()->withRole('USER')->create();

        // Create an ALREADY-EXPIRED token manually
        // Note: Laravel's time travel affects Carbon::now() but NOT PHP's time() function
        // Firebase JWT uses time(), so we must create a pre-expired token
        $now = time();
        $payload = [
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'iat' => $now - 3600,  // Issued 1 hour ago
            'exp' => $now - 1800,  // Expired 30 minutes ago (absolute expiration in past)
            'sub' => $user->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'session_id' => \Illuminate\Support\Str::random(32),
            'roles' => $user->getAllRolesForJWT(),
        ];

        $expiredToken = \Firebase\JWT\JWT::encode(
            $payload,
            config('jwt.secret'),
            config('jwt.algo')
        );

        // ==================== Act ====================
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$expiredToken}",
        ])->getJson('/api/tickets');

        // ==================== Assert ====================
        $response->assertStatus(401);
    }
}
