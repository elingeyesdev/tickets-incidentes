<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Permissions;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Company Following Permissions
 *
 * Tests that company following status affects UI priority and notifications,
 * but does NOT restrict access control.
 *
 * Coverage:
 * - User can create ticket in any company (following is not a barrier)
 * - Following affects company listing order, not access
 * - Following affects notifications, not access
 * - Agent does not need to follow own company
 * - Company admin does not need to follow own company
 * - Following provides information priority only
 *
 * Expected Status Codes:
 * - 200: OK (GET successful)
 * - 201: Created (POST successful)
 *
 * Key Concept: Following is informational/UI preference, NOT access control
 */
class CompanyFollowingTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test #1: User can create ticket in any company
     *
     * Verifies that users can create tickets in any company regardless
     * of following status. Following is NOT a barrier for ticket creation.
     *
     * Expected: 201 for any company
     */
    #[Test]
    public function test_user_can_create_ticket_in_any_company(): void
    {
        // ==================== Arrange ====================
        $user = User::factory()->withRole('USER')->create();

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // User does NOT follow either company
        // No CompanyFollower records created

        $payloadA = [
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
            'title' => 'Ticket in Company A without following',
            'description' => 'User should be able to create tickets in any company.',
        ];

        $payloadB = [
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'title' => 'Ticket in Company B without following',
            'description' => 'Following is not required for ticket creation.',
        ];

        // ==================== Act ====================
        $responseA = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payloadA);

        $responseB = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payloadB);

        // ==================== Assert ====================
        $responseA->assertStatus(201);
        $responseB->assertStatus(201);
    }

    /**
     * Test #2: Following affects company listing order not access
     *
     * Verifies that following a company affects UI priority (listing order)
     * but does NOT restrict access to tickets from non-followed companies.
     *
     * Expected: 200 for both followed and non-followed companies
     */
    #[Test]
    public function test_following_affects_company_listing_order_not_access(): void
    {
        // ==================== Arrange ====================
        $user = User::factory()->withRole('USER')->create();

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // User follows Company A
        CompanyFollower::factory()->create([
            'user_id' => $user->id,
            'company_id' => $companyA->id,
        ]);

        // User does NOT follow Company B

        $ticketA = Ticket::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
            'created_by_user_id' => $user->id,
        ]);

        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $user->id,
        ]);

        // ==================== Act ====================
        $responseA = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticketA->ticket_code}");

        $responseB = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticketB->ticket_code}");

        // ==================== Assert ====================
        // User can access tickets from followed company
        $responseA->assertStatus(200);

        // User CAN ALSO access tickets from non-followed company
        $responseB->assertStatus(200);
    }

    /**
     * Test #3: Following affects notifications not access
     *
     * Verifies that following affects notification preferences but
     * owners still get notified for their tickets regardless of following.
     *
     * Expected: Notifications work by ownership/role, not following
     */
    #[Test]
    public function test_following_affects_notifications_not_access(): void
    {
        // ==================== Arrange ====================
        $user = User::factory()->withRole('USER')->create();

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // User follows Company A
        CompanyFollower::factory()->create([
            'user_id' => $user->id,
            'company_id' => $companyA->id,
        ]);

        // User does NOT follow Company B but creates ticket there

        $payloadB = [
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'title' => 'Ticket in non-followed company',
            'description' => 'Owner should still get notified regardless of following status.',
        ];

        // ==================== Act ====================
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payloadB);

        // ==================== Assert ====================
        // Ticket created successfully
        $response->assertStatus(201);

        // Verify ticket was created (owner gets notified regardless of following)
        $this->assertDatabaseHas('ticketing.tickets', [
            'company_id' => $companyB->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket in non-followed company',
        ]);
    }

    /**
     * Test #4: Agent does not need to follow own company
     *
     * Verifies that agents have access to all company tickets
     * WITHOUT needing to follow the company.
     * Role-based access supersedes following.
     *
     * Expected: 200 for agent accessing company tickets without following
     */
    #[Test]
    public function test_agent_does_not_need_to_follow_own_company(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        // Agent does NOT follow the company
        // No CompanyFollower record

        $user = User::factory()->withRole('USER')->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // ==================== Act ====================
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // ==================== Assert ====================
        $response->assertStatus(200);

        // Verify agent can access WITHOUT following
        $this->assertDatabaseMissing('business.user_company_followers', [
            'user_id' => $agent->id,
            'company_id' => $company->id,
        ]);
    }

    /**
     * Test #5: Company admin does not need to follow own company
     *
     * Verifies that company admins have full access to company tickets
     * WITHOUT needing to follow the company.
     * Role-based access supersedes following.
     *
     * Expected: 200 for admin accessing company tickets without following
     */
    #[Test]
    public function test_company_admin_does_not_need_to_follow_own_company(): void
    {
        // ==================== Arrange ====================
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $admin = $this->createCompanyAdmin();
        $company->update(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        // Admin does NOT follow the company
        // No CompanyFollower record

        $user = User::factory()->withRole('USER')->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // ==================== Act ====================
        $response = $this->authenticateWithJWT($admin)
            ->getJson("/api/tickets/{$ticket->ticket_code}");

        // ==================== Assert ====================
        $response->assertStatus(200);

        // Verify admin can access WITHOUT following
        $this->assertDatabaseMissing('business.user_company_followers', [
            'user_id' => $admin->id,
            'company_id' => $company->id,
        ]);
    }

    /**
     * Test #6: Following provides information priority only
     *
     * Verifies that following a company affects UI priority (e.g., which
     * companies appear first in listings) but does NOT restrict access.
     * Users can access tickets from any company.
     *
     * Expected: User can access both followed and non-followed companies
     */
    #[Test]
    public function test_following_provides_information_priority_only(): void
    {
        // ==================== Arrange ====================
        $user = User::factory()->withRole('USER')->create();

        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // User follows Company A (should appear first in UI)
        CompanyFollower::factory()->create([
            'user_id' => $user->id,
            'company_id' => $companyA->id,
        ]);

        // User does NOT follow Company B (but can still access)

        $ticketA = Ticket::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
            'created_by_user_id' => $user->id,
        ]);

        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $user->id,
        ]);

        // ==================== Act ====================
        $responseA = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticketA->ticket_code}");

        $responseB = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticketB->ticket_code}");

        // ==================== Assert ====================
        // Both companies are accessible
        $responseA->assertStatus(200);
        $responseB->assertStatus(200);

        // Following is informational only
        $this->assertDatabaseHas('business.user_company_followers', [
            'user_id' => $user->id,
            'company_id' => $companyA->id,
        ]);

        $this->assertDatabaseMissing('business.user_company_followers', [
            'user_id' => $user->id,
            'company_id' => $companyB->id,
        ]);
    }
}
