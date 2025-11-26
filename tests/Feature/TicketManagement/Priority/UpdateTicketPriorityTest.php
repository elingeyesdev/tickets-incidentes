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
 * Feature Tests for Updating Ticket Priority
 *
 * Tests the endpoint PATCH /api/tickets/:code with priority field
 *
 * Coverage:
 * - AGENT can update priority of tickets
 * - USER (creator) can update priority when ticket is OPEN
 * - Invalid priority values fail validation
 * - Priority change persists correctly
 * - Updated priority is returned in response
 *
 * Expected Status Codes:
 * - 200: Priority updated successfully
 * - 422: Invalid priority value
 * - 403: Insufficient permissions
 *
 * Database Schema: ticketing.tickets.priority (ENUM: low, medium, high)
 */
class UpdateTicketPriorityTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: AGENT Updates Priority (Tests 1-3) ====================

    /**
     * Test #1: AGENT can update ticket priority from low to high
     *
     * Expected: 200 OK with priority = high
     * Database: Ticket priority should be updated
     */
    #[Test]
    public function agent_can_update_ticket_priority(): void
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
        ]);

        $payload = ['priority' => 'high'];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => 'high',
        ]);
    }

    /**
     * Test #2: AGENT can update priority to low
     *
     * Expected: 200 OK
     */
    #[Test]
    public function agent_can_update_priority_to_low(): void
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
            'priority' => 'high',
        ]);

        $payload = ['priority' => 'low'];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.priority', 'low');

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => 'low',
        ]);
    }

    /**
     * Test #3: AGENT can update priority to medium
     *
     * Expected: 200 OK
     */
    #[Test]
    public function agent_can_update_priority_to_medium(): void
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
        ]);

        $payload = ['priority' => 'medium'];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.priority', 'medium');
    }

    // ==================== GROUP 2: USER (Creator) Updates Priority (Test 4) ====================

    /**
     * Test #4: USER (creator) can update priority when ticket is OPEN
     *
     * Expected: 200 OK
     */
    #[Test]
    public function user_creator_can_update_priority_when_ticket_is_open(): void
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
        ]);

        $payload = ['priority' => 'high'];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => 'high',
        ]);
    }

    // ==================== GROUP 3: Validation (Test 5) ====================

    /**
     * Test #5: Invalid priority value fails validation
     *
     * Expected: 422 Unprocessable Entity
     */
    #[Test]
    public function invalid_priority_value_fails_validation(): void
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
        ]);

        $payload = ['priority' => 'critical']; // Invalid value

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);

        // Verify priority unchanged
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => 'medium',
        ]);
    }

    // ==================== GROUP 4: Persistence (Test 6) ====================

    /**
     * Test #6: Priority change persists correctly
     *
     * Expected: 200 OK and database has updated priority
     */
    #[Test]
    public function priority_change_persists_correctly(): void
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
        ]);

        $payload = ['priority' => 'high'];

        // Act
        $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload)
            ->assertStatus(200);

        // Refresh ticket from DB
        $ticket->refresh();

        // Assert
        $this->assertEquals('high', $ticket->priority->value);

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => 'high',
        ]);
    }

    // ==================== GROUP 5: Response Format (Test 7) ====================

    /**
     * Test #7: Updated priority is returned in response
     *
     * Expected: 200 OK with updated priority in response
     */
    #[Test]
    public function updated_priority_is_returned_in_response(): void
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
        ]);

        $payload = ['priority' => 'high'];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'ticket_code',
                'priority',
                'title',
                'description',
                'status',
            ],
        ]);
        $response->assertJsonPath('data.priority', 'high');
    }

    // ==================== GROUP 6: Partial Update (Test 8) ====================

    /**
     * Test #8: Can update priority without changing other fields
     *
     * Expected: 200 OK with only priority changed
     */
    #[Test]
    public function can_update_priority_without_changing_other_fields(): void
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
            'title' => 'Original Title',
            'description' => 'Original Description',
            'priority' => 'low',
        ]);

        $payload = ['priority' => 'high']; // Only updating priority

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.priority', 'high');
        $response->assertJsonPath('data.title', 'Original Title');
        $response->assertJsonPath('data.description', 'Original Description');

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => 'high',
            'title' => 'Original Title',
            'description' => 'Original Description',
        ]);
    }
}
