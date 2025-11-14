<?php

declare(strict_types=1);

namespace Tests\Unit\TicketManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Exceptions\TicketNotFoundException;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Services\TicketService;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Tests for TicketService
 *
 * White-box testing of business logic in TicketService.
 * Tests validate internal logic that Feature Tests assume exists.
 *
 * Coverage:
 * - Test 1: Generates unique ticket codes in format TKT-YYYY-NNNNN
 * - Test 2: Validates company exists before creating ticket
 * - Test 3: Filters tickets by owner for USER role (ownership validation)
 * - Test 4: Delete only allows tickets with status='closed'
 *
 * Total: 4 tests
 */
class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TicketService::class);
    }

    /**
     * Test #1: Generates unique ticket codes
     *
     * Validates that TicketService generates unique sequential codes
     * in the format TKT-YYYY-NNNNN.
     *
     * Business Logic:
     * - First ticket: TKT-2025-00001
     * - Second ticket: TKT-2025-00002
     * - Codes are sequential within the same year
     *
     * Expected: Two consecutive tickets have sequential codes
     */
    #[Test]
    public function generates_unique_ticket_codes(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $data1 = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'First ticket for code generation',
            'description' => 'Testing ticket code generation algorithm.',
            'created_by_user_id' => $user->id,
        ];

        $data2 = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Second ticket for code generation',
            'description' => 'Testing sequential ticket code generation.',
            'created_by_user_id' => $user->id,
        ];

        // Act
        $ticket1 = $this->service->create($data1, $user);
        $ticket2 = $this->service->create($data2, $user);

        // Assert
        $currentYear = now()->year;

        // Validate format TKT-YYYY-NNNNN
        $this->assertMatchesRegularExpression(
            "/^TKT-{$currentYear}-\d{5}$/",
            $ticket1->ticket_code,
            "First ticket code should match pattern TKT-{$currentYear}-XXXXX"
        );

        $this->assertMatchesRegularExpression(
            "/^TKT-{$currentYear}-\d{5}$/",
            $ticket2->ticket_code,
            "Second ticket code should match pattern TKT-{$currentYear}-XXXXX"
        );

        // Validate sequential numbering
        $code1Number = (int) substr($ticket1->ticket_code, -5);
        $code2Number = (int) substr($ticket2->ticket_code, -5);

        $this->assertEquals($code1Number + 1, $code2Number, 'Codes should be sequential');

        // Validate uniqueness
        $this->assertNotEquals($ticket1->ticket_code, $ticket2->ticket_code, 'Codes should be unique');
    }

    /**
     * Test #2: Validates company exists
     *
     * Validates that TicketService throws exception when attempting
     * to create a ticket with an invalid company_id.
     *
     * Business Logic:
     * - Company must exist in database before creating ticket
     * - Invalid company_id should throw TicketNotFoundException
     *
     * Expected: Exception thrown with descriptive message
     */
    #[Test]
    public function validates_company_exists(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['is_active' => true]);

        $invalidCompanyId = '550e8400-e29b-41d4-a716-446655440000';

        $data = [
            'company_id' => $invalidCompanyId,
            'category_id' => $category->id,
            'title' => 'Ticket with invalid company',
            'description' => 'This should fail because company does not exist.',
            'created_by_user_id' => $user->id,
        ];

        // Assert & Act
        $this->expectException(TicketNotFoundException::class);
        $this->expectExceptionMessage('Company not found');

        $this->service->create($data, $user);
    }

    /**
     * Test #3: Filters tickets by owner for USER role
     *
     * Validates that TicketService correctly filters tickets for USER role.
     * Users should only see tickets they created (ownership validation).
     *
     * Business Logic:
     * - User A creates 2 tickets
     * - User B creates 1 ticket
     * - When User A calls list(), should return only 2 tickets
     * - When User B calls list(), should return only 1 ticket
     * - Users should never see tickets created by other users
     *
     * Expected: Correct filtering by created_by_user_id
     */
    #[Test]
    public function filters_tickets_by_owner_for_users(): void
    {
        // Arrange
        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create 2 tickets for User A
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'title' => 'Ticket 1 by User A',
            'status' => 'open',
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'title' => 'Ticket 2 by User A',
            'status' => 'pending',
        ]);

        // Create 1 ticket for User B
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userB->id,
            'title' => 'Ticket 1 by User B',
            'status' => 'open',
        ]);

        // Act - List tickets for User A (simulate JWT context)
        request()->attributes->set('jwt_payload', [
            'sub' => $userA->id,
            'roles' => [['code' => 'USER', 'company_id' => null]],
        ]);
        $ticketsForUserA = $this->service->list([], $userA);

        // Act - List tickets for User B (simulate JWT context)
        request()->attributes->set('jwt_payload', [
            'sub' => $userB->id,
            'roles' => [['code' => 'USER', 'company_id' => null]],
        ]);
        $ticketsForUserB = $this->service->list([], $userB);

        // Assert - User A should see only 2 tickets
        $this->assertCount(2, $ticketsForUserA);
        foreach ($ticketsForUserA as $ticket) {
            $this->assertEquals($userA->id, $ticket->created_by_user_id);
        }

        // Assert - User B should see only 1 ticket
        $this->assertCount(1, $ticketsForUserB);
        foreach ($ticketsForUserB as $ticket) {
            $this->assertEquals($userB->id, $ticket->created_by_user_id);
        }

        // Assert - User B should NOT see User A's tickets
        $ticketTitlesForUserB = $ticketsForUserB->pluck('title')->toArray();
        $this->assertNotContains('Ticket 1 by User A', $ticketTitlesForUserB);
        $this->assertNotContains('Ticket 2 by User A', $ticketTitlesForUserB);
    }

    /**
     * Test #4: Delete only allows tickets with status='closed'
     *
     * Validates that TicketService only allows deletion of tickets
     * with status='closed'. Active tickets (open, pending, resolved)
     * should not be deletable.
     *
     * Business Logic:
     * - Only CLOSED tickets can be deleted
     * - Attempting to delete OPEN ticket should throw exception
     * - Attempting to delete PENDING ticket should throw exception
     * - Attempting to delete RESOLVED ticket should throw exception
     * - Deleting CLOSED ticket should succeed
     *
     * Expected: Exception for non-closed tickets, success for closed tickets
     */
    #[Test]
    public function delete_only_allows_closed_tickets(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $user = User::factory()->withRole('USER')->create();

        // Create ticket with status=open
        $openTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Create ticket with status=pending
        $pendingTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Create ticket with status=resolved
        $resolvedTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'resolved',
        ]);

        // Create ticket with status=closed
        $closedTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'closed',
        ]);

        // Assert & Act - Open ticket should throw exception
        try {
            $this->service->delete($openTicket->id);
            $this->fail('Expected exception for deleting OPEN ticket');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('closed', strtolower($e->getMessage()));
        }

        // Assert & Act - Pending ticket should throw exception
        try {
            $this->service->delete($pendingTicket->id);
            $this->fail('Expected exception for deleting PENDING ticket');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('closed', strtolower($e->getMessage()));
        }

        // Assert & Act - Resolved ticket should throw exception
        try {
            $this->service->delete($resolvedTicket->id);
            $this->fail('Expected exception for deleting RESOLVED ticket');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('closed', strtolower($e->getMessage()));
        }

        // Assert & Act - Closed ticket should succeed
        $result = $this->service->delete($closedTicket->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('ticketing.tickets', [
            'id' => $closedTicket->id,
        ]);
    }
}
