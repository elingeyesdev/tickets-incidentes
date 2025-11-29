<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Priority;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Listing Tickets with Priority
 *
 * Tests the endpoint GET /api/tickets with priority field
 *
 * Coverage:
 * - List includes priority in each ticket
 * - Can filter by priority using byPriority scope
 * - Priority format is correct in TicketListResource
 * - Tickets without priority (migrated) have default value
 *
 * Expected Status Codes:
 * - 200: Tickets listed successfully with priority
 *
 * Response: Each ticket should include priority field
 */
class ListTicketsWithPriorityTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Priority in List Response (Tests 1-2) ====================

    /**
     * Test #1: List includes priority in each ticket
     *
     * Expected: 200 OK with priority field in each ticket
     */
    #[Test]
    public function list_includes_priority_in_each_ticket(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        // Create tickets with different priorities
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'low',
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'medium',
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'high',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'ticket_code',
                    'priority', // Should be present
                    'title',
                    'status',
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);

        // Verify each ticket has priority field
        foreach ($data as $ticket) {
            $this->assertArrayHasKey('priority', $ticket);
            $this->assertContains($ticket['priority'], ['low', 'medium', 'high']);
        }
    }

    /**
     * Test #2: Priority format is correct in TicketListResource
     *
     * Expected: priority is returned as string value (not object)
     */
    #[Test]
    public function priority_format_is_correct_in_ticket_list_resource(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'high',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);

        $ticket = $response->json('data.0');
        $this->assertEquals('high', $ticket['priority']);
        $this->assertIsString($ticket['priority']);
    }

    // ==================== GROUP 2: Filter by Priority (Tests 3-5) ====================

    /**
     * Test #3: Can filter by priority = low
     *
     * Expected: 200 OK with only low priority tickets
     */
    #[Test]
    public function can_filter_by_priority_low(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        Ticket::factory()->count(2)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'low',
        ]);

        Ticket::factory()->count(3)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'medium',
        ]);

        Ticket::factory()->count(1)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'high',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&priority=low");

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);

        foreach ($data as $ticket) {
            $this->assertEquals('low', $ticket['priority']);
        }
    }

    /**
     * Test #4: Can filter by priority = medium
     *
     * Expected: 200 OK with only medium priority tickets
     */
    #[Test]
    public function can_filter_by_priority_medium(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        Ticket::factory()->count(2)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'low',
        ]);

        Ticket::factory()->count(3)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'medium',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&priority=medium");

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);

        foreach ($data as $ticket) {
            $this->assertEquals('medium', $ticket['priority']);
        }
    }

    /**
     * Test #5: Can filter by priority = high
     *
     * Expected: 200 OK with only high priority tickets
     */
    #[Test]
    public function can_filter_by_priority_high(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        Ticket::factory()->count(2)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'low',
        ]);

        Ticket::factory()->count(1)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'high',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}&priority=high");

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);

        foreach ($data as $ticket) {
            $this->assertEquals('high', $ticket['priority']);
        }
    }

    // ==================== GROUP 3: Without Filter (Test 6) ====================

    /**
     * Test #6: Without priority filter returns all tickets
     *
     * Expected: 200 OK with all tickets regardless of priority
     */
    #[Test]
    public function without_priority_filter_returns_all_tickets(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'low',
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'medium',
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'priority' => 'high',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    // ==================== GROUP 4: Default Values (Test 7) ====================

    /**
     * Test #7: Tickets without priority have default value medium
     *
     * Expected: Tickets created without priority show as 'medium'
     * (This tests backward compatibility with tickets created before priority was added)
     */
    #[Test]
    public function tickets_without_explicit_priority_have_default_medium(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        // Create ticket using factory default (which should be medium)
        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            // NOT specifying priority - factory default should be medium
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets?company_id={$company->id}");

        // Assert
        $response->assertStatus(200);
        $ticket = $response->json('data.0');
        $this->assertEquals('medium', $ticket['priority']);
    }
}
