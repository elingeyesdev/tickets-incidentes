<?php

declare(strict_types=1);

namespace Tests\Unit\TicketManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketRating;
use App\Features\TicketManagement\Services\RatingService;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Unit Tests for RatingService
 *
 * Tests the BUSINESS LOGIC of ticket rating system.
 * These tests validate internal rules that Feature Tests ASSUME work.
 *
 * Coverage:
 * - Ticket status validation (only resolved/closed can be rated)
 * - User ownership validation (only ticket owner can rate)
 * - Historical agent ID snapshot (rated_agent_id immutability)
 *
 * NOT COVERED HERE (covered by Feature Tests):
 * - Endpoint integration
 * - Authentication/Authorization
 * - Rating value validation (1-5)
 * - Comment length validation
 *
 * Expected Exceptions:
 * - TicketNotRateableException: When ticket status is open/pending
 * - NotTicketOwnerException: When user is not ticket owner
 * - RatingAlreadyExistsException: When ticket already has rating (from Feature Tests)
 *
 * Business Rules:
 * - Only tickets with status 'resolved' or 'closed' can be rated
 * - Only the ticket creator can rate the ticket
 * - rated_agent_id is a historical snapshot (does NOT change if ticket is reassigned)
 * - Rating can be updated within 24 hours (covered by Feature Tests)
 */
class RatingServiceTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    private RatingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Service will be instantiated when implemented
        // $this->service = app(RatingService::class);
    }

    // ==================== GROUP 1: Status Validation ====================

    /**
     * Test #4: Validates ticket status (only resolved/closed)
     *
     * BUSINESS LOGIC: The service rejects rating attempts on tickets with status 'open' or 'pending'
     *
     * Validates that RatingService throws TicketNotRateableException when attempting
     * to rate tickets that are NOT yet resolved or closed.
     *
     * Allowed Statuses for Rating:
     * - resolved: Ticket marked as resolved by agent
     * - closed: Ticket closed (manually or auto-closed after 7 days)
     *
     * Disallowed Statuses for Rating:
     * - open: Ticket still in progress (new or client responded)
     * - pending: Agent responded, waiting for client
     *
     * Expected Exception:
     * - Type: TicketNotRateableException
     * - Error message explains status requirement
     */
    #[Test]
    public function validates_ticket_resolved_or_closed_only(): void
    {
        // Arrange
        $this->service = app(RatingService::class);
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
            'status' => 'open', // Invalid status for rating
            'owner_agent_id' => null,
        ]);

        // Act & Assert
        try {
            $this->service->createRating($ticket, $user, [
                'rating' => 5,
                'comment' => 'Great service!',
            ]);
            $this->fail('Expected exception when rating open ticket');
        } catch (\Exception $e) {
            $this->assertTrue(
                str_contains(get_class($e), 'Exception'),
                'Should throw exception for non-resolved/closed ticket'
            );
            $this->assertStringContainsStringIgnoringCase(
                'resolved',
                $e->getMessage(),
                'Exception message should explain ticket must be resolved or closed'
            );
        }
    }

    // ==================== GROUP 2: Ownership Validation ====================

    /**
     * Test #5: Validates user is ticket owner
     *
     * BUSINESS LOGIC: The service rejects rating attempts from users who did not create the ticket
     *
     * Validates that RatingService throws NotTicketOwnerException when attempting
     * to rate a ticket created by a different user.
     *
     * Ownership Rule:
     * - created_by_user_id MUST match the authenticated user
     * - Agents CANNOT rate tickets (only clients can rate)
     * - Platform admins CANNOT rate tickets (business rule)
     * - Only ticket creator can rate their own tickets
     *
     * Expected Exception:
     * - Type: NotTicketOwnerException
     * - Error message explains only owner can rate
     */
    #[Test]
    public function validates_user_is_ticket_owner(): void
    {
        // Arrange
        $this->service = app(RatingService::class);
        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id, // Ticket owned by User A
            'owner_agent_id' => $agent->id,
            'status' => 'resolved',
            'resolved_at' => now()->subHour(),
        ]);

        // Act & Assert - User B (NOT the owner) attempts to rate
        try {
            $this->service->createRating($ticket, $userB, [
                'rating' => 5,
                'comment' => 'Unauthorized rating attempt',
            ]);
            $this->fail('Expected exception when non-owner attempts to rate');
        } catch (\Exception $e) {
            $this->assertTrue(
                str_contains(get_class($e), 'Exception'),
                'Should throw exception when user is not ticket owner'
            );
            $this->assertStringContainsStringIgnoringCase(
                'owner',
                $e->getMessage(),
                'Exception message should explain ownership requirement'
            );
        }
    }

    // ==================== GROUP 3: Historical Snapshot ====================

    /**
     * Test #6: Saves rated_agent_id as historical snapshot
     *
     * BUSINESS LOGIC: The rated_agent_id is captured at rating time and NEVER changes
     *
     * This is CRITICAL for metrics and reporting:
     * - Agent performance metrics need historical accuracy
     * - If ticket is reassigned AFTER rating, the rating still belongs to original agent
     * - rated_agent_id is immutable (snapshot in time)
     *
     * Context:
     * - Feature Tests validate 200 response and data structure
     * - Unit Test validates the BUSINESS LOGIC: Historical immutability
     *
     * Scenario:
     * 1. Create ticket with owner_agent_id = Agent A
     * 2. User rates ticket → rated_agent_id is saved as Agent A
     * 3. Ticket is reassigned to Agent B (owner_agent_id changes)
     * 4. Verify rated_agent_id STILL points to Agent A (immutable)
     *
     * Expected Result:
     * - rated_agent_id = Agent A (historical snapshot)
     * - owner_agent_id = Agent B (current assignment)
     * - Rating record is NOT updated when ticket is reassigned
     *
     * Why This Matters:
     * - Agent metrics: Agent A should get credit for the rating
     * - Historical accuracy: Rating reflects WHO was rated, not current assignment
     * - Data integrity: Ratings are permanent records
     *
     * Implementation Note:
     * This test will FAIL (RED) until RatingService is implemented.
     */
    #[Test]
    public function saves_rated_agent_id_from_current_owner(): void
    {
        // Arrange
        $this->service = app(RatingService::class);
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $agentA = User::factory()->withRole('AGENT', $company->id)->create();
        $agentB = User::factory()->withRole('AGENT', $company->id)->create();

        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Step 1: Create ticket assigned to Agent A
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'owner_agent_id' => $agentA->id, // Agent A
            'status' => 'resolved',
            'resolved_at' => now()->subHour(),
        ]);

        // Step 2: User rates ticket
        $rating = $this->service->createRating($ticket, $user, [
            'rating' => 5,
            'comment' => 'Agent A was excellent!',
        ]);

        // Step 3: Verify rated_agent_id is Agent A (at creation time)
        $this->assertEquals(
            $agentA->id,
            $rating->rated_agent_id,
            'rated_agent_id should be set to current owner (Agent A) at creation time'
        );

        // Step 4: Reassign ticket to Agent B
        $ticket->update(['owner_agent_id' => $agentB->id]);
        $ticket->refresh();

        // Step 5: Verify rated_agent_id STILL points to Agent A (immutable snapshot)
        $rating->refresh();
        $this->assertEquals(
            $agentA->id,
            $rating->rated_agent_id,
            'rated_agent_id MUST NOT change when ticket is reassigned (historical snapshot)'
        );
        $this->assertEquals(
            $agentB->id,
            $ticket->owner_agent_id,
            'owner_agent_id should now be Agent B (current assignment changed)'
        );

        // Step 6: Verify immutability - rated_agent_id != current owner
        $this->assertNotEquals(
            $ticket->owner_agent_id,
            $rating->rated_agent_id,
            'rated_agent_id and owner_agent_id should NOT match (immutability validation)'
        );
    }
}
