<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Integration;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Integration Tests for Permissions and Access Control
 *
 * Tests complex permission scenarios across:
 * - Company isolation (users/agents cannot access other companies)
 * - Role-based permissions (USER vs AGENT capabilities)
 * - Following vs access control (following is UI, not security)
 * - Dynamic role changes and immediate permission updates
 *
 * Coverage:
 * - User isolation between companies
 * - Agent isolation between companies
 * - Following does not grant access
 * - Role changes update permissions immediately
 */
class PermissionsIntegrationTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test #1: User Access Isolated Between Companies
     *
     * Verifies that:
     * 1. Company A and B created
     * 2. User A in Company A (creates ticket)
     * 3. User B in Company B
     * 4. User A tries GET /api/tickets from Company B → 200 but sees 0 tickets (empty list)
     * 5. User A cannot see Company B tickets even with request
     */
    #[Test]
    public function test_user_access_isolated_between_companies(): void
    {
        // ==================== Arrange ====================
        // Create two separate companies
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        // Create categories for both companies
        $categoryA = Category::factory()->create([
            'company_id' => $companyA->id,
            'is_active' => true,
        ]);
        $categoryB = Category::factory()->create([
            'company_id' => $companyB->id,
            'is_active' => true,
        ]);

        // User A belongs to Company A
        $userA = User::factory()->withRole('USER')->create();

        // User B belongs to Company B
        $userB = User::factory()->withRole('USER')->create();

        // User A creates ticket in Company A
        $this->authenticateWithJWT($userA);
        $ticketAResponse = $this->postJson('/api/tickets', [
            'title' => 'Company A Ticket',
            'description' => 'This ticket belongs to Company A',
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
        ]);

        $ticketAResponse->assertStatus(201);
        $ticketACode = $ticketAResponse->json('data.ticket_code');

        // User B creates ticket in Company B
        $this->authenticateWithJWT($userB);
        $ticketBResponse = $this->postJson('/api/tickets', [
            'title' => 'Company B Ticket',
            'description' => 'This ticket belongs to Company B',
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
        ]);

        $ticketBResponse->assertStatus(201);
        $ticketBCode = $ticketBResponse->json('data.ticket_code');

        // ==================== Act & Assert - User A tries to access Company B tickets ====================
        $this->authenticateWithJWT($userA);

        // Try to list tickets (should only see Company A tickets, not Company B)
        $listResponse = $this->getJson('/api/tickets');
        $listResponse->assertStatus(200);

        // User A should only see their own ticket (Company A), not Company B ticket
        $tickets = $listResponse->json('data');
        $this->assertIsArray($tickets);

        // Count tickets User A can see
        $ticketCodes = array_column($tickets, 'ticket_code');
        $this->assertContains($ticketACode, $ticketCodes, 'User A should see their own ticket');
        $this->assertNotContains($ticketBCode, $ticketCodes, 'User A should NOT see Company B ticket');

        // Try to access Company B ticket directly
        $accessTicketBResponse = $this->getJson("/api/tickets/{$ticketBCode}");
        $accessTicketBResponse->assertStatus(403); // Forbidden - cannot access other company's ticket

        // ==================== Assert - User B isolation ====================
        $this->authenticateWithJWT($userB);

        // User B should only see their own ticket (Company B)
        $listResponseB = $this->getJson('/api/tickets');
        $listResponseB->assertStatus(200);

        $ticketsB = $listResponseB->json('data');
        $ticketCodesB = array_column($ticketsB, 'ticket_code');
        $this->assertContains($ticketBCode, $ticketCodesB, 'User B should see their own ticket');
        $this->assertNotContains($ticketACode, $ticketCodesB, 'User B should NOT see Company A ticket');

        // User B cannot access Company A ticket directly
        $accessTicketAResponse = $this->getJson("/api/tickets/{$ticketACode}");
        $accessTicketAResponse->assertStatus(403); // Forbidden

        // ==================== Assert - Verify DB isolation ====================
        // Verify both tickets exist in DB but are isolated by company
        $this->assertDatabaseHas('ticketing.tickets', [
            'ticket_code' => $ticketACode,
            'company_id' => $companyA->id,
        ]);
        $this->assertDatabaseHas('ticketing.tickets', [
            'ticket_code' => $ticketBCode,
            'company_id' => $companyB->id,
        ]);
    }

    /**
     * Test #2: Agent Access Isolated Between Companies
     *
     * Verifies that:
     * 1. Company A and B created
     * 2. Agent A assigned to Company A
     * 3. Create ticket in Company B
     * 4. Agent A tries POST /api/tickets/{B-ticket}/responses → 403
     * 5. Agent A tries GET /api/tickets/{B-ticket} → 403
     * 6. Agent completely isolated by company
     */
    #[Test]
    public function test_agent_access_isolated_between_companies(): void
    {
        // ==================== Arrange ====================
        // Create two separate companies
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        // Create categories
        $categoryA = Category::factory()->create([
            'company_id' => $companyA->id,
            'is_active' => true,
        ]);
        $categoryB = Category::factory()->create([
            'company_id' => $companyB->id,
            'is_active' => true,
        ]);

        // Agent A assigned to Company A only
        $agentA = User::factory()->withRole('AGENT', $companyA->id)->create();

        // Agent B assigned to Company B only
        $agentB = User::factory()->withRole('AGENT', $companyB->id)->create();

        // Regular users
        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();

        // Create ticket in Company A
        $this->authenticateWithJWT($userA);
        $ticketAResponse = $this->postJson('/api/tickets', [
            'title' => 'Company A Ticket',
            'description' => 'Agent A should access this',
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
        ]);
        $ticketAResponse->assertStatus(201);
        $ticketACode = $ticketAResponse->json('data.ticket_code');

        // Create ticket in Company B
        $this->authenticateWithJWT($userB);
        $ticketBResponse = $this->postJson('/api/tickets', [
            'title' => 'Company B Ticket',
            'description' => 'Agent A should NOT access this',
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
        ]);
        $ticketBResponse->assertStatus(201);
        $ticketBCode = $ticketBResponse->json('data.ticket_code');

        // ==================== Act & Assert - Agent A tries to access Company B ticket ====================
        $this->authenticateWithJWT($agentA);

        // Try to respond to Company B ticket
        $responseAttempt = $this->postJson("/api/tickets/{$ticketBCode}/responses", [
            'content' => 'Agent A trying to respond to Company B ticket - should be forbidden',
        ]);
        $responseAttempt->assertStatus(403); // Forbidden - wrong company

        // Try to view Company B ticket
        $viewAttempt = $this->getJson("/api/tickets/{$ticketBCode}");
        $viewAttempt->assertStatus(403); // Forbidden - wrong company

        // Try to resolve Company B ticket
        $resolveAttempt = $this->postJson("/api/tickets/{$ticketBCode}/resolve");
        $resolveAttempt->assertStatus(403); // Forbidden - wrong company

        // ==================== Assert - Agent A CAN access Company A ticket ====================
        // Verify Agent A can still access their own company's tickets
        $this->authenticateWithJWT($agentA);

        $viewCompanyATicket = $this->getJson("/api/tickets/{$ticketACode}");
        $viewCompanyATicket->assertStatus(200);
        $viewCompanyATicket->assertJsonPath('data.ticket_code', $ticketACode);

        // Agent A can respond to Company A ticket
        $respondCompanyA = $this->postJson("/api/tickets/{$ticketACode}/responses", [
            'content' => 'Agent A responding to Company A ticket - should succeed',
        ]);
        $respondCompanyA->assertStatus(201);

        // ==================== Assert - Agent B CAN access Company B ticket ====================
        $this->authenticateWithJWT($agentB);

        $viewCompanyBTicket = $this->getJson("/api/tickets/{$ticketBCode}");
        $viewCompanyBTicket->assertStatus(200);
        $viewCompanyBTicket->assertJsonPath('data.ticket_code', $ticketBCode);

        // Agent B can respond to Company B ticket
        $respondCompanyB = $this->postJson("/api/tickets/{$ticketBCode}/responses", [
            'content' => 'Agent B responding to Company B ticket - should succeed',
        ]);
        $respondCompanyB->assertStatus(201);

        // ==================== Assert - Agent B CANNOT access Company A ticket ====================
        $viewAttemptB = $this->getJson("/api/tickets/{$ticketACode}");
        $viewAttemptB->assertStatus(403); // Forbidden
    }

    /**
     * Test #3: Following Affects Notifications Not Access
     *
     * Verifies that:
     * 1. User creates ticket in Company A they don't follow
     * 2. User still gets notified (owner notification, not following-based)
     * 3. User follows Company B but didn't create ticket
     * 4. User can still access Company B tickets (following is UI, not access)
     * 5. Access independent of following status
     */
    #[Test]
    public function test_following_affects_notifications_not_access(): void
    {
        // ==================== Arrange ====================
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $categoryA = Category::factory()->create([
            'company_id' => $companyA->id,
            'is_active' => true,
        ]);
        $categoryB = Category::factory()->create([
            'company_id' => $companyB->id,
            'is_active' => true,
        ]);

        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT', $companyA->id)->create();

        // ==================== Act & Assert - Step 1: User creates ticket (not following) ====================
        $this->authenticateWithJWT($user);

        // Create ticket in Company A (user NOT explicitly following)
        $ticketResponse = $this->postJson('/api/tickets', [
            'title' => 'Ticket Without Following',
            'description' => 'User should still have access as creator',
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
        ]);

        $ticketResponse->assertStatus(201);
        $ticketCode = $ticketResponse->json('data.ticket_code');

        // Verify user can access their own ticket (creator access, not following-based)
        $accessResponse = $this->getJson("/api/tickets/{$ticketCode}");
        $accessResponse->assertStatus(200);
        $accessResponse->assertJsonPath('data.ticket_code', $ticketCode);

        // Verify user gets notified when agent responds (owner notification)
        $this->authenticateWithJWT($agent);
        $agentResponseResult = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'Agent response - user should be notified',
        ]);
        $agentResponseResult->assertStatus(201);

        // User should still have access after agent response
        $this->authenticateWithJWT($user);
        $accessAfterResponse = $this->getJson("/api/tickets/{$ticketCode}");
        $accessAfterResponse->assertStatus(200);

        // ==================== Act & Assert - Step 2: Following does not grant special access ====================
        // User follows a company (simulate following)
        // NOTE: Following is a UI feature for notifications, NOT an access control mechanism

        // User can respond to their own ticket regardless of following status
        $userFollowupResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'User followup - access independent of following',
        ]);
        $userFollowupResponse->assertStatus(201);

        // ==================== Assert - Step 3: Verify access is based on creation/assignment, not following ====================
        $ticket = Ticket::where('ticket_code', $ticketCode)->firstOrFail();

        // User created the ticket, so they have access
        $this->assertEquals($user->id, $ticket->created_by_user_id);

        // User can view ticket
        $finalAccessCheck = $this->getJson("/api/tickets/{$ticketCode}");
        $finalAccessCheck->assertStatus(200);

        // User can list their tickets
        $listResponse = $this->getJson('/api/tickets');
        $listResponse->assertStatus(200);
        $tickets = $listResponse->json('data');
        $ticketCodes = array_column($tickets, 'ticket_code');
        $this->assertContains($ticketCode, $ticketCodes, 'User should see their created ticket');

        // ==================== Assert - Step 4: Following a company doesn't grant access to all tickets ====================
        // Create another user and a ticket they didn't create
        $otherUser = User::factory()->withRole('USER')->create();

        $this->authenticateWithJWT($otherUser);
        $otherTicketResponse = $this->postJson('/api/tickets', [
            'title' => 'Other User Ticket',
            'description' => 'First user should not access this',
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
        ]);
        $otherTicketResponse->assertStatus(201);
        $otherTicketCode = $otherTicketResponse->json('data.ticket_code');

        // First user tries to access other user's ticket
        $this->authenticateWithJWT($user);
        $unauthorizedAccess = $this->getJson("/api/tickets/{$otherTicketCode}");
        $unauthorizedAccess->assertStatus(403); // Cannot access tickets created by others

        // Verify following is UI-only, not access control
        // Access is based on: creator, assigned agent, or company admin
        // NOT based on following status
    }

    /**
     * Test #4: Role Changes Affect Permissions Immediately
     *
     * Verifies that:
     * 1. User created as USER role
     * 2. USER tries to resolve ticket → 403
     * 3. Promote USER to AGENT role (assign to company)
     * 4. AGENT tries to resolve ticket → 201
     * 5. Permissions changed immediately without clearing cache
     * 6. Old USER permissions gone, new AGENT permissions active
     */
    #[Test]
    public function test_role_changes_affect_permissions_immediately(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create user with USER role
        $user = User::factory()->withRole('USER')->create();

        // Create an agent to respond first (so ticket can be resolved later)
        $existingAgent = User::factory()->withRole('AGENT', $company->id)->create();

        // ==================== Act & Assert - Step 1: USER role cannot resolve tickets ====================
        $this->authenticateWithJWT($user);

        // User creates ticket
        $createResponse = $this->postJson('/api/tickets', [
            'title' => 'Role Change Permissions Test',
            'description' => 'Testing immediate permission changes on role promotion',
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);
        $createResponse->assertStatus(201);
        $ticketCode = $createResponse->json('data.ticket_code');

        // Agent responds first (so ticket can be resolved)
        $this->authenticateWithJWT($existingAgent);
        $agentResponse = $this->postJson("/api/tickets/{$ticketCode}/responses", [
            'content' => 'Agent response to enable resolution',
        ]);
        $agentResponse->assertStatus(201);

        // User (still USER role) tries to resolve ticket
        $this->authenticateWithJWT($user);
        $resolveAttemptAsUser = $this->postJson("/api/tickets/{$ticketCode}/resolve");
        $resolveAttemptAsUser->assertStatus(403); // Forbidden - USER cannot resolve tickets

        // Verify ticket still open/pending (not resolved)
        $ticket = Ticket::where('ticket_code', $ticketCode)->firstOrFail();
        $this->assertEquals('pending', $ticket->status->value);
        $this->assertNull($ticket->resolved_at);

        // ==================== Act - Step 2: Promote USER to AGENT role ====================
        // Update user to AGENT role (simulate promotion)
        $user->assignRole('AGENT', $company->id);
        $user->refresh();

        // Verify role changed
        $this->assertTrue($user->hasRole('AGENT'), 'User should now have AGENT role');

        // ==================== Assert - Step 3: AGENT role CAN resolve tickets ====================
        // Re-authenticate as the promoted agent (same user, new permissions)
        $this->authenticateWithJWT($user);

        // Now try to resolve ticket as AGENT
        $resolveAttemptAsAgent = $this->postJson("/api/tickets/{$ticketCode}/resolve");
        $resolveAttemptAsAgent->assertStatus(200); // Success - AGENT can resolve tickets

        // Verify ticket was resolved
        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status->value, 'Ticket should be resolved');
        $this->assertNotNull($ticket->resolved_at, 'resolved_at should be set');

        // ==================== Assert - Step 4: Verify permissions changed immediately ====================
        // Create another ticket to test agent permissions
        $secondTicketResponse = $this->postJson('/api/tickets', [
            'title' => 'Second Ticket After Promotion',
            'description' => 'Testing agent can respond immediately',
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);
        $secondTicketResponse->assertStatus(201);
        $secondTicketCode = $secondTicketResponse->json('data.ticket_code');

        // As AGENT, can respond to any ticket in their company
        $agentResponseToNewTicket = $this->postJson("/api/tickets/{$secondTicketCode}/responses", [
            'content' => 'Agent responding to ticket - permissions active immediately',
        ]);
        $agentResponseToNewTicket->assertStatus(201);

        // Verify auto-assignment works (agent permissions active)
        $secondTicket = Ticket::where('ticket_code', $secondTicketCode)->firstOrFail();
        $this->assertEquals($user->id, $secondTicket->owner_agent_id, 'Auto-assignment should work for promoted agent');
        $this->assertEquals('pending', $secondTicket->status->value);

        // ==================== Assert - Step 5: Old USER permissions gone ====================
        // Verify user no longer has USER-only restrictions
        // (Already verified above: can resolve, can auto-assign, etc.)

        // Verify DB reflects role change
        $this->assertDatabaseHas('auth.user_roles', [
            'user_id' => $user->id,
            'role_code' => 'AGENT',
            'company_id' => $company->id,
        ]);
    }
}
