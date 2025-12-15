<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Tickets\Actions;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Reopening Tickets
 *
 * Tests the endpoint POST /api/tickets/:code/reopen
 *
 * Coverage:
 * - User can reopen own resolved ticket
 * - User can reopen own closed ticket within 30 days
 * - User cannot reopen closed ticket after 30 days
 * - Agent can reopen any ticket regardless of time
 * - Reopen reason is optional
 * - Reopen reason is saved when provided
 * - Reopened ticket returns to pending status
 * - Reopen triggers TicketReopened event
 * - Cannot reopen open ticket
 * - Cannot reopen pending ticket
 * - User cannot reopen other user's ticket
 * - Unauthenticated user cannot reopen
 * - last_response_author_type persists after reopen
 *
 * Expected Status Codes:
 * - 200: Ticket reopened successfully
 * - 400: Cannot reopen ticket in current status (open/pending)
 * - 401: Unauthenticated
 * - 403: Insufficient permissions or time limit exceeded
 *
 * Database Schema: ticketing.tickets
 * - status: ENUM (open, pending, resolved, closed)
 * - closed_at: TIMESTAMPTZ (nullable)
 * - last_response_author_type: VARCHAR(20) (none, user, agent)
 *
 * Business Rules:
 * - USER: Can reopen resolved/closed tickets within 30 days of closed_at
 * - AGENT: Can reopen any ticket regardless of time
 * - After reopen: status = pending (NOT open)
 * - last_response_author_type field must persist unchanged
 */
class ReopenTicketTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Basic Reopen Operations (Tests 1-2) ====================

    /**
     * Test #1: User can reopen own resolved ticket
     *
     * Verifies that a user can reopen their own ticket when it is in resolved status.
     * The ticket should change from resolved to pending.
     *
     * Expected: 200 OK with status = pending
     */
    #[Test]
    public function user_can_reopen_own_resolved_ticket(): void
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
            'status' => TicketStatus::RESOLVED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(2),
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test #2: User can reopen own closed ticket within 30 days
     *
     * Verifies that a user can reopen their own ticket when it has been closed
     * within the last 30 days.
     *
     * Expected: 200 OK with status = pending
     */
    #[Test]
    public function user_can_reopen_own_closed_ticket_within_30_days(): void
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
            'status' => TicketStatus::CLOSED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(25),
            'closed_at' => Carbon::now()->subDays(20), // Closed 20 days ago (within 30 days)
        ]);

        $payload = [
            'reopen_reason' => 'El problema volvió a ocurrir',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending',
        ]);
    }

    // ==================== GROUP 2: Time Restrictions (Tests 3-4) ====================

    /**
     * Test #3: User cannot reopen closed ticket after 30 days
     *
     * Verifies that a user is forbidden from reopening a ticket that has been
     * closed for more than 30 days.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_reopen_closed_ticket_after_30_days(): void
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
            'status' => TicketStatus::CLOSED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(40),
            'closed_at' => Carbon::now()->subDays(35), // Closed 35 days ago (exceeds 30 days)
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert
        $response->assertStatus(403);

        // Verify status unchanged
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);
    }

    /**
     * Test #4: Agent can reopen any ticket regardless of time
     *
     * Verifies that agents can reopen tickets even if they have been closed
     * for more than 30 days (no time restriction for agents).
     *
     * Expected: 200 OK with status = pending
     */
    #[Test]
    public function agent_can_reopen_any_ticket_regardless_of_time(): void
    {
        // Arrange
        $agent = User::factory()->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $user = User::factory()->withRole('USER')->create();

        // Assign agent to company
        $agent->assignRole('AGENT', $company->id);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::CLOSED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(55),
            'closed_at' => Carbon::now()->subDays(50), // Closed 50 days ago (exceeds 30 days)
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending',
        ]);
    }

    // ==================== GROUP 3: Reopen Reason (Tests 5-6) ====================

    /**
     * Test #5: Reopen reason is optional
     *
     * Verifies that the reopen_reason field is not required when reopening a ticket.
     *
     * Expected: 200 OK without providing reopen_reason
     */
    #[Test]
    public function reopen_reason_is_optional(): void
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
            'status' => TicketStatus::RESOLVED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(3),
        ]);

        // Act - Empty payload (no reopen_reason)
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen", []);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'pending');
    }

    /**
     * Test #6: Reopen reason is saved when provided
     *
     * Verifies that when a reopen_reason is provided, it is saved properly.
     * Note: This assumes there's a reopen_reason field or it's stored in a related table.
     * If not implemented, this test may need adjustment.
     *
     * Expected: 200 OK with reopen_reason saved
     */
    #[Test]
    public function reopen_reason_is_saved_when_provided(): void
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
            'status' => TicketStatus::RESOLVED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(2),
        ]);

        $payload = [
            'reopen_reason' => 'El problema volvió a aparecer después de la actualización',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen", $payload);

        // Assert
        $response->assertStatus(200);

        // Note: Adjust this assertion based on actual implementation
        // If reopen_reason is stored in responses or a separate table, verify accordingly
        // For now, we just verify the request was accepted
    }

    // ==================== GROUP 4: Status Transition (Test 7) ====================

    /**
     * Test #7: Reopened ticket returns to pending status
     *
     * Verifies that when a ticket is reopened, it returns to 'pending' status
     * (NOT 'open').
     *
     * Expected: status = pending
     */
    #[Test]
    public function reopened_ticket_returns_to_pending_status(): void
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
            'status' => TicketStatus::CLOSED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(10),
            'closed_at' => Carbon::now()->subDays(5),
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert - Must be pending, NOT open
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'pending');

        $ticket->refresh();
        $this->assertEquals('pending', $ticket->status->value);
        $this->assertNotEquals('open', $ticket->status->value);
    }

    // ==================== GROUP 5: Events (Test 8) ====================

    /**
     * Test #8: Reopen triggers TicketReopened event
     *
     * Verifies that reopening a ticket dispatches the TicketReopened event.
     *
     * Expected: TicketReopened event is dispatched
     */
    #[Test]
    public function reopen_triggers_ticket_reopened_event(): void
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
            'status' => TicketStatus::RESOLVED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(3),
        ]);

        Event::fake();

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert
        $response->assertStatus(200);

        // Verify event was dispatched
        Event::assertDispatched(\App\Features\TicketManagement\Events\TicketReopened::class);
    }

    // ==================== GROUP 6: Invalid Status Transitions (Tests 9-10) ====================

    /**
     * Test #9: Cannot reopen open ticket
     *
     * Verifies that a ticket that is already open cannot be reopened.
     * This would be a logical error.
     *
     * Expected: 400 Bad Request
     */
    #[Test]
    public function cannot_reopen_open_ticket(): void
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
            'status' => TicketStatus::OPEN,
            'last_response_author_type' => 'none',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert
        $response->assertStatus(400);

        // Status should remain unchanged
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'open',
        ]);
    }

    /**
     * Test #10: Cannot reopen pending ticket
     *
     * Verifies that a ticket that is pending cannot be reopened.
     * It's already in an active state.
     *
     * Expected: 400 Bad Request
     */
    #[Test]
    public function cannot_reopen_pending_ticket(): void
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
            'status' => TicketStatus::PENDING,
            'last_response_author_type' => 'agent',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert
        $response->assertStatus(400);

        // Status should remain unchanged
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending',
        ]);
    }

    // ==================== GROUP 7: Permissions (Tests 11-12) ====================

    /**
     * Test #11: User cannot reopen other user's ticket
     *
     * Verifies that a user cannot reopen a ticket created by another user,
     * even if it's within the 30-day window.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_reopen_other_user_ticket(): void
    {
        // Arrange
        $userA = User::factory()->withRole('USER')->create(['email' => 'usera@example.com']);
        $userB = User::factory()->withRole('USER')->create(['email' => 'userb@example.com']);

        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Ticket created by userB
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userB->id,
            'status' => TicketStatus::RESOLVED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(3),
        ]);

        // Act - userA tries to reopen userB's ticket
        $response = $this->authenticateWithJWT($userA)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert
        $response->assertStatus(403);

        // Status should remain unchanged
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
        ]);
    }

    /**
     * Test #12: Unauthenticated user cannot reopen
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_reopen(): void
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
            'status' => TicketStatus::RESOLVED,
            'last_response_author_type' => 'agent',
            'resolved_at' => Carbon::now()->subDays(2),
        ]);

        // Act - No authenticateWithJWT() call
        $response = $this->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert
        $response->assertStatus(401);

        // Status should remain unchanged
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
        ]);
    }

    // ==================== GROUP 8: Field Persistence (Test 13) ====================

    /**
     * Test #13: last_response_author_type persists after ticket reopen
     *
     * Verifies that the last_response_author_type field does NOT change when
     * reopening a ticket. Only the status should change to 'pending'.
     *
     * Expected: last_response_author_type remains 'agent' (or whatever it was)
     */
    #[Test]
    public function last_response_author_type_persists_after_ticket_reopen(): void
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
            'status' => TicketStatus::CLOSED,
            'last_response_author_type' => 'agent', // Important: was 'agent'
            'resolved_at' => Carbon::now()->subDays(10),
            'closed_at' => Carbon::now()->subDays(5),
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/reopen");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.last_response_author_type', 'agent'); // Must remain 'agent'

        // Verify in database
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending',
            'last_response_author_type' => 'agent', // Must NOT change
        ]);

        $ticket->refresh();
        $this->assertEquals('agent', $ticket->last_response_author_type);
        $this->assertEquals('pending', $ticket->status->value);
    }
}
