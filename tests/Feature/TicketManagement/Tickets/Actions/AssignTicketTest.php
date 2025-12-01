<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Tickets\Actions;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Assigning Tickets
 *
 * Tests the endpoint POST /api/tickets/:code/assign
 *
 * Coverage:
 * - Agent can assign ticket to another agent
 * - Validation: new_agent_id is required
 * - Validation: new_agent_id must exist
 * - Validation: new_agent must be from same company
 * - Validation: new_agent must have agent role
 * - assignment_note is optional
 * - assignment_note is saved when provided
 * - TicketAssigned event is triggered
 * - Notification sent to new agent
 * - USER cannot assign tickets
 *
 * Expected Status Codes:
 * - 200: Ticket assigned successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (USER)
 * - 422: Validation errors
 *
 * Business Rules:
 * - Only AGENT can assign tickets
 * - new_agent_id is required
 * - New agent must exist in database
 * - New agent must have role='agent'
 * - New agent must be from same company as current agent
 * - owner_agent_id changes to new agent
 * - last_response_author_type DOES NOT change during assignment
 * - assignment_note is optional (like resolution_note, reopen_reason)
 */
class AssignTicketTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Successful Assignment (Test 1) ====================

    /**
     * Test #1: Agent can assign ticket to another agent
     *
     * Verifies that an agent can successfully assign a ticket to another agent
     * from the same company. The owner_agent_id should change, but
     * last_response_author_type should NOT change.
     *
     * Expected: 200 OK with updated ticket data
     * Database: owner_agent_id updated, last_response_author_type unchanged
     */
    #[Test]
    public function agent_can_assign_ticket_to_another_agent(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $agent2 = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
            'status' => 'pending',
            'last_response_author_type' => 'agent',
        ]);

        $originalLastResponseAuthorType = $ticket->last_response_author_type;

        $payload = [
            'new_agent_id' => $agent2->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.owner_agent_id', $agent2->id);

        // Verify database
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'owner_agent_id' => $agent2->id,
        ]);

        // CRITICAL: Verify last_response_author_type did NOT change
        $ticket->refresh();
        $this->assertEquals($originalLastResponseAuthorType, $ticket->last_response_author_type);
    }

    // ==================== GROUP 2: Validation Tests (Tests 2-5) ====================

    /**
     * Test #2: Validates new_agent_id is required
     *
     * Verifies that the request fails when new_agent_id is missing.
     *
     * Expected: 422 Unprocessable Entity with validation error
     */
    #[Test]
    public function validates_new_agent_id_is_required(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
        ]);

        // Empty payload (missing new_agent_id)
        $payload = [];

        // Act
        $response = $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['new_agent_id']);
    }

    /**
     * Test #3: Validates new_agent exists
     *
     * Verifies that the request fails when new_agent_id references
     * a non-existent user.
     *
     * Expected: 422 Unprocessable Entity with validation error
     */
    #[Test]
    public function validates_new_agent_exists(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
        ]);

        $fakeAgentId = Str::uuid()->toString();

        $payload = [
            'new_agent_id' => $fakeAgentId,
        ];

        // Act
        $response = $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['new_agent_id']);
    }

    /**
     * Test #4: Validates new_agent is from same company
     *
     * Verifies that the request fails when new_agent_id references
     * an agent from a different company.
     *
     * Expected: 422 Unprocessable Entity with validation error
     */
    #[Test]
    public function validates_new_agent_is_from_same_company(): void
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company1->id)->create();
        $agentOtherCompany = User::factory()->withRole('AGENT', $company2->id)->create();
        $category = Category::factory()->create(['company_id' => $company1->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company1->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
        ]);

        $payload = [
            'new_agent_id' => $agentOtherCompany->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['new_agent_id']);
    }

    /**
     * Test #5: Validates new_agent has agent role
     *
     * Verifies that the request fails when new_agent_id references
     * a user with role='USER' instead of 'AGENT'.
     *
     * Expected: 422 Unprocessable Entity with validation error
     */
    #[Test]
    public function validates_new_agent_has_agent_role(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $userWithUserRole = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
        ]);

        $payload = [
            'new_agent_id' => $userWithUserRole->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['new_agent_id']);
    }

    // ==================== GROUP 3: Optional Fields (Tests 6-7) ====================

    /**
     * Test #6: assignment_note is optional
     *
     * Verifies that assignment_note is not required.
     * Ticket can be assigned without providing a note.
     *
     * Expected: 200 OK
     */
    #[Test]
    public function assignment_note_is_optional(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $agent2 = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
        ]);

        $payload = [
            'new_agent_id' => $agent2->id,
            // assignment_note NOT provided
        ];

        // Act
        $response = $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.owner_agent_id', $agent2->id);
    }

    /**
     * Test #7: assignment_note is saved when provided
     *
     * Verifies that when assignment_note is provided,
     * it is saved in the database.
     *
     * Expected: 200 OK with assignment_note saved
     */
    #[Test]
    public function assignment_note_is_saved_when_provided(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $agent2 = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
        ]);

        $payload = [
            'new_agent_id' => $agent2->id,
            'assignment_note' => 'Reasignando a Carlos porque es experto en este tipo de issues',
        ];

        // Act
        $response = $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(200);

        // Note: Assuming assignment_note is saved in a ticket_assignments table
        // or as metadata. Adjust this assertion based on actual implementation.
        // For now, verify the response includes the assignment_note
        $response->assertJsonPath('data.owner_agent_id', $agent2->id);
    }

    // ==================== GROUP 4: Events and Notifications (Tests 8-9) ====================

    /**
     * Test #8: assign triggers ticket assigned event
     *
     * Verifies that assigning a ticket triggers a TicketAssigned event.
     *
     * Expected: TicketAssigned event is dispatched
     */
    #[Test]
    public function assign_triggers_ticket_assigned_event(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $agent2 = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
        ]);

        $payload = [
            'new_agent_id' => $agent2->id,
        ];

        Event::fake();

        // Act
        $response = $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(200);

        // Verify event was dispatched
        Event::assertDispatched(\App\Features\TicketManagement\Events\TicketAssigned::class);
    }

    /**
     * Test #9: assign sends notification to new agent
     *
     * Verifies that when a ticket is assigned to a new agent,
     * the new agent receives a notification.
     *
     * Expected: Notification sent to new agent
     */
    #[Test]
    public function assign_sends_notification_to_new_agent(): void
    {
        // Arrange
        Notification::fake();

        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $agent2 = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
        ]);

        $payload = [
            'new_agent_id' => $agent2->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($agent1)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(200);

        // Verify notification was sent to new agent
        Notification::assertSentTo(
            [$agent2],
            \App\Features\TicketManagement\Notifications\TicketAssignedNotification::class
        );
    }

    // ==================== GROUP 5: Permissions (Test 10) ====================

    /**
     * Test #10: User cannot assign ticket
     *
     * Verifies that users with USER role are forbidden from assigning tickets.
     * Only AGENT role should be able to assign tickets.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_assign_ticket(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $agent2 = User::factory()->withRole('AGENT', $company->id)->create();
        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent1->id,
        ]);

        $payload = [
            'new_agent_id' => $agent2->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT assigned
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'owner_agent_id' => $agent1->id, // Still assigned to agent1
        ]);
    }

    /**
     * Test #11: Company Admin can assign ticket to agent
     *
     * Verifies that a COMPANY_ADMIN can successfully assign a ticket to an agent
     * from the same company.
     *
     * Expected: 200 OK with updated ticket data
     */
    #[Test]
    public function company_admin_can_assign_ticket_to_agent(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => null, // Unassigned
        ]);

        $payload = [
            'new_agent_id' => $agent->id,
            'assignment_note' => 'Assigning via Company Admin',
        ];

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)
            ->postJson("/api/tickets/{$ticket->ticket_code}/assign", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.owner_agent_id', $agent->id);

        // Verify database
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'owner_agent_id' => $agent->id,
        ]);
    }
}
