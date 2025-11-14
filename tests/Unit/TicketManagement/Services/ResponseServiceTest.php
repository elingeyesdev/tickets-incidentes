<?php

declare(strict_types=1);

namespace Tests\Unit\TicketManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Services\ResponseService;
use App\Features\UserManagement\Models\User;
use App\Features\TicketManagement\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Tests for ResponseService
 *
 * White-box testing of business logic in ResponseService.
 * Tests validate internal logic that Feature Tests assume exists.
 *
 * Coverage:
 * - Test 1: Determines author_type automatically based on user role
 * - Test 2: Validates auto-assignment trigger for first agent response
 *
 * Total: 2 tests
 */
class ResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResponseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ResponseService::class);
    }

    /**
     * Test #1: Determines author_type automatically
     *
     * Validates that ResponseService correctly determines author_type
     * based on the authenticated user's role.
     *
     * Business Logic:
     * - USER role → author_type = 'user'
     * - AGENT role → author_type = 'agent'
     * - This determination is critical for trigger logic
     * - Feature Tests assume this (line 61 in CreateResponseTest)
     *
     * Expected: Correct author_type mapping
     */
    #[Test]
    public function determines_author_type_automatically(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();
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

        // Mock JWT payload para USER
        request()->attributes->set('jwt_payload', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => [['code' => 'USER', 'company_id' => null]]
        ]);

        // Act - User creates response
        $userResponse = $this->service->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'response_content' => 'Response from user',
        ]);

        // Mock JWT payload para AGENT
        request()->attributes->set('jwt_payload', [
            'user_id' => $agent->id,
            'email' => $agent->email,
            'roles' => [['code' => 'AGENT', 'company_id' => $company->id]]
        ]);

        // Act - Agent creates response
        $agentResponse = $this->service->create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'response_content' => 'Response from agent',
        ]);

        // Assert - User should have author_type='user'
        $this->assertEquals('user', $userResponse->author_type->value);
        $this->assertDatabaseHas('ticketing.ticket_responses', [
            'id' => $userResponse->id,
            'author_id' => $user->id,
            'author_type' => 'user',
        ]);

        // Assert - Agent should have author_type='agent'
        $this->assertEquals('agent', $agentResponse->author_type->value);
        $this->assertDatabaseHas('ticketing.ticket_responses', [
            'id' => $agentResponse->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
        ]);
    }

    /**
     * Test #2: Validates auto-assignment trigger only for first agent response
     *
     * Validates that ResponseService triggers auto-assignment logic
     * only for the FIRST agent response to a ticket.
     *
     * Business Logic:
     * - Ticket starts with owner_agent_id = NULL
     * - Agent 1 responds → owner_agent_id = Agent 1, status = 'pending'
     * - Agent 2 responds → owner_agent_id SHOULD STAY Agent 1 (no change)
     * - Trigger only fires when: author_type='agent' AND owner_agent_id IS NULL
     *
     * Expected:
     * - First agent response assigns ticket to that agent
     * - Subsequent agent responses do NOT change ownership
     * - Status changes from 'open' to 'pending' on first agent response
     */
    #[Test]
    public function validates_auto_assignment_trigger_only_first_agent(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $agent1 = User::factory()->withRole('AGENT', $company->id)->create();
        $agent2 = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create ticket without owner (status=open, owner_agent_id=null)
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => null,
        ]);

        // Verify initial state
        $this->assertNull($ticket->owner_agent_id);
        $this->assertEquals(TicketStatus::OPEN, $ticket->status);
        $this->assertEquals('none', $ticket->last_response_author_type);

        // Mock JWT payload para AGENT 1
        request()->attributes->set('jwt_payload', [
            'user_id' => $agent1->id,
            'email' => $agent1->email,
            'roles' => [['code' => 'AGENT', 'company_id' => $company->id]]
        ]);

        // Act - Agent 1 responds (FIRST agent response)
        $response1 = $this->service->create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent1->id,
            'response_content' => 'Hello, I will help you with this issue.',
        ]);

        // Refresh ticket from database (trigger has executed)
        $ticket->refresh();

        // Assert - Trigger should assign ticket to Agent 1
        $this->assertEquals($agent1->id, $ticket->owner_agent_id, 'Ticket should be assigned to Agent 1');
        $this->assertEquals('pending', $ticket->status->value, 'Status should change to pending');
        $this->assertEquals('agent', $ticket->last_response_author_type, 'last_response_author_type should be agent');
        $this->assertNotNull($ticket->first_response_at, 'first_response_at should be set');

        // Assert - Database should reflect assignment
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'owner_agent_id' => $agent1->id,
            'status' => 'pending',
            'last_response_author_type' => 'agent',
        ]);

        // Mock JWT payload para AGENT 2
        request()->attributes->set('jwt_payload', [
            'user_id' => $agent2->id,
            'email' => $agent2->email,
            'roles' => [['code' => 'AGENT', 'company_id' => $company->id]]
        ]);

        // Act - Agent 2 responds (SECOND agent response, trigger should NOT fire)
        $response2 = $this->service->create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent2->id,
            'response_content' => 'I can also help if needed.',
        ]);

        // Refresh ticket from database
        $ticket->refresh();

        // Assert - Ownership should STILL be Agent 1 (no change)
        $this->assertEquals(
            $agent1->id,
            $ticket->owner_agent_id,
            'Ticket should STILL be assigned to Agent 1 (no change)'
        );

        // Assert - Status should STILL be pending (no change)
        $this->assertEquals('pending', $ticket->status->value, 'Status should remain pending');

        // Assert - last_response_author_type should be 'agent' (updated, but ownership unchanged)
        $this->assertEquals('agent', $ticket->last_response_author_type);

        // Assert - Database should still show Agent 1 as owner
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'owner_agent_id' => $agent1->id,
            'status' => 'pending',
        ]);

        // Assert - Both responses should exist in database
        $this->assertDatabaseHas('ticketing.ticket_responses', [
            'id' => $response1->id,
            'ticket_id' => $ticket->id,
            'author_id' => $agent1->id,
            'author_type' => 'agent',
        ]);

        $this->assertDatabaseHas('ticketing.ticket_responses', [
            'id' => $response2->id,
            'ticket_id' => $ticket->id,
            'author_id' => $agent2->id,
            'author_type' => 'agent',
        ]);
    }
}
