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
 * Feature Tests for Ticket Ownership Permissions
 *
 * Tests ownership and role-based access control for ticket management.
 *
 * Coverage:
 * - User can only access own tickets
 * - User can respond only to own tickets
 * - User can upload attachments only to own tickets
 * - Agent can access all tickets from own company
 * - Agent cannot access tickets from other companies
 * - Company admin has full access to own company tickets
 * - Company admin cannot access other company tickets
 * - Platform admin has read-only access to all tickets
 * - Suspended user cannot access tickets
 *
 * Expected Status Codes:
 * - 200: OK (GET, PATCH successful)
 * - 201: Created (POST successful)
 * - 403: Forbidden (insufficient permissions)
 * - 401: Unauthenticated (no token or invalid)
 */
class TicketOwnershipTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test #1: User can only access own tickets
     *
     * Verifies that users can only access tickets they created.
     * Other users should receive 403 Forbidden when trying to access
     * tickets they don't own.
     *
     * Expected: 200 for owner, 403 for other user
     */
    #[Test]
    public function test_user_can_only_access_own_tickets(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'status' => TicketStatus::OPEN,
        ]);

        // ==================== Act ====================
        // User A accesses own ticket
        $responseUserA = $this->authenticateWithJWT($userA)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // User B tries to access User A's ticket
        $responseUserB = $this->authenticateWithJWT($userB)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // ==================== Assert ====================
        $responseUserA->assertStatus(200);
        $responseUserA->assertJsonPath('data.id', $ticket->id);

        $responseUserB->assertStatus(403);
    }

    /**
     * Test #2: User can respond only to own tickets
     *
     * Verifies that users can only add responses to tickets they created.
     * Other users should receive 403 Forbidden.
     *
     * Expected: 201 for owner, 403 for other user
     */
    #[Test]
    public function test_user_can_respond_only_to_own_tickets(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'status' => TicketStatus::OPEN,
        ]);

        $payload = [
            'content' => 'This is a response to the ticket with minimum required length.',
        ];

        // ==================== Act ====================
        // User B tries to POST response
        $responseUserB = $this->authenticateWithJWT($userB)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", $payload);

        // User A can POST response
        $responseUserA = $this->authenticateWithJWT($userA)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", $payload);

        // ==================== Assert ====================
        $responseUserB->assertStatus(403);
        $responseUserA->assertStatus(201);
    }

    /**
     * Test #3: User can upload attachments only to own tickets
     *
     * Verifies that users can only upload attachments to tickets they created.
     * Other users should receive 403 Forbidden.
     *
     * Expected: 201 for owner, 403 for other user
     */
    #[Test]
    public function test_user_can_upload_attachments_only_to_own_tickets(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'status' => TicketStatus::OPEN,
        ]);

        $payload = [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('test.jpg'),
        ];

        // ==================== Act ====================
        // User B tries to POST attachment
        $responseUserB = $this->authenticateWithJWT($userB)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", $payload);

        // User A can POST attachment
        $responseUserA = $this->authenticateWithJWT($userA)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", $payload);

        // ==================== Assert ====================
        $responseUserB->assertStatus(403);
        $responseUserA->assertStatus(201);
    }

    /**
     * Test #4: Agent can access all tickets from own company
     *
     * Verifies that agents assigned to a company can access all tickets
     * from that company, regardless of who created them.
     *
     * Expected: 200, agent sees all 3 tickets from company
     */
    #[Test]
    public function test_agent_can_access_all_tickets_from_own_company(): void
    {
        // ==================== Arrange ====================
        $companyA = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $companyA->id]);

        $agent = User::factory()->withRole('AGENT', $companyA->id)->create();

        $user1 = User::factory()->withRole('USER')->create();
        $user2 = User::factory()->withRole('USER')->create();
        $user3 = User::factory()->withRole('USER')->create();

        Ticket::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user1->id,
        ]);

        Ticket::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user2->id,
        ]);

        Ticket::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user3->id,
        ]);

        // ==================== Act ====================
        $response = $this->authenticateWithJWT($agent)
            ->getJson('/api/tickets');

        // ==================== Assert ====================
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test #5: Agent cannot access tickets from other companies
     *
     * Verifies that agents can only access tickets from their assigned company.
     * Tickets from other companies should return 403 Forbidden.
     *
     * Expected: 403 for tickets from other companies
     */
    #[Test]
    public function test_agent_cannot_access_tickets_from_other_companies(): void
    {
        // ==================== Arrange ====================
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        $agentA = User::factory()->withRole('AGENT', $companyA->id)->create();
        $userB = User::factory()->withRole('USER')->create();

        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $userB->id,
        ]);

        // ==================== Act ====================
        $response = $this->authenticateWithJWT($agentA)
            ->getJson("/api/tickets/{$ticketB->ticket_code}");

        // ==================== Assert ====================
        $response->assertStatus(403);
    }

    /**
     * Test #6: Company admin has full access to own company tickets
     *
     * Verifies that company admins have full CRUD access to all tickets
     * from their company.
     *
     * Expected: 200 for all operations on company tickets
     */
    #[Test]
    public function test_company_admin_has_full_access_to_own_company_tickets(): void
    {
        // ==================== Arrange ====================
        $companyA = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $companyA->id]);

        $admin = $this->createCompanyAdmin();
        $companyA->update(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $companyA->id);

        $user = User::factory()->withRole('USER')->create();

        $ticket1 = Ticket::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        $ticket2 = Ticket::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // ==================== Act ====================
        $responseGet = $this->authenticateWithJWT($admin)
            ->getJson("/api/tickets/{$ticket1->ticket_code}");

        $responsePatch = $this->authenticateWithJWT($admin)
            ->patchJson("/api/tickets/{$ticket2->ticket_code}", [
                'title' => 'Updated title by admin',
            ]);

        // ==================== Assert ====================
        $responseGet->assertStatus(200);
        $responsePatch->assertStatus(200);
    }

    /**
     * Test #7: Company admin cannot access other company tickets
     *
     * Verifies that company admins are isolated to their own company
     * and cannot access tickets from other companies.
     *
     * Expected: 403 for tickets from other companies
     */
    #[Test]
    public function test_company_admin_cannot_access_other_company_tickets(): void
    {
        // ==================== Arrange ====================
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        $adminA = $this->createCompanyAdmin();
        $companyA->update(['admin_user_id' => $adminA->id]);
        $adminA->assignRole('COMPANY_ADMIN', $companyA->id);

        $userB = User::factory()->withRole('USER')->create();

        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $userB->id,
        ]);

        // ==================== Act ====================
        $response = $this->authenticateWithJWT($adminA)
            ->patchJson("/api/tickets/{$ticketB->ticket_code}", [
                'title' => 'Trying to update other company ticket',
            ]);

        // ==================== Assert ====================
        $response->assertStatus(403);
    }

    /**
     * Test #8: Platform admin has read-only access to all tickets
     *
     * Verifies that platform admins can read all tickets from any company
     * but cannot modify them (read-only access).
     *
     * Expected: 200 for GET, 403 for PATCH
     */
    #[Test]
    public function test_platform_admin_has_read_only_access_to_all_tickets(): void
    {
        // ==================== Arrange ====================
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();

        $ticketA = Ticket::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
            'created_by_user_id' => $userA->id,
        ]);

        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $userB->id,
        ]);

        // ==================== Act ====================
        $responseGetAll = $this->authenticateWithJWT($platformAdmin)
            ->getJson('/api/tickets');

        $responseGetA = $this->authenticateWithJWT($platformAdmin)
            ->getJson("/api/tickets/{$ticketA->ticket_code}");

        $responsePatch = $this->authenticateWithJWT($platformAdmin)
            ->patchJson("/api/tickets/{$ticketB->ticket_code}", [
                'title' => 'Platform admin trying to modify',
            ]);

        // ==================== Assert ====================
        $responseGetAll->assertStatus(200);
        $responseGetAll->assertJsonCount(2, 'data');

        $responseGetA->assertStatus(200);

        $responsePatch->assertStatus(403);
    }

    /**
     * Test #9: Suspended user cannot access tickets
     *
     * Verifies that users with suspended status (is_active = false)
     * cannot create or access tickets.
     *
     * Expected: 403 for suspended user
     */
    #[Test]
    public function test_suspended_user_cannot_access_tickets(): void
    {
        // ==================== Arrange ====================
        $user = User::factory()->withRole('USER')->suspended()->create();

        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Test ticket from suspended user',
            'description' => 'This should not be allowed because user is suspended.',
        ];

        // ==================== Act ====================
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // ==================== Assert ====================
        $response->assertStatus(401);
    }
}
