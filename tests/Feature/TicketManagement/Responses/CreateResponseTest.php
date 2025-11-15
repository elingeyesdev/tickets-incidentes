<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Responses;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating Ticket Response
 *
 * Tests the endpoint POST /api/tickets/:code/responses
 *
 * Coverage:
 * - User responses (permissions, content validation)
 * - Agent responses (auto-assignment, state transitions)
 * - Notifications
 * - last_response_author_type tracking
 * - State machine validations (OPEN→PENDING, PENDING→OPEN)
 */
class CreateResponseTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Permisos y Autenticación ====================

    /**
     * Test #1: User can respond to own ticket
     * Verifies that a user owner can create a response to their ticket
     * Expected: 201 Created
     */
    #[Test]
    public function user_can_respond_to_own_ticket(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'This is a test response from user.',
            ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.author_type', 'user');
        $response->assertJsonPath('data.author_id', $user->id);
    }

    /**
     * Test #2: Agent can respond to any company ticket
     * Verifies that an AGENT can respond to any ticket in their company
     * Expected: 201 Created
     */
    #[Test]
    public function agent_can_respond_to_any_company_ticket(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Response from agent.',
            ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.author_type', 'agent');
        $response->assertJsonPath('data.author_id', $agent->id);
    }

    /**
     * Test #3: Validates response_content is required
     * Verifies that missing response_content returns 422
     * Expected: 422 Unprocessable Entity
     */
    #[Test]
    public function validates_response_content_is_required(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                // Missing response_content
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('content');
    }

    /**
     * Test #4: Validates response_content length
     * Verifies that content must be 1-5000 characters
     * Expected: 422 for empty or >5000 chars
     */
    #[Test]
    public function validates_response_content_length(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act - Empty content
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => '',
            ]);

        // Assert - Empty fails
        $response->assertStatus(422);

        // Act - Content too long (6001 chars)
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => str_repeat('a', 6001),
            ]);

        // Assert - Too long fails
        $response->assertStatus(422);

        // Act - Valid content (1 char)
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'a',
            ]);

        // Assert - Valid passes
        $response->assertStatus(201);

        // Act - Valid content (5000 chars)
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => str_repeat('b', 5000),
            ]);

        // Assert - Valid passes
        $response->assertStatus(201);
    }

    /**
     * Test #5: author_type is set automatically
     * Verifies that author_type is 'user' for users, 'agent' for agents
     * Expected: 201 with correct author_type
     */
    #[Test]
    public function author_type_is_set_automatically(): void
    {
        // Arrange - User response
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act - User responds
        $userResponse = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'User response.',
            ]);

        // Assert
        $userResponse->assertStatus(201);
        $userResponse->assertJsonPath('data.author_type', 'user');

        // Arrange - Agent response
        $agent = User::factory()->withRole('AGENT')->create();
        $agent->assignRole('AGENT', $company->id);

        // Act - Agent responds
        $agentResponse = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Agent response.',
            ]);

        // Assert
        $agentResponse->assertStatus(201);
        $agentResponse->assertJsonPath('data.author_type', 'agent');
    }

    // ==================== GROUP 2: Auto-assignment y State Machine ====================

    /**
     * Test #6: First agent response triggers auto-assignment ⭐ CRÍTICO
     * Verifies that when first agent responds to OPEN unassigned ticket:
     * - owner_agent_id is set to responding agent
     * - status changes to PENDING
     * - last_response_author_type updates to 'agent'
     * Expected: 201 + owner_agent_id set + status=pending + last_response_author_type='agent'
     */
    #[Test]
    public function first_agent_response_triggers_auto_assignment(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => null,
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'First agent response to assign ticket.',
            ]);

        // Assert
        $response->assertStatus(201);

        // Reload ticket from DB to verify state changes
        $ticket->refresh();

        // Verify auto-assignment
        $this->assertEquals($agent->id, $ticket->owner_agent_id);

        // Verify status changed to pending
        $this->assertEquals('pending', $ticket->status);

        // Verify last_response_author_type updated
        $this->assertEquals('agent', $ticket->last_response_author_type);

        // Verify response in response body
        $response->assertJsonPath('data.author_type', 'agent');
    }

    /**
     * Test #7: Auto-assignment only happens once
     * Verifies that second agent response does NOT change owner_agent_id
     * Expected: 201 + owner_agent_id stays first agent + last_response_author_type updates to second agent
     */
    #[Test]
    public function auto_assignment_only_happens_once(): void
    {
        // Arrange
        $agent1 = User::factory()->withRole('AGENT')->create();
        $agent2 = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent1->assignRole('AGENT', $company->id);
        $agent2->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => null,
        ]);

        // First agent response - triggers assignment
        $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'First agent response.',
            ]);

        $ticket->refresh();
        $firstOwner = $ticket->owner_agent_id;

        // Second agent response - should NOT change owner
        $response = $this->authenticateWithJWT($agent2)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Second agent response.',
            ]);

        // Assert
        $response->assertStatus(201);

        $ticket->refresh();

        // Owner should still be agent1
        $this->assertEquals($firstOwner, $ticket->owner_agent_id);
        $this->assertEquals($agent1->id, $ticket->owner_agent_id);

        // last_response_author_type should update to 'agent' (from second response)
        $this->assertEquals('agent', $ticket->last_response_author_type);
    }

    /**
     * Test #8: First agent response sets first_response_at
     * Verifies that first_response_at timestamp is set on first response
     * Expected: 201 + first_response_at is set
     */
    #[Test]
    public function first_agent_response_sets_first_response_at(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'first_response_at' => null,
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'First response sets timestamp.',
            ]);

        // Assert
        $response->assertStatus(201);

        $ticket->refresh();

        // first_response_at should be set
        $this->assertNotNull($ticket->first_response_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $ticket->first_response_at);
    }

    /**
     * Test #9: User response does NOT trigger auto-assignment
     * Verifies that when user responds, owner_agent_id stays null
     * Expected: 201 + owner_agent_id remains null + last_response_author_type='user'
     */
    #[Test]
    public function user_response_does_not_trigger_auto_assignment(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => null,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'User response.',
            ]);

        // Assert
        $response->assertStatus(201);

        $ticket->refresh();

        // owner_agent_id should remain null
        $this->assertNull($ticket->owner_agent_id);

        // last_response_author_type should be 'user'
        $this->assertEquals('user', $ticket->last_response_author_type);
    }

    /**
     * Test #10: Response triggers ResponseAdded event
     * Verifies that creating a response dispatches ResponseAdded event
     * Expected: 201 + Event dispatched
     */
    #[Test]
    public function response_triggers_response_added_event(): void
    {
        // Arrange
        \Event::fake();

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Trigger event.',
            ]);

        // Assert
        $response->assertStatus(201);

        \Event::assertDispatched(\App\Features\TicketManagement\Events\ResponseAdded::class);
    }

    // ==================== GROUP 3: Notificaciones y Permisos ====================

    /**
     * Test #11: Response sends notification to relevant parties
     * Verifies notifications are sent: user response → agent, agent response → user
     * Expected: 201 + Notifications queued
     */
    #[Test]
    public function response_sends_notification_to_relevant_parties(): void
    {
        // Arrange
        \Notification::fake();

        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => $agent->id,
        ]);

        // Act - User responds to agent
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'User notification test.',
            ]);

        // Assert - Notification sent to agent
        $response->assertStatus(201);
        \Notification::assertSentTo($agent, \App\Features\TicketManagement\Notifications\ResponseNotification::class);
    }

    /**
     * Test #12: User cannot respond to other user's ticket
     * Verifies that User A cannot respond to User B's ticket
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_respond_to_other_user_ticket(): void
    {
        // Arrange
        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($userB)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'User B tries to respond to A ticket.',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #13: Agent cannot respond to other company's ticket
     * Verifies that Agent from Company A cannot respond to Company B ticket
     * Expected: 403 Forbidden
     */
    #[Test]
    public function agent_cannot_respond_to_other_company_ticket(): void
    {
        // Arrange
        $agentA = User::factory()->withRole('AGENT')->create();
        $agentB = User::factory()->withRole('AGENT')->create();
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $agentA->assignRole('AGENT', $companyA->id);
        $agentB->assignRole('AGENT', $companyB->id);

        $user = User::factory()->withRole('USER')->create();
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);
        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agentA)
            ->postJson("/api/tickets/{$ticketB->ticket_code}/responses", [
                'content' => 'Agent from A tries Company B ticket.',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #14: Cannot respond to closed ticket
     * Verifies that nobody can respond to a ticket with status=closed
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_respond_to_closed_ticket(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'closed',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Cannot respond to closed ticket.',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #15: Unauthenticated user cannot respond
     * Verifies that request without JWT token returns 401
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_respond(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act - No JWT authentication
        $response = $this->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
            'content' => 'No auth attempt.',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    // ==================== GROUP 4: last_response_author_type STATE MACHINE ====================

    /**
     * Test #16: User response to PENDING ticket changes status to OPEN ⭐ CRÍTICO
     * Verifies that PENDING→OPEN trigger fires when user responds
     * AND last_response_author_type updates to 'user'
     * Expected: 201 + status changed to open + last_response_author_type='user'
     */
    #[Test]
    public function user_response_to_pending_ticket_changes_status_to_open(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'User response to pending ticket.',
            ]);

        // Assert
        $response->assertStatus(201);

        $ticket->refresh();

        // Status should change from pending to open (TRIGGER)
        $this->assertEquals('open', $ticket->status);

        // last_response_author_type should update to 'user'
        $this->assertEquals('user', $ticket->last_response_author_type);
    }

    /**
     * Test #17: User response to PENDING updates last_response_author_type to 'user'
     * Verifies synchronization of status change and field update
     * Expected: 201 + status=open + last_response_author_type='user'
     */
    #[Test]
    public function user_response_to_pending_ticket_updates_last_response_author_type_to_user(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Sync test.',
            ]);

        // Assert
        $response->assertStatus(201);

        $ticket->refresh();

        // Field should be 'user' (synchronized with trigger)
        $this->assertEquals('user', $ticket->last_response_author_type);
    }

    /**
     * Test #18: Agent response to OPEN ticket sets last_response_author_type to 'agent'
     * Verifies that field updates without changing status (no trigger)
     * Expected: 201 + status still open + last_response_author_type='agent'
     */
    #[Test]
    public function agent_response_to_open_ticket_sets_last_response_author_type_to_agent(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'user',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Agent response to open.',
            ]);

        // Assert
        $response->assertStatus(201);

        $ticket->refresh();

        // Status should NOT change (still open)
        $this->assertEquals('open', $ticket->status);

        // last_response_author_type should update to 'agent'
        $this->assertEquals('agent', $ticket->last_response_author_type);
    }

    /**
     * Test #19: Multiple user responses keep last_response_author_type as 'user'
     * Verifies idempotence of field when same author responds multiple times
     * Expected: 201 + last_response_author_type='user' (no changes)
     */
    #[Test]
    public function multiple_user_responses_keep_last_response_author_type_as_user(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'last_response_author_type' => 'user',
        ]);

        // Act - First user response
        $response1 = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'User response 1.',
            ]);

        // Assert
        $response1->assertStatus(201);
        $ticket->refresh();
        $this->assertEquals('user', $ticket->last_response_author_type);

        // Act - Second user response
        $response2 = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'User response 2.',
            ]);

        // Assert
        $response2->assertStatus(201);
        $ticket->refresh();
        $this->assertEquals('user', $ticket->last_response_author_type);
    }

    /**
     * Test #20: Alternating responses update last_response_author_type correctly
     * Verifies: user→'user', agent→'agent', user→'user'
     * Expected: 201 + field updates on each response
     */
    #[Test]
    public function alternating_responses_update_last_response_author_type_correctly(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'none',
        ]);

        // User responds
        $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'User response.',
            ]);
        $ticket->refresh();
        $this->assertEquals('user', $ticket->last_response_author_type);

        // Agent responds
        $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Agent response.',
            ]);
        $ticket->refresh();
        $this->assertEquals('agent', $ticket->last_response_author_type);

        // User responds again
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'User response again.',
            ]);

        // Assert
        $response->assertStatus(201);
        $ticket->refresh();
        $this->assertEquals('user', $ticket->last_response_author_type);
    }

    /**
     * Test #21: PENDING→OPEN transition preserves owner_agent_id
     * Verifies that trigger only changes status and last_response_author_type
     * Expected: 201 + owner_agent_id unchanged + last_response_author_type='user'
     */
    #[Test]
    public function pending_to_open_transition_preserves_owner_agent_id(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
        ]);

        $originalOwnerId = $ticket->owner_agent_id;

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Preserve owner test.',
            ]);

        // Assert
        $response->assertStatus(201);

        $ticket->refresh();

        // owner_agent_id should NOT change
        $this->assertEquals($originalOwnerId, $ticket->owner_agent_id);
        $this->assertEquals($agent->id, $ticket->owner_agent_id);

        // Only status and last_response_author_type should change
        $this->assertEquals('open', $ticket->status);
        $this->assertEquals('user', $ticket->last_response_author_type);
    }

    /**
     * Test #22: User response to OPEN ticket does NOT change status
     * Verifies that no trigger fires for user responses to OPEN tickets
     * Expected: 201 + status still open + last_response_author_type='user'
     */
    #[Test]
    public function user_response_to_open_ticket_does_not_change_status(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'last_response_author_type' => 'none',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'No status change.',
            ]);

        // Assert
        $response->assertStatus(201);

        $ticket->refresh();

        // Status should remain 'open'
        $this->assertEquals('open', $ticket->status);

        // Only last_response_author_type updates
        $this->assertEquals('user', $ticket->last_response_author_type);
    }

    /**
     * Test #23: Agent response to PENDING ticket does NOT change status
     * Verifies that agent responses don't trigger state transitions
     * Expected: 201 + status still pending + last_response_author_type='agent'
     */
    #[Test]
    public function agent_response_to_pending_ticket_does_not_change_status(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'user',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Agent no status change.',
            ]);

        // Assert
        $response->assertStatus(201);

        $ticket->refresh();

        // Status should remain 'pending'
        $this->assertEquals('pending', $ticket->status);

        // Only last_response_author_type updates
        $this->assertEquals('agent', $ticket->last_response_author_type);
    }
}
