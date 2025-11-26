<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Priority;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketPriority;
use App\Features\TicketManagement\Models\Category;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating Tickets with Priority
 *
 * Tests the endpoint POST /api/tickets with priority field
 *
 * Coverage:
 * - Create ticket with priority (low, medium, high)
 * - Priority is optional (defaults to medium)
 * - Invalid priority values fail validation
 * - Priority is returned in TicketResource
 * - Priority is stored correctly in ENUM column
 *
 * Expected Status Codes:
 * - 201: Ticket created successfully with priority
 * - 422: Invalid priority value
 *
 * Database Schema: ticketing.tickets.priority (ENUM: low, medium, high)
 */
class CreateTicketWithPriorityTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Create with Priority (Tests 1-3) ====================

    /**
     * Test #1: USER can create ticket with priority LOW
     *
     * Expected: 201 Created with priority = low
     * Database: Ticket should have priority = low
     */
    #[Test]
    public function user_can_create_ticket_with_priority_low(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con prioridad baja',
            'description' => 'Este ticket tiene prioridad baja.',
            'priority' => 'low',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.priority', 'low');

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket con prioridad baja',
            'priority' => 'low',
        ]);
    }

    /**
     * Test #2: USER can create ticket with priority MEDIUM
     *
     * Expected: 201 Created with priority = medium
     */
    #[Test]
    public function user_can_create_ticket_with_priority_medium(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con prioridad media',
            'description' => 'Este ticket tiene prioridad media.',
            'priority' => 'medium',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.priority', 'medium');

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket con prioridad media',
            'priority' => 'medium',
        ]);
    }

    /**
     * Test #3: USER can create ticket with priority HIGH
     *
     * Expected: 201 Created with priority = high
     */
    #[Test]
    public function user_can_create_ticket_with_priority_high(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con prioridad alta',
            'description' => 'Este ticket tiene prioridad alta.',
            'priority' => 'high',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket con prioridad alta',
            'priority' => 'high',
        ]);
    }

    // ==================== GROUP 2: Optional Field (Test 4) ====================

    /**
     * Test #4: Priority is optional and defaults to medium
     *
     * Expected: 201 Created with priority = medium (default)
     * Database: Ticket should have priority = medium
     */
    #[Test]
    public function priority_is_optional_and_defaults_to_medium(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket sin prioridad',
            'description' => 'Este ticket no especifica prioridad.',
            // NOT providing priority
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.priority', 'medium');

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket sin prioridad',
            'priority' => 'medium',
        ]);
    }

    // ==================== GROUP 3: Validation (Tests 5-6) ====================

    /**
     * Test #5: Invalid priority value fails validation
     *
     * Expected: 422 Unprocessable Entity
     */
    #[Test]
    public function invalid_priority_value_fails_validation(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con prioridad inválida',
            'description' => 'Este ticket tiene prioridad inválida.',
            'priority' => 'critical', // Invalid value
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);
    }

    /**
     * Test #6: Priority validation accepts only low, medium, high
     *
     * Expected: 422 for invalid values
     */
    #[Test]
    public function priority_validation_accepts_only_valid_values(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $basePayload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Test Ticket',
            'description' => 'Test Description',
        ];

        // Invalid values
        $invalidValues = ['urgent', 'critical', 'normal', 'LOW', 'MEDIUM', 'HIGH', ''];

        foreach ($invalidValues as $invalidValue) {
            $payload = array_merge($basePayload, ['priority' => $invalidValue]);

            $response = $this->authenticateWithJWT($user)
                ->postJson('/api/tickets', $payload);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['priority']);
        }
    }

    // ==================== GROUP 4: Response Format (Test 7) ====================

    /**
     * Test #7: Priority is returned in TicketResource
     *
     * Expected: 201 with priority in response
     */
    #[Test]
    public function priority_is_returned_in_ticket_resource(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket para verificar resource',
            'description' => 'Verificar que priority aparece en response.',
            'priority' => 'high',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'ticket_code',
                'priority', // Should be present
                'title',
                'description',
                'status',
            ],
        ]);
        $response->assertJsonPath('data.priority', 'high');
    }

    // ==================== GROUP 5: Database Storage (Test 8) ====================

    /**
     * Test #8: Priority is stored correctly in ENUM column
     *
     * Expected: 201 and database has correct ENUM value
     */
    #[Test]
    public function priority_is_stored_correctly_in_enum_column(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Test all valid priority values
        $priorities = ['low', 'medium', 'high'];

        foreach ($priorities as $priority) {
            $payload = [
                'company_id' => $company->id,
                'category_id' => $category->id,
                'title' => "Ticket con priority {$priority}",
                'description' => "Testing ENUM storage for {$priority}.",
                'priority' => $priority,
            ];

            // Act
            $response = $this->authenticateWithJWT($user)
                ->postJson('/api/tickets', $payload);

            // Assert
            $response->assertStatus(201);

            // Verify ENUM storage
            $this->assertDatabaseHas('ticketing.tickets', [
                'title' => "Ticket con priority {$priority}",
                'priority' => $priority,
            ]);
        }
    }
}
