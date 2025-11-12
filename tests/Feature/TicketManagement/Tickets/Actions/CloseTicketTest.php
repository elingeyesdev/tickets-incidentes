<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Tickets\Actions;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Events\TicketClosed;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Closing Tickets
 *
 * Tests the endpoint POST /api/tickets/:code/close
 *
 * Coverage:
 * - Authentication (unauthenticated, USER, AGENT)
 * - Permissions by role (AGENT can close any ticket, USER can close own resolved ticket)
 * - Status restrictions (USER cannot close pending/open tickets)
 * - closed_at timestamp is set
 * - Event triggering (TicketClosed)
 * - Cannot close already closed ticket
 * - User cannot close other user ticket
 * - Agent from different company cannot close
 * - last_response_author_type persists after close
 *
 * Expected Status Codes:
 * - 200: Ticket closed successfully
 * - 400: Cannot close already closed ticket
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (wrong user, wrong status, wrong company)
 *
 * Database Schema: ticketing.tickets
 * - id: UUID
 * - ticket_code: VARCHAR(50)
 * - company_id: UUID
 * - status: ENUM (open, pending, resolved, closed)
 * - closed_at: TIMESTAMPTZ (nullable)
 * - last_response_author_type: VARCHAR(20)
 */
class CloseTicketTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Permisos AGENT (Test 1) ====================

    /**
     * Test #1: Agent can close any ticket
     *
     * Verifies that AGENT role can close any ticket from their company,
     * regardless of status or owner.
     *
     * Expected: 200 OK with ticket closed
     * Database: status = 'closed', closed_at is set
     * Validation: last_response_author_type persists during close
     */
    #[Test]
    public function agent_can_close_any_ticket(): void
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

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
            'last_response_author_type' => 'agent',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'closed');
        $response->assertJsonPath('data.last_response_author_type', 'agent'); // Should persist
        $response->assertJsonStructure([
            'data' => [
                'id',
                'ticket_code',
                'status',
                'closed_at',
                'last_response_author_type',
            ],
        ]);

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
            'last_response_author_type' => 'agent', // Must persist
        ]);

        // Verify closed_at is not null
        $ticket->refresh();
        $this->assertNotNull($ticket->closed_at);
    }

    // ==================== GROUP 2: Permisos USER (Tests 2-4) ====================

    /**
     * Test #2: User can close own resolved ticket
     *
     * Verifies that USER role can close their own ticket ONLY when status is 'resolved'.
     *
     * Expected: 200 OK with ticket closed
     * Database: status = 'closed', closed_at is set
     */
    #[Test]
    public function user_can_close_own_resolved_ticket(): void
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
            'status' => 'resolved', // Key: status must be 'resolved'
            'last_response_author_type' => 'agent',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);

        // Verify closed_at is not null
        $ticket->refresh();
        $this->assertNotNull($ticket->closed_at);
    }

    /**
     * Test #3: User cannot close own pending ticket
     *
     * Verifies that USER role CANNOT close their own ticket when status is 'pending'.
     * Only 'resolved' tickets can be closed by users.
     *
     * Expected: 403 Forbidden
     * Database: status remains 'pending', closed_at remains null
     */
    #[Test]
    public function user_cannot_close_own_pending_ticket(): void
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
            'status' => 'pending', // User cannot close pending tickets
            'last_response_author_type' => 'agent',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT closed
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending', // Should remain unchanged
        ]);

        $ticket->refresh();
        $this->assertNull($ticket->closed_at);
    }

    /**
     * Test #4: User cannot close own open ticket
     *
     * Verifies that USER role CANNOT close their own ticket when status is 'open'.
     * Only 'resolved' tickets can be closed by users.
     *
     * Expected: 403 Forbidden
     * Database: status remains 'open', closed_at remains null
     */
    #[Test]
    public function user_cannot_close_own_open_ticket(): void
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
            'status' => 'open', // User cannot close open tickets
            'last_response_author_type' => 'none',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT closed
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'open', // Should remain unchanged
        ]);

        $ticket->refresh();
        $this->assertNull($ticket->closed_at);
    }

    // ==================== GROUP 3: Timestamps y Eventos (Tests 5-6) ====================

    /**
     * Test #5: Close sets closed_at timestamp
     *
     * Verifies that closing a ticket sets the closed_at timestamp to the current time.
     *
     * Expected: 200 OK with closed_at field populated
     * Database: closed_at is not null and is recent (within last minute)
     */
    #[Test]
    public function close_sets_closed_at_timestamp(): void
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

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
            'closed_at' => null, // Initially null
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['closed_at']]);

        // Verify closed_at is set and is recent
        $ticket->refresh();
        $this->assertNotNull($ticket->closed_at);

        // Verify timestamp is within the last minute
        $this->assertTrue(
            $ticket->closed_at->diffInSeconds(now()) < 60,
            'closed_at timestamp should be recent (within last 60 seconds)'
        );
    }

    /**
     * Test #6: Close triggers ticket closed event
     *
     * Verifies that closing a ticket triggers a TicketClosed event.
     *
     * Expected: TicketClosed event is dispatched
     */
    #[Test]
    public function close_triggers_ticket_closed_event(): void
    {
        // Arrange
        Event::fake();

        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign agent to company
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(200);

        // Verify event was dispatched
        Event::assertDispatched(TicketClosed::class);
    }

    // ==================== GROUP 4: Validaciones de Estado (Tests 7-8) ====================

    /**
     * Test #7: Cannot close already closed ticket
     *
     * Verifies that attempting to close an already closed ticket returns an error.
     *
     * Expected: 400 Bad Request
     * Database: status remains 'closed', closed_at unchanged
     */
    #[Test]
    public function cannot_close_already_closed_ticket(): void
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

        $user = User::factory()->withRole('USER')->create();
        $originalClosedAt = now()->subDays(2);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'closed', // Already closed
            'closed_at' => $originalClosedAt,
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(400);

        // Verify ticket remains closed with original closed_at
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);

        $ticket->refresh();
        $this->assertEquals(
            $originalClosedAt->timestamp,
            $ticket->closed_at->timestamp,
            'closed_at should not change when attempting to close an already closed ticket'
        );
    }

    /**
     * Test #8: User cannot close other user ticket
     *
     * Verifies that USER role cannot close tickets created by other users,
     * even if the ticket is in 'resolved' status.
     *
     * Expected: 403 Forbidden
     * Database: status remains unchanged, closed_at remains null
     */
    #[Test]
    public function user_cannot_close_other_user_ticket(): void
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
            'status' => 'resolved', // Even if resolved
            'last_response_author_type' => 'agent',
        ]);

        // Act - User B tries to close User A's ticket
        $response = $this->authenticateWithJWT($userB)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT closed
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'resolved', // Should remain unchanged
        ]);

        $ticket->refresh();
        $this->assertNull($ticket->closed_at);
    }

    // ==================== GROUP 5: Aislamiento de Empresa (Test 9) ====================

    /**
     * Test #9: Agent from different company cannot close
     *
     * Verifies that AGENT role cannot close tickets from other companies.
     *
     * Expected: 403 Forbidden
     * Database: status remains unchanged, closed_at remains null
     */
    #[Test]
    public function agent_from_different_company_cannot_close(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        // Assign agent to Company A
        $agent->assignRole('AGENT', $companyA->id);

        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // Create ticket in Company B
        $userB = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $userB->id,
            'status' => 'pending',
        ]);

        // Act - Agent from Company A tries to close Company B ticket
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT closed
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending', // Should remain unchanged
        ]);

        $ticket->refresh();
        $this->assertNull($ticket->closed_at);
    }

    // ==================== GROUP 6: Autenticación (Test 10) ====================

    /**
     * Test #10: Unauthenticated user cannot close
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     * Database: status remains unchanged, closed_at remains null
     */
    #[Test]
    public function unauthenticated_user_cannot_close(): void
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
            'status' => 'pending',
        ]);

        // Act - No authenticateWithJWT() call
        $response = $this->postJson("/api/tickets/{$ticket->ticket_code}/close");

        // Assert
        $response->assertStatus(401);

        // Verify ticket was NOT closed
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending', // Should remain unchanged
        ]);

        $ticket->refresh();
        $this->assertNull($ticket->closed_at);
    }

    // ==================== GROUP 7: Persistencia de Datos (Test 11) ====================

    /**
     * Test #11: last_response_author_type persists after ticket close
     *
     * Verifies that the last_response_author_type field does NOT change when closing a ticket.
     * Only status and closed_at should change.
     *
     * Expected: last_response_author_type remains unchanged
     * Database: Verify field value persists before and after close
     */
    #[Test]
    public function last_response_author_type_persists_after_ticket_close(): void
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

        $user = User::factory()->withRole('USER')->create();

        // Test multiple scenarios: 'none', 'user', 'agent'
        $scenarios = [
            ['last_response_author_type' => 'none', 'description' => 'No responses yet'],
            ['last_response_author_type' => 'user', 'description' => 'User responded last'],
            ['last_response_author_type' => 'agent', 'description' => 'Agent responded last'],
        ];

        foreach ($scenarios as $scenario) {
            $ticket = Ticket::factory()->create([
                'company_id' => $company->id,
                'category_id' => $category->id,
                'created_by_user_id' => $user->id,
                'status' => 'pending',
                'last_response_author_type' => $scenario['last_response_author_type'],
            ]);

            $originalValue = $ticket->last_response_author_type;

            // Act
            $response = $this->authenticateWithJWT($agent)
                ->postJson("/api/tickets/{$ticket->ticket_code}/close");

            // Assert
            $response->assertStatus(200);
            $response->assertJsonPath('data.last_response_author_type', $originalValue);

            // Verify field persisted in database
            $this->assertDatabaseHas('ticketing.tickets', [
                'id' => $ticket->id,
                'status' => 'closed',
                'last_response_author_type' => $originalValue, // Should NOT change
            ]);

            $ticket->refresh();
            $this->assertEquals(
                $originalValue,
                $ticket->last_response_author_type,
                "last_response_author_type should persist after close (scenario: {$scenario['description']})"
            );
        }
    }
}
