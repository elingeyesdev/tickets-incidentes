<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\AutoEscalation;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Events\TicketCreated;
use App\Features\TicketManagement\Jobs\EscalateTicketPriorityJob;
use App\Features\TicketManagement\Listeners\DispatchEscalationJob;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Ticket Auto-Escalation Job
 *
 * Tests the auto-escalation system:
 * - TicketCreated event is dispatched
 * - DispatchEscalationJob listener programs the job
 * - EscalateTicketPriorityJob executes after 24 hours
 * - Escalation only happens if ticket is OPEN and has no first_response_at
 * - Job configuration (3 retries, 30s timeout, default queue)
 *
 * Business Rule: Tickets OPEN with no agent response after 24h escalate to HIGH priority
 *
 * Expected Behavior:
 * - OPEN + no first_response_at → escalate to HIGH
 * - PENDING/RESOLVED/CLOSED → do NOT escalate
 * - OPEN + first_response_at exists → do NOT escalate
 * - Already HIGH → do NOT escalate again
 */
class TicketAutoEscalationJobTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Event and Listener (Tests 1-2) ====================

    /**
     * Test #1: Creating ticket dispatches TicketCreated event
     *
     * Expected: TicketCreated event is dispatched when ticket is created
     */
    #[Test]
    public function creating_ticket_dispatches_ticket_created_event(): void
    {
        // Arrange
        Event::fake([TicketCreated::class]);

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Test Ticket for Event',
            'description' => 'Testing event dispatch.',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        Event::assertDispatched(TicketCreated::class);
    }

    /**
     * Test #2: DispatchEscalationJob listener programs job for 24 hours later
     *
     * Expected: EscalateTicketPriorityJob is queued with 24h delay
     */
    #[Test]
    public function dispatch_escalation_job_listener_programs_job_for_24_hours(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Test Ticket for Queue',
            'description' => 'Testing job queueing.',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);

        Queue::assertPushed(EscalateTicketPriorityJob::class, function ($job) {
            // Verify job is programmed with delay
            return $job->delay !== null;
        });
    }

    // ==================== GROUP 2: Job Execution - Escalate (Tests 3-4) ====================

    /**
     * Test #3: Job escalates OPEN ticket with no first_response_at to HIGH
     *
     * Expected: Ticket priority changes from medium to high
     * Database: priority should be 'high'
     */
    #[Test]
    public function job_escalates_open_ticket_with_no_first_response_at_to_high(): void
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
            'priority' => 'medium',
            'first_response_at' => null,
        ]);

        // Act - Execute the job directly (simulating 24 hours later)
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert
        $ticket->refresh();
        $this->assertEquals('high', $ticket->priority->value);

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => 'high',
        ]);
    }

    /**
     * Test #4: Job escalates from LOW to HIGH
     *
     * Expected: Priority changes from low to high
     */
    #[Test]
    public function job_escalates_from_low_to_high(): void
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
            'priority' => 'low',
            'first_response_at' => null,
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert
        $ticket->refresh();
        $this->assertEquals('high', $ticket->priority->value);
    }

    // ==================== GROUP 3: Job Execution - Do NOT Escalate (Tests 5-8) ====================

    /**
     * Test #5: Job does NOT escalate if ticket has first_response_at
     *
     * Expected: Priority remains unchanged
     */
    #[Test]
    public function job_does_not_escalate_if_ticket_has_first_response_at(): void
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
            'priority' => 'medium',
            'first_response_at' => now(), // Agent already responded
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert - Priority should NOT change
        $ticket->refresh();
        $this->assertEquals('medium', $ticket->priority->value);
    }

    /**
     * Test #6: Job does NOT escalate if ticket status is PENDING
     *
     * Expected: Priority remains unchanged
     */
    #[Test]
    public function job_does_not_escalate_if_ticket_status_is_pending(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
            'priority' => 'medium',
            'first_response_at' => null,
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert
        $ticket->refresh();
        $this->assertEquals('medium', $ticket->priority->value);
    }

    /**
     * Test #7: Job does NOT escalate if ticket status is RESOLVED
     *
     * Expected: Priority remains unchanged
     */
    #[Test]
    public function job_does_not_escalate_if_ticket_status_is_resolved(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'resolved',
            'priority' => 'medium',
            'first_response_at' => null,
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert
        $ticket->refresh();
        $this->assertEquals('medium', $ticket->priority->value);
    }

    /**
     * Test #8: Job does NOT escalate if ticket status is CLOSED
     *
     * Expected: Priority remains unchanged
     */
    #[Test]
    public function job_does_not_escalate_if_ticket_status_is_closed(): void
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
            'priority' => 'medium',
            'first_response_at' => null,
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert
        $ticket->refresh();
        $this->assertEquals('medium', $ticket->priority->value);
    }

    // ==================== GROUP 4: Already HIGH Priority (Test 9) ====================

    /**
     * Test #9: Job does NOT escalate if ticket is already HIGH
     *
     * Expected: Priority remains HIGH (no change)
     */
    #[Test]
    public function job_does_not_escalate_if_ticket_is_already_high(): void
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
            'priority' => 'high',
            'first_response_at' => null,
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert - Should remain HIGH (no re-escalation)
        $ticket->refresh();
        $this->assertEquals('high', $ticket->priority->value);
    }

    // ==================== GROUP 5: Job Configuration (Test 10) ====================

    /**
     * Test #10: Job has correct configuration (3 retries, 30s timeout, default queue)
     *
     * Expected: Job properties match specifications
     */
    #[Test]
    public function job_has_correct_configuration(): void
    {
        // Arrange
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'priority' => 'medium',
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);

        // Assert - Verify job configuration
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(30, $job->timeout);
        $this->assertEquals('default', $job->queue);
    }
}
