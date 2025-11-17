<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Integration;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Integration Tests for Auto-Assignment Flow
 *
 * Tests the PostgreSQL trigger-based auto-assignment mechanism:
 * - First agent response triggers auto-assignment
 * - Status changes from 'open' to 'pending'
 * - first_response_at timestamp set
 * - owner_agent_id set to responding agent
 * - last_response_author_type updated to 'agent'
 *
 * Coverage:
 * - Auto-assignment trigger behavior
 * - Field updates in single transaction
 * - User responses do NOT trigger assignment
 * - Subsequent agent responses preserve first assignment
 */
class AutoAssignmentFlowTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test #1: First Agent Response Triggers Auto-Assignment
     *
     * Verifies that when an agent responds to an unassigned ticket:
     * 1. Ticket created (status=open, owner_agent_id=null)
     * 2. Agent responses → TRIGGER fires (PostgreSQL trigger)
     * 3. Status changed to 'pending'
     * 4. owner_agent_id set to agent
     * 5. last_response_author_type='agent'
     * 6. first_response_at set
     * 7. All updates happen in single transaction
     */
    #[Test]
    public function test_first_agent_response_triggers_auto_assignment(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        // Create unassigned ticket
        $this->authenticateWithJWT($user);
        $createResponse = $this->postJson('/api/tickets', [
            'title' => 'Auto-Assignment Trigger Test',
            'description' => 'Testing PostgreSQL trigger for auto-assignment',
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $createResponse->assertStatus(201);
        $ticketCode = $createResponse->json('data.ticket_code');
        $ticket = Ticket::where('ticket_code', $ticketCode)->firstOrFail();

        // Verify initial state (unassigned)
        $this->assertNull($ticket->owner_agent_id, 'Ticket should start unassigned');
        $this->assertEquals('open', $ticket->status->value, 'Ticket should start as open');
        $this->assertEquals('none', $ticket->last_response_author_type, 'last_response_author_type should be none');
        $this->assertNull($ticket->first_response_at, 'first_response_at should be null initially');

        // ==================== Act ====================
        $this->authenticateWithJWT($agent);
        $responseTime = Carbon::now();
        $agentResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'First agent response - should trigger auto-assignment',
        ]);

        // ==================== Assert ====================
        $agentResponse->assertStatus(201);

        // Verify trigger fired and updated all fields in single transaction
        $ticket->refresh();

        $this->assertEquals('pending', $ticket->status->value, 'Status should change to pending via trigger');
        $this->assertEquals($agent->id, $ticket->owner_agent_id, 'owner_agent_id should be set to responding agent');
        $this->assertEquals('agent', $ticket->last_response_author_type, 'last_response_author_type should be agent');
        $this->assertNotNull($ticket->first_response_at, 'first_response_at should be set');
        $this->assertTrue(
            $ticket->first_response_at->greaterThanOrEqualTo($responseTime->subSeconds(2)),
            'first_response_at should be approximately now'
        );

        // Verify API response reflects trigger changes
        $getResponse = $this->getJson("/api/tickets/{$ticketCode}");
        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.status', 'pending');
        $getResponse->assertJsonPath('data.owner_agent_id', $agent->id);
        $getResponse->assertJsonPath('data.last_response_author_type', 'agent');

        // Verify DB state matches
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
        ]);

        // Verify response was created
        $this->assertDatabaseHas('ticketing.ticket_responses', [
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
        ]);
    }

    /**
     * Test #2: Auto-Assignment Changes Status to Pending
     *
     * Verifies that:
     * 1. Create unassigned, open ticket
     * 2. Agent POST /api/tickets/{code}/responses
     * 3. Status updated from 'open' to 'pending'
     * 4. DB state and API response both show 'pending'
     */
    #[Test]
    public function test_auto_assignment_changes_status_to_pending(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        // Create ticket in 'open' status
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'created_by_user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TicketStatus::OPEN,
            'owner_agent_id' => null,
            'last_response_author_type' => 'none',
            'first_response_at' => null,
        ]);

        // Verify starting state
        $this->assertEquals('open', $ticket->status->value, 'Ticket should start as open');
        $this->assertNull($ticket->owner_agent_id, 'Ticket should be unassigned');

        // ==================== Act ====================
        $this->authenticateWithJWT($agent);
        $agentResponse = $this->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
            'content' => 'Agent response - should change status to pending',
        ]);

        // ==================== Assert ====================
        $agentResponse->assertStatus(201);

        // Verify status changed to pending in DB
        $ticket->refresh();
        $this->assertEquals('pending', $ticket->status->value, 'Status should be pending after agent response');

        // Verify API response shows pending
        $getResponse = $this->getJson("/api/tickets/{$ticket->ticket_code}");
        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.status', 'pending');

        // Verify DB state
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test #3: Auto-Assignment Sets first_response_at
     *
     * Verifies that:
     * 1. Create open ticket with first_response_at=null
     * 2. Agent responds
     * 3. first_response_at is NOT null after response
     * 4. Timestamp is approximately now()
     */
    #[Test]
    public function test_auto_assignment_sets_first_response_at(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        // Create ticket with null first_response_at
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'created_by_user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TicketStatus::OPEN,
            'owner_agent_id' => null,
            'first_response_at' => null,
        ]);

        // Verify starting state
        $this->assertNull($ticket->first_response_at, 'first_response_at should be null initially');

        // ==================== Act ====================
        $this->authenticateWithJWT($agent);
        $responseTime = Carbon::now();
        $agentResponse = $this->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
            'content' => 'Agent first response - should set first_response_at',
        ]);

        // ==================== Assert ====================
        $agentResponse->assertStatus(201);

        // Verify first_response_at was set
        $ticket->refresh();
        $this->assertNotNull($ticket->first_response_at, 'first_response_at should be set after agent response');

        // Verify timestamp is approximately now
        $this->assertTrue(
            $ticket->first_response_at->greaterThanOrEqualTo($responseTime->subSeconds(2)),
            'first_response_at should be greater than or equal to response time minus 2 seconds'
        );
        $this->assertTrue(
            $ticket->first_response_at->lessThanOrEqualTo(Carbon::now()->addSeconds(2)),
            'first_response_at should be less than or equal to now plus 2 seconds'
        );

        // Verify API response includes first_response_at
        $getResponse = $this->getJson("/api/tickets/{$ticket->ticket_code}");
        $getResponse->assertStatus(200);
        $getResponse->assertJsonStructure([
            'data' => [
                'timeline' => [
                    'first_response_at',
                ],
            ],
        ]);
        $this->assertNotNull($getResponse->json('data.timeline.first_response_at'), 'API should return first_response_at');

        // Verify DB state
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
        ]);
        $ticketFromDb = Ticket::find($ticket->id);
        $this->assertNotNull($ticketFromDb->first_response_at, 'DB should have first_response_at set');
    }

    /**
     * Test #4: User Response Does Not Trigger Auto-Assignment
     *
     * Verifies that:
     * 1. Create open ticket (owner_agent_id=null)
     * 2. USER responds (not agent)
     * 3. owner_agent_id STAYS null (NOT assigned)
     * 4. last_response_author_type='user'
     * 5. Status stays 'open' (no pending)
     */
    #[Test]
    public function test_user_response_does_not_trigger_auto_assignment(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();

        // Create ticket as User A
        $this->authenticateWithJWT($userA);
        $createResponse = $this->postJson('/api/tickets', [
            'title' => 'User Response No Assignment Test',
            'description' => 'Testing that user responses do not trigger auto-assignment',
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $createResponse->assertStatus(201);
        $ticketCode = $createResponse->json('data.ticket_code');
        $ticket = Ticket::where('ticket_code', $ticketCode)->firstOrFail();

        // Verify initial state
        $this->assertNull($ticket->owner_agent_id, 'Ticket should be unassigned');
        $this->assertEquals('open', $ticket->status->value, 'Status should be open');
        $this->assertEquals('none', $ticket->last_response_author_type);

        // ==================== Act ====================
        // User A responds to their own ticket
        $this->authenticateWithJWT($userA);
        $userResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'User response - should NOT trigger auto-assignment',
        ]);

        // ==================== Assert ====================
        $userResponse->assertStatus(201);

        // Verify ticket remains unassigned
        $ticket->refresh();
        $this->assertNull($ticket->owner_agent_id, 'owner_agent_id should STAY null (user response does not assign)');
        $this->assertEquals('open', $ticket->status->value, 'Status should STAY open (not pending)');
        $this->assertEquals('user', $ticket->last_response_author_type, 'last_response_author_type should be user');
        $this->assertNull($ticket->first_response_at, 'first_response_at should remain null (only set on agent response)');

        // Verify API response
        $getResponse = $this->getJson("/api/tickets/{$ticketCode}");
        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.status', 'open');
        $getResponse->assertJsonPath('data.owner_agent_id', null);
        $getResponse->assertJsonPath('data.last_response_author_type', 'user');

        // Verify DB state
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'status' => 'open',
            'owner_agent_id' => null,
            'last_response_author_type' => 'user',
        ]);

        // Verify response was created
        $this->assertDatabaseHas('ticketing.ticket_responses', [
            'ticket_id' => $ticket->id,
            'author_id' => $userA->id,
            'author_type' => 'user',
        ]);
    }

    /**
     * Test #5: Second Agent Response Does Not Change Owner
     *
     * Verifies that:
     * 1. Ticket: owner_agent_id=Agent A
     * 2. Agent B responds
     * 3. owner_agent_id STAYS Agent A (not changed to B)
     * 4. last_response_author_type='agent' (updated)
     */
    #[Test]
    public function test_second_agent_response_does_not_change_owner(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user = User::factory()->withRole('USER')->create();
        $agentA = User::factory()->withRole('AGENT', $company->id)->create();
        $agentB = User::factory()->withRole('AGENT', $company->id)->create();

        // Create ticket already assigned to Agent A
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'created_by_user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TicketStatus::PENDING,
            'owner_agent_id' => $agentA->id,
            'last_response_author_type' => 'agent',
            'first_response_at' => Carbon::now()->subMinutes(10),
        ]);

        // Verify starting state
        $this->assertEquals($agentA->id, $ticket->owner_agent_id, 'Ticket should be assigned to Agent A');
        $this->assertEquals('pending', $ticket->status->value);

        // ==================== Act ====================
        // Agent B responds
        $this->authenticateWithJWT($agentB);
        $agentBResponse = $this->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
            'content' => 'Agent B response - should NOT change owner from Agent A',
        ]);

        // ==================== Assert ====================
        $agentBResponse->assertStatus(201);

        // Verify owner STAYS Agent A (not changed to Agent B)
        $ticket->refresh();
        $this->assertEquals($agentA->id, $ticket->owner_agent_id, 'owner_agent_id should STAY Agent A (not change to Agent B)');
        $this->assertEquals('agent', $ticket->last_response_author_type, 'last_response_author_type should be agent');
        $this->assertEquals('pending', $ticket->status->value, 'Status should remain pending');

        // Verify API response
        $getResponse = $this->getJson("/api/tickets/{$ticket->ticket_code}");
        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.owner_agent_id', $agentA->id);
        $getResponse->assertJsonPath('data.last_response_author_type', 'agent');

        // Verify DB state
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'owner_agent_id' => $agentA->id,
            'last_response_author_type' => 'agent',
            'status' => 'pending',
        ]);

        // Verify Agent B's response was created
        $this->assertDatabaseHas('ticketing.ticket_responses', [
            'ticket_id' => $ticket->id,
            'author_id' => $agentB->id,
            'author_type' => 'agent',
        ]);

        // Verify Agent A's original assignment is unchanged
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'owner_agent_id' => $agentA->id, // NOT agentB
        ]);
    }
}
