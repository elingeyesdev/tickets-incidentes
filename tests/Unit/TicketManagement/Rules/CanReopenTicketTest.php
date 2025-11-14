<?php

declare(strict_types=1);

namespace Tests\Unit\TicketManagement\Rules;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Rules\CanReopenTicket;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Unit Tests for CanReopenTicket Rule
 *
 * Tests the custom Laravel validation rule for ticket reopening permissions.
 * This rule validates whether a user can reopen a ticket based on their role
 * and the ticket's current status and closure time.
 *
 * Coverage:
 * - User can reopen within 30 days of closure
 * - User cannot reopen after 30 days
 * - Agent can reopen regardless of time
 * - Only resolved/closed tickets can be reopened
 * - Error messages explain the 30-day limit
 *
 * Business Rules:
 * - USER: Can reopen resolved/closed tickets within 30 days of closed_at
 * - AGENT: Can reopen any ticket regardless of time
 * - Only tickets with status 'resolved' or 'closed' can be reopened
 * - Open and pending tickets cannot be reopened (they're already active)
 */
class CanReopenTicketTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test #4: User can reopen within 30 days
     *
     * Verifies that a user can reopen a closed ticket when it has been closed
     * within the last 30 days.
     *
     * Expected: true when closed_at > now() - 30 days
     */
    #[Test]
    public function user_can_reopen_within_30_days(): void
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
            'resolved_at' => Carbon::now()->subDays(25),
            'closed_at' => Carbon::now()->subDays(20), // Closed 20 days ago (within 30 days)
        ]);

        // Mock JWT payload para USER
        request()->attributes->set('jwt_payload', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => [['code' => 'USER', 'company_id' => null]]
        ]);

        $rule = new CanReopenTicket($ticket);

        // Act
        $validator = Validator::make(['ticket' => $ticket->id], ['ticket' => $rule]);

        // Assert
        $this->assertTrue(
            $validator->passes(),
            'User should be able to reopen ticket closed 20 days ago (within 30-day limit)'
        );
    }

    /**
     * Test #5: User cannot reopen after 30 days
     *
     * Verifies that a user cannot reopen a ticket that has been closed
     * for more than 30 days (boundary testing).
     *
     * Expected: false when closed_at < now() - 30 days
     */
    #[Test]
    public function user_cannot_reopen_after_30_days(): void
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
            'resolved_at' => Carbon::now()->subDays(45),
            'closed_at' => Carbon::now()->subDays(40), // Closed 40 days ago (exceeds 30 days)
        ]);

        // Mock JWT payload para USER
        request()->attributes->set('jwt_payload', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => [['code' => 'USER', 'company_id' => null]]
        ]);

        $rule = new CanReopenTicket($ticket);

        // Act
        $validator = Validator::make(['ticket' => $ticket->id], ['ticket' => $rule]);

        // Assert
        $this->assertFalse(
            $validator->passes(),
            'User should NOT be able to reopen ticket closed 40 days ago (exceeds 30-day limit)'
        );
    }

    /**
     * Test #6: Agent can reopen regardless of time
     *
     * Verifies that agents can reopen tickets without time restrictions,
     * even if the ticket has been closed for more than 30 days.
     *
     * Expected: true for agents regardless of closed_at timestamp
     */
    #[Test]
    public function agent_can_reopen_regardless_of_time(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user = User::factory()->withRole('USER')->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::CLOSED,
            'resolved_at' => Carbon::now()->subDays(370),
            'closed_at' => Carbon::now()->subDays(365), // Closed 365 days ago (1 year)
        ]);

        // Mock JWT payload para AGENT
        request()->attributes->set('jwt_payload', [
            'user_id' => $agent->id,
            'email' => $agent->email,
            'roles' => [['code' => 'AGENT', 'company_id' => $company->id]]
        ]);

        $rule = new CanReopenTicket($ticket);

        // Act
        $validator = Validator::make(['ticket' => $ticket->id], ['ticket' => $rule]);

        // Assert
        $this->assertTrue(
            $validator->passes(),
            'Agent should be able to reopen ticket closed 365 days ago (no time limit for agents)'
        );
    }

    /**
     * Test #7: Must be resolved or closed status
     *
     * Verifies that the rule only allows reopening tickets that are in
     * 'resolved' or 'closed' status. Open and pending tickets cannot be reopened.
     *
     * Expected: false for open/pending, true for resolved/closed
     */
    #[Test]
    public function must_be_resolved_or_closed_status(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Test 1: Open ticket cannot be reopened
        $openTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
        ]);

        // Mock JWT payload para USER
        request()->attributes->set('jwt_payload', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => [['code' => 'USER', 'company_id' => null]]
        ]);

        $rule = new CanReopenTicket($openTicket);
        $validator = Validator::make(['ticket' => $openTicket->id], ['ticket' => $rule]);
        $this->assertFalse(
            $validator->passes(),
            'Open ticket should NOT be reopenable'
        );

        // Test 2: Pending ticket cannot be reopened
        $pendingTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::PENDING,
        ]);

        // Mock JWT payload para USER
        request()->attributes->set('jwt_payload', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => [['code' => 'USER', 'company_id' => null]]
        ]);

        $rule = new CanReopenTicket($pendingTicket);
        $validator = Validator::make(['ticket' => $pendingTicket->id], ['ticket' => $rule]);
        $this->assertFalse(
            $validator->passes(),
            'Pending ticket should NOT be reopenable'
        );

        // Test 3: Resolved ticket can be reopened
        $resolvedTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::RESOLVED,
            'resolved_at' => Carbon::now()->subDays(3),
        ]);

        // Mock JWT payload para USER
        request()->attributes->set('jwt_payload', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => [['code' => 'USER', 'company_id' => null]]
        ]);

        $rule = new CanReopenTicket($resolvedTicket);
        $validator = Validator::make(['ticket' => $resolvedTicket->id], ['ticket' => $rule]);
        $this->assertTrue(
            $validator->passes(),
            'Resolved ticket should be reopenable'
        );

        // Test 4: Closed ticket can be reopened (within 30 days)
        $closedTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::CLOSED,
            'resolved_at' => Carbon::now()->subDays(10),
            'closed_at' => Carbon::now()->subDays(5),
        ]);

        // Mock JWT payload para USER
        request()->attributes->set('jwt_payload', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => [['code' => 'USER', 'company_id' => null]]
        ]);

        $rule = new CanReopenTicket($closedTicket);
        $validator = Validator::make(['ticket' => $closedTicket->id], ['ticket' => $rule]);
        $this->assertTrue(
            $validator->passes(),
            'Closed ticket (within 30 days) should be reopenable'
        );
    }

    /**
     * Test #8: Error message explains 30 day limit
     *
     * Verifies that the error message clearly explains the 30-day limit
     * for users when they attempt to reopen an old ticket.
     *
     * Expected: Message mentions "30 days" or "30 días"
     */
    #[Test]
    public function error_message_explains_30_day_limit(): void
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
            'resolved_at' => Carbon::now()->subDays(45),
            'closed_at' => Carbon::now()->subDays(40), // Closed 40 days ago
        ]);

        // Mock JWT payload para USER
        request()->attributes->set('jwt_payload', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => [['code' => 'USER', 'company_id' => null]]
        ]);

        $rule = new CanReopenTicket($ticket);

        // Act - Trigger validation failure
        $validator = Validator::make(['ticket' => $ticket->id], ['ticket' => $rule]);
        $validator->fails(); // Execute validation to generate error messages
        $message = $validator->errors()->first('ticket');

        // Assert - Message should explain the 30-day limit
        $this->assertIsString($message, 'Error message should be a string');

        // Check for "30 days" or "30 días" (supports both English and Spanish)
        $contains30Days = str_contains(strtolower($message), '30 days') ||
                          str_contains(strtolower($message), '30 días');

        $this->assertTrue(
            $contains30Days,
            'Error message should explain the 30-day limit for users'
        );
    }
}
