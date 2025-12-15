<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\AutoEscalation;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Jobs\EscalateTicketPriorityJob;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Auto-Escalation Edge Cases
 *
 * Tests edge cases and special scenarios:
 * - Ticket created with HIGH priority (should not escalate again)
 * - Ticket receives agent response before 24h (should not escalate)
 * - Ticket closed before 24h (should not escalate)
 * - Multiple tickets created simultaneously escalate individually
 * - Job handles deleted tickets gracefully
 * - Job refreshes ticket before checking state
 *
 * Coverage: Error handling, concurrency, data consistency
 */
class AutoEscalationEdgeCasesTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Created with HIGH Priority (Test 1) ====================

    /**
     * Test #1: Ticket created with HIGH priority does not escalate
     *
     * Expected: Priority remains HIGH (job does nothing)
     */
    #[Test]
    public function ticket_created_with_high_priority_does_not_escalate(): void
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
            'priority' => 'high', // Created with HIGH
            'first_response_at' => null,
        ]);

        // Act - Execute escalation job
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert - Should remain HIGH
        $ticket->refresh();
        $this->assertEquals('high', $ticket->priority->value);
    }

    // ==================== GROUP 2: Agent Response Before 24h (Test 2) ====================

    /**
     * Test #2: Ticket with agent response before 24h does not escalate
     *
     * Expected: Priority remains unchanged because first_response_at is set
     */
    #[Test]
    public function ticket_with_agent_response_before_24h_does_not_escalate(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'priority' => 'medium',
            'first_response_at' => null,
        ]);

        // Simulate agent response (sets first_response_at)
        $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Agent response to prevent escalation.',
            ])
            ->assertStatus(201);

        $ticket->refresh();
        $this->assertNotNull($ticket->first_response_at);

        // Act - Execute escalation job (simulating 24h later)
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert - Should NOT escalate because first_response_at is set
        $ticket->refresh();
        $this->assertEquals('medium', $ticket->priority->value);
    }

    // ==================== GROUP 3: Ticket Closed Before 24h (Test 3) ====================

    /**
     * Test #3: Ticket closed before 24h does not escalate
     *
     * Expected: Priority remains unchanged because status is closed
     */
    #[Test]
    public function ticket_closed_before_24h_does_not_escalate(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'priority' => 'low',
            'first_response_at' => null,
            'owner_agent_id' => $agent->id,
        ]);

        // Close the ticket
        $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/close")
            ->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals('closed', $ticket->status->value);

        // Act - Execute escalation job
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert - Should NOT escalate because status is closed
        $ticket->refresh();
        $this->assertEquals('low', $ticket->priority->value);
    }

    // ==================== GROUP 4: Multiple Tickets Simultaneous (Test 4) ====================

    /**
     * Test #4: Multiple tickets created simultaneously escalate individually
     *
     * Expected: Each ticket queues its own escalation job
     */
    #[Test]
    public function multiple_tickets_created_simultaneously_escalate_individually(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        // Create 3 tickets simultaneously
        for ($i = 1; $i <= 3; $i++) {
            $payload = [
                'company_id' => $company->id,
                'category_id' => $category->id,
                'title' => "Ticket {$i}",
                'description' => "Testing concurrent tickets {$i}.",
            ];

            $this->authenticateWithJWT($user)
                ->postJson('/api/tickets', $payload)
                ->assertStatus(201);
        }

        // Assert - 3 jobs should be queued
        Queue::assertPushed(EscalateTicketPriorityJob::class, 3);
    }

    // ==================== GROUP 5: Deleted Ticket (Test 5) ====================

    /**
     * Test #5: Job handles deleted ticket gracefully
     *
     * Expected: Job completes without errors even if ticket was deleted
     * Note: Since we use soft deletes, ticket will still exist but marked as deleted
     */
    #[Test]
    public function job_handles_deleted_ticket_gracefully(): void
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

        // Soft delete the ticket
        $ticket->delete();

        // Act - Execute job (should not throw exception)
        $job = new EscalateTicketPriorityJob($ticket);

        // Assert - Job completes without throwing exception
        $this->expectNotToPerformAssertions();
        $job->handle();
    }

    // ==================== GROUP 6: Model Refresh (Test 6) ====================

    /**
     * Test #6: Job refreshes ticket before verifying state
     *
     * Expected: Job uses fresh data from database, not stale model
     */
    #[Test]
    public function job_refreshes_ticket_before_verifying_state(): void
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

        // Simulate ticket state change in database (not reflected in current model instance)
        Ticket::where('id', $ticket->id)->update(['status' => 'closed']);

        // Act - Execute job with stale model
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert - Job should have refreshed and NOT escalated (because status is now closed)
        $ticket->refresh();
        $this->assertEquals('medium', $ticket->priority->value);
        $this->assertEquals('closed', $ticket->status->value);
    }

    // ==================== GROUP 7: Concurrency Safety (Test 7) ====================

    /**
     * Test #7: Multiple jobs for same ticket don't cause issues
     *
     * Expected: Running job twice on same ticket is idempotent
     */
    #[Test]
    public function multiple_jobs_for_same_ticket_are_idempotent(): void
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

        // Act - Execute job twice
        $job1 = new EscalateTicketPriorityJob($ticket);
        $job1->handle();

        $ticket->refresh();

        $job2 = new EscalateTicketPriorityJob($ticket);
        $job2->handle();

        // Assert - Should only escalate once (HIGH after first job, stays HIGH after second)
        $ticket->refresh();
        $this->assertEquals('high', $ticket->priority->value);
    }

    // ==================== GROUP 8: Queue Integration (Test 8) ====================

    /**
     * Test #8: Jobs enqueue correctly in 'default' queue
     *
     * Expected: EscalateTicketPriorityJob uses 'default' queue
     */
    #[Test]
    public function jobs_enqueue_correctly_in_default_queue(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Test Queue Assignment',
            'description' => 'Testing queue configuration.',
        ];

        // Act
        $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload)
            ->assertStatus(201);

        // Assert
        Queue::assertPushed(EscalateTicketPriorityJob::class, function ($job) {
            return $job->queue === 'default';
        });
    }
}
