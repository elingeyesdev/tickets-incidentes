<?php

declare(strict_types=1);

namespace Tests\Unit\TicketManagement\Jobs;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Jobs\AutoCloseResolvedTicketsJob;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Unit Tests for AutoCloseResolvedTicketsJob
 *
 * Tests the scheduled job that automatically closes tickets that have been
 * in resolved status for more than 7 days.
 *
 * Coverage:
 * - Closes tickets resolved more than 7 days ago
 * - Does not close tickets resolved less than 7 days ago
 * - Sets closed_at timestamp when closing
 * - Only affects resolved tickets (not open/pending)
 * - Logs the count of closed tickets
 *
 * Business Rules:
 * - Tickets in 'resolved' status for MORE than 7 days are automatically closed
 * - Tickets in 'resolved' status for LESS than 7 days remain unchanged
 * - Only tickets with status='resolved' are affected
 * - closed_at timestamp is set to current time when closing
 * - Job logs the number of tickets closed for monitoring
 *
 * IMPORTANT:
 * This job is documented in the database schema but was not previously
 * covered by Feature Tests. These Unit Tests ensure the core logic works correctly.
 */
class AutoCloseResolvedTicketsJobTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test #9: Closes tickets resolved more than 7 days ago
     *
     * Verifies that the job closes tickets that have been in resolved status
     * for more than 7 days.
     *
     * Expected: Ticket status changes from 'resolved' to 'closed'
     */
    #[Test]
    public function closes_tickets_resolved_more_than_7_days_ago(): void
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
            'status' => TicketStatus::RESOLVED,
            'resolved_at' => Carbon::now()->subDays(8), // Resolved 8 days ago
            'closed_at' => null,
        ]);

        // Act
        $job = new AutoCloseResolvedTicketsJob();
        $job->handle();

        // Assert
        $ticket->refresh();
        $this->assertEquals(
            'closed',
            $ticket->status->value,
            'Ticket resolved 8 days ago should be closed'
        );
    }

    /**
     * Test #10: Does not close tickets resolved less than 7 days ago
     *
     * Verifies that the job does NOT close tickets that have been in resolved
     * status for less than 7 days (boundary testing).
     *
     * Expected: Ticket status remains 'resolved'
     */
    #[Test]
    public function does_not_close_tickets_resolved_less_than_7_days_ago(): void
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
            'status' => TicketStatus::RESOLVED,
            'resolved_at' => Carbon::now()->subDays(5), // Resolved 5 days ago
            'closed_at' => null,
        ]);

        // Act
        $job = new AutoCloseResolvedTicketsJob();
        $job->handle();

        // Assert
        $ticket->refresh();
        $this->assertEquals(
            'resolved',
            $ticket->status->value,
            'Ticket resolved 5 days ago should remain resolved (not closed)'
        );
    }

    /**
     * Test #11: Sets closed_at timestamp
     *
     * Verifies that when the job closes a ticket, it sets the closed_at
     * timestamp to the current time.
     *
     * Expected: closed_at is set to the timestamp when job executed
     */
    #[Test]
    public function sets_closed_at_timestamp(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Use a fixed timestamp for deterministic testing
        $fixedNow = Carbon::parse('2025-01-15 10:00:00');

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::RESOLVED,
            'resolved_at' => $fixedNow->clone()->subDays(8), // Resolved 8 days ago
            'closed_at' => null, // Should be set by job
        ]);

        // Act - Inject fixed timestamp (simulates job running at specific time)
        $job = new AutoCloseResolvedTicketsJob($fixedNow);
        $job->handle();

        // Assert
        $ticket->refresh();
        $this->assertNotNull(
            $ticket->closed_at,
            'closed_at should be set after job runs'
        );

        // Verify closed_at matches the injected timestamp (deterministic)
        $this->assertEquals(
            $fixedNow->toDateTimeString(),
            $ticket->closed_at->toDateTimeString(),
            'closed_at should be set to the job execution timestamp'
        );
    }

    /**
     * Test #12: Only affects resolved tickets
     *
     * Verifies that the job ONLY closes tickets with status='resolved' and
     * does not affect tickets in other statuses (open, pending, closed).
     *
     * Expected: Only resolved ticket is closed, others remain unchanged
     */
    #[Test]
    public function only_affects_resolved_tickets(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create tickets with different statuses, all "old" (8 days ago)
        $resolvedTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::RESOLVED,
            'resolved_at' => Carbon::now()->subDays(8),
            'closed_at' => null,
        ]);

        $openTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
            'created_at' => Carbon::now()->subDays(8), // Old but still open
        ]);

        $pendingTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::PENDING,
            'created_at' => Carbon::now()->subDays(8), // Old but still pending
        ]);

        // Act
        $job = new AutoCloseResolvedTicketsJob();
        $job->handle();

        // Assert
        $resolvedTicket->refresh();
        $openTicket->refresh();
        $pendingTicket->refresh();

        $this->assertEquals(
            'closed',
            $resolvedTicket->status->value,
            'Resolved ticket should be closed'
        );

        $this->assertEquals(
            'open',
            $openTicket->status->value,
            'Open ticket should remain open'
        );

        $this->assertEquals(
            'pending',
            $pendingTicket->status->value,
            'Pending ticket should remain pending'
        );

        // Verify count of closed tickets
        $closedCount = Ticket::where('status', TicketStatus::CLOSED)->count();
        $this->assertEquals(
            1,
            $closedCount,
            'Only 1 ticket (the resolved one) should be closed'
        );
    }

    /**
     * Test #13: Logs closed tickets count
     *
     * Verifies that the job logs the number of tickets that were closed
     * for monitoring and auditing purposes.
     *
     * Expected: Log::info() is called with count of closed tickets
     */
    #[Test]
    public function logs_closed_tickets_count(): void
    {
        // Arrange
        Log::spy();

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create 3 resolved tickets (all should be closed)
        for ($i = 0; $i < 3; $i++) {
            Ticket::factory()->create([
                'company_id' => $company->id,
                'category_id' => $category->id,
                'created_by_user_id' => $user->id,
                'status' => TicketStatus::RESOLVED,
                'resolved_at' => Carbon::now()->subDays(8),
                'closed_at' => null,
            ]);
        }

        // Act
        $job = new AutoCloseResolvedTicketsJob();
        $job->handle();

        // Assert - Verify logging occurred
        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context = []) {
                // Check that the log message contains count information
                $containsCount = str_contains(strtolower($message), 'closed') ||
                                 str_contains(strtolower($message), 'ticket');

                // Check if context contains count=3 or message mentions "3"
                $hasCorrectCount = (isset($context['count']) && $context['count'] === 3) ||
                                   str_contains($message, '3');

                return $containsCount && $hasCorrectCount;
            });
    }
}
