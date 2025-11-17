<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Integration;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Integration Tests for Complete Ticket Flows
 *
 * Tests complex, multi-step ticket workflows involving:
 * - State transitions (open → pending → resolved → closed)
 * - Auto-assignment triggers
 * - last_response_author_type field updates
 * - Multi-user interactions
 *
 * Coverage:
 * - Complete lifecycle from creation to closure
 * - Auto-assignment on first agent response
 * - Status changes triggered by user responses
 * - Field persistence across transitions
 */
class CompleteTicketFlowTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test #1: Complete Ticket Lifecycle from Creation to Resolution
     *
     * Verifies the complete flow:
     * 1. User A creates ticket → status=open, last_response_author_type='none'
     * 2. Agent responds (auto-assigns) → status=pending, owner_agent_id=agent, last_response_author_type='agent', first_response_at set
     * 3. User responds again → status=open (TRIGGER), last_response_author_type='user' (agent stays assigned)
     * 4. Agent resolves → status=resolved
     * 5. Ticket should be closeable
     * 6. Verify complete lifecycle and all transitions correct
     */
    #[Test]
    public function test_complete_ticket_lifecycle_from_creation_to_resolution(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $userA = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        // ==================== Act & Assert - Step 1: User A creates ticket ====================
        $this->authenticateWithJWT($userA);
        $createResponse = $this->postJson('/api/tickets', [
            'title' => 'Complete Lifecycle Test Ticket',
            'description' => 'Testing full lifecycle from creation to resolution',
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJsonPath('data.status', 'open');
        $createResponse->assertJsonPath('data.last_response_author_type', 'none');
        $createResponse->assertJsonPath('data.owner_agent_id', null);

        $ticketCode = $createResponse->json('data.ticket_code');
        $ticket = Ticket::where('ticket_code', $ticketCode)->firstOrFail();

        // Verify initial state in DB
        $this->assertEquals('open', $ticket->status->value);
        $this->assertEquals('none', $ticket->last_response_author_type);
        $this->assertNull($ticket->owner_agent_id);
        $this->assertNull($ticket->first_response_at);
        $this->assertNull($ticket->resolved_at);

        // ==================== Act & Assert - Step 2: Agent responds (auto-assigns) ====================
        $this->authenticateWithJWT($agent);
        $agentResponseTime = Carbon::now();
        $agentResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'Agent first response - should trigger auto-assignment',
        ]);

        $agentResponse->assertStatus(201);

        // Verify ticket state after agent response
        $ticket->refresh();
        $this->assertEquals('pending', $ticket->status->value, 'Status should change to pending after first agent response');
        $this->assertEquals($agent->id, $ticket->owner_agent_id, 'Agent should be auto-assigned');
        $this->assertEquals('agent', $ticket->last_response_author_type, 'last_response_author_type should be agent');
        $this->assertNotNull($ticket->first_response_at, 'first_response_at should be set');
        $this->assertTrue($ticket->first_response_at->greaterThanOrEqualTo($agentResponseTime->subSeconds(2)));
        $this->assertNull($ticket->resolved_at);

        // Verify API response matches DB state
        $getResponse = $this->getJson("/api/tickets/{$ticketCode}");
        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.status', 'pending');
        $getResponse->assertJsonPath('data.owner_agent_id', $agent->id);
        $getResponse->assertJsonPath('data.last_response_author_type', 'agent');

        // ==================== Act & Assert - Step 3: User responds again (should reopen) ====================
        $this->authenticateWithJWT($userA);
        $userResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'User response - should trigger status change to open',
        ]);

        $userResponse->assertStatus(201);

        // Verify ticket reopened with TRIGGER
        $ticket->refresh();
        $this->assertEquals('open', $ticket->status->value, 'Status should change back to open after user response');
        $this->assertEquals($agent->id, $ticket->owner_agent_id, 'Agent assignment should be preserved');
        $this->assertEquals('user', $ticket->last_response_author_type, 'last_response_author_type should be user');
        $this->assertNotNull($ticket->first_response_at, 'first_response_at should remain set');

        // ==================== Act & Assert - Step 4: Agent resolves ticket ====================
        $this->authenticateWithJWT($agent);
        $resolveTime = Carbon::now();
        $resolveResponse = $this->postJson("/api/tickets/{$ticketCode}/resolve");

        $resolveResponse->assertStatus(200);

        // Verify ticket resolved
        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status->value, 'Status should be resolved');
        $this->assertEquals($agent->id, $ticket->owner_agent_id, 'Agent assignment should be preserved');
        $this->assertNotNull($ticket->resolved_at, 'resolved_at should be set');
        $this->assertTrue($ticket->resolved_at->greaterThanOrEqualTo($resolveTime->subSeconds(2)));
        $this->assertNull($ticket->closed_at, 'closed_at should still be null');

        // ==================== Act & Assert - Step 5: Verify ticket closeable ====================
        $closeResponse = $this->postJson("/api/tickets/{$ticketCode}/close");

        $closeResponse->assertStatus(200);

        // Verify ticket closed
        $ticket->refresh();
        $this->assertEquals('closed', $ticket->status->value, 'Status should be closed');
        $this->assertNotNull($ticket->closed_at, 'closed_at should be set');

        // ==================== Assert - Final: Verify complete lifecycle ====================
        $this->assertNotNull($ticket->first_response_at, 'first_response_at persisted through lifecycle');
        $this->assertNotNull($ticket->resolved_at, 'resolved_at persisted through lifecycle');
        $this->assertNotNull($ticket->closed_at, 'closed_at set on close');
        $this->assertEquals($agent->id, $ticket->owner_agent_id, 'Agent assignment persisted through lifecycle');
        $this->assertEquals($userA->id, $ticket->created_by_user_id, 'Creator preserved through lifecycle');
    }

    /**
     * Test #2: Multiple Agents Responding Preserves First Assignment
     *
     * Verifies that:
     * 1. Agent A responds to unassigned ticket → owner_agent_id=A, last_response_author_type='agent'
     * 2. Agent B responds → owner_agent_id STAYS A (not changed), last_response_author_type='agent'
     * 3. First agent assignment preserved, last_response_author updates
     */
    #[Test]
    public function test_multiple_agents_responding_preserves_first_assignment(): void
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

        // Create ticket
        $this->authenticateWithJWT($user);
        $createResponse = $this->postJson('/api/tickets', [
            'title' => 'Multiple Agents Assignment Test',
            'description' => 'Testing that first agent assignment is preserved',
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $createResponse->assertStatus(201);
        $ticketCode = $createResponse->json('data.ticket_code');
        $ticket = Ticket::where('ticket_code', $ticketCode)->firstOrFail();

        // Verify initial unassigned state
        $this->assertNull($ticket->owner_agent_id);
        $this->assertEquals('none', $ticket->last_response_author_type);

        // ==================== Act & Assert - Step 1: Agent A responds first ====================
        $this->authenticateWithJWT($agentA);
        $agentAResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'Agent A first response - should auto-assign to Agent A',
        ]);

        $agentAResponse->assertStatus(201);

        // Verify Agent A assigned
        $ticket->refresh();
        $this->assertEquals($agentA->id, $ticket->owner_agent_id, 'Agent A should be auto-assigned');
        $this->assertEquals('agent', $ticket->last_response_author_type);
        $this->assertEquals('pending', $ticket->status->value);

        // ==================== Act & Assert - Step 2: Agent B responds ====================
        $this->authenticateWithJWT($agentB);
        $agentBResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'Agent B second response - should NOT change assignment',
        ]);

        $agentBResponse->assertStatus(201);

        // Verify Agent A assignment preserved
        $ticket->refresh();
        $this->assertEquals($agentA->id, $ticket->owner_agent_id, 'Owner should STAY Agent A (not changed to Agent B)');
        $this->assertEquals('agent', $ticket->last_response_author_type, 'last_response_author_type should still be agent');
        $this->assertEquals('pending', $ticket->status->value, 'Status should remain pending');

        // ==================== Assert - Final: Verify responses exist from both agents ====================
        $getResponse = $this->getJson("/api/tickets/{$ticketCode}");
        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.owner_agent_id', $agentA->id);
        $getResponse->assertJsonPath('data.last_response_author_type', 'agent');

        // Verify both responses exist in DB
        $this->assertDatabaseHas('ticketing.ticket_responses', [
            'ticket_id' => $ticket->id,
            'author_id' => $agentA->id,
            'author_type' => 'agent',
        ]);
        $this->assertDatabaseHas('ticketing.ticket_responses', [
            'ticket_id' => $ticket->id,
            'author_id' => $agentB->id,
            'author_type' => 'agent',
        ]);
    }

    /**
     * Test #3: Ticket with Attachments Through Flow
     *
     * Verifies that:
     * 1. User creates ticket with attachment
     * 2. Agent responds (auto-assigns)
     * 3. Agent uploads attachment
     * 4. Attachments persist through status changes
     * 5. attachment_count increases correctly
     */
    #[Test]
    public function test_ticket_with_attachments_through_flow(): void
    {
        // ==================== Arrange ====================
        Storage::fake('local');

        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        $userAttachment = UploadedFile::fake()->image('user-screenshot.png', 800, 600);

        // ==================== Act & Assert - Step 1: User creates ticket ====================
        $this->authenticateWithJWT($user);
        $createResponse = $this->postJson('/api/tickets', [
            'title' => 'Ticket with Attachments Flow Test',
            'description' => 'Testing attachment persistence through flow',
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $createResponse->assertStatus(201);
        $ticketCode = $createResponse->json('data.ticket_code');
        $ticket = Ticket::where('ticket_code', $ticketCode)->firstOrFail();

        // Load the count of attachments
        $ticket->loadCount(['attachments']);

        // Verify no attachments initially
        $this->assertEquals(0, $ticket->attachments_count, 'Should have 0 attachments initially');

        // ==================== Act & Assert - Step 2: Agent responds (auto-assigns) ====================
        $this->authenticateWithJWT($agent);
        $agentResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'Agent response - checking attachment persistence',
        ]);

        $agentResponse->assertStatus(201);

        // Verify ticket state after agent response
        $ticket->refresh();
        $this->assertEquals('pending', $ticket->status->value);
        $this->assertEquals($agent->id, $ticket->owner_agent_id);
        $ticket->loadCount(['attachments']);
        $this->assertEquals(0, $ticket->attachments_count, 'No attachments yet');

        // ==================== Act & Assert - Step 3: Agent uploads attachment ====================
        $agentAttachment = UploadedFile::fake()->image('agent-diagnostic.png', 1024, 768);

        $uploadResponse = $this->postJson("/api/tickets/{$ticketCode}/attachments", [
            'file' => $agentAttachment,
        ]);

        $uploadResponse->assertStatus(200);

        // Verify attachment count increased
        $ticket->refresh();
        $ticket->loadCount(['attachments']);
        $this->assertEquals(1, $ticket->attachments_count, 'Should have 1 attachment after agent upload');
        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by_user_id' => $agent->id,
        ]);

        // ==================== Act & Assert - Step 4: User responds (status change to open) ====================
        $this->authenticateWithJWT($user);
        $userResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'User response - checking attachment persistence through status change',
        ]);

        $userResponse->assertStatus(201);

        // Verify attachments persist through status change back to open
        $ticket->refresh();
        $this->assertEquals('open', $ticket->status->value);
        $ticket->loadCount(['attachments']);
        $this->assertEquals(1, $ticket->attachments_count, 'Attachments should persist through status change to open');

        // ==================== Act & Assert - Step 5: Resolve ticket ====================
        $this->authenticateWithJWT($agent);
        $resolveResponse = $this->postJson("/api/tickets/{$ticketCode}/resolve");

        $resolveResponse->assertStatus(200);

        // Verify attachments persist through resolution
        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status->value);
        $ticket->loadCount(['attachments']);
        $this->assertEquals(1, $ticket->attachments_count, 'Attachments should persist through resolution');

        // ==================== Assert - Final: Verify all attachments accessible ====================
        $getResponse = $this->getJson("/api/tickets/{$ticketCode}");
        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.attachments_count', 1);

        // Verify agent attachment exists in storage
        $agentAttachmentRecord = $ticket->attachments()->where('uploaded_by_user_id', $agent->id)->first();

        $this->assertNotNull($agentAttachmentRecord, 'Agent attachment record should exist');
        Storage::disk('local')->assertExists($agentAttachmentRecord->file_path);
    }
}
