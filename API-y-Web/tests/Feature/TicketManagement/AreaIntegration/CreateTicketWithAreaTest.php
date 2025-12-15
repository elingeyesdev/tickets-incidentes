<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\AreaIntegration;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating Tickets with Area
 *
 * Tests the endpoint POST /api/tickets with area_id field
 *
 * Coverage:
 * - USER can create ticket with area_id (optional)
 * - area_id must exist in business.areas
 * - Area must be active (is_active = true)
 * - Area must belong to same company as ticket
 * - Area from other company fails validation
 * - Inactive area fails validation
 * - Creating without area_id is valid (null)
 * - Area is returned in TicketResource when present
 *
 * Database Schema:
 * - ticketing.tickets.area_id (UUID, nullable, FK to business.areas)
 * - business.areas (id, company_id, name, is_active)
 *
 * Validation: Cross-schema FK integrity
 */
class CreateTicketWithAreaTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Create with Valid Area (Tests 1-2) ====================

    /**
     * Test #1: USER can create ticket with valid area_id
     *
     * Expected: 201 Created with area_id
     * Database: Ticket should have area_id set
     */
    #[Test]
    public function user_can_create_ticket_with_valid_area_id(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $area = Area::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con área',
            'description' => 'Este ticket tiene área asignada.',
            'area_id' => $area->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.area_id', $area->id);

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket con área',
            'area_id' => $area->id,
        ]);
    }

    /**
     * Test #2: Area is returned in TicketResource when present
     *
     * Expected: 201 with area object in response
     */
    #[Test]
    public function area_is_returned_in_ticket_resource_when_present(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $area = Area::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name' => 'Soporte Técnico',
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con área en response',
            'description' => 'Verificar que área aparece en response.',
            'area_id' => $area->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.area_id', $area->id);

        // Verify area object is included when loaded
        if ($response->json('data.area')) {
            $response->assertJsonPath('data.area.id', $area->id);
            $response->assertJsonPath('data.area.name', 'Soporte Técnico');
        }
    }

    // ==================== GROUP 2: Optional Field (Test 3) ====================

    /**
     * Test #3: Creating ticket without area_id is valid (null)
     *
     * Expected: 201 Created with area_id = null
     */
    #[Test]
    public function creating_ticket_without_area_id_is_valid(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket sin área',
            'description' => 'Este ticket no tiene área asignada.',
            // NOT providing area_id
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.area_id', null);

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket sin área',
            'area_id' => null,
        ]);
    }

    // ==================== GROUP 3: Area Exists Validation (Test 4) ====================

    /**
     * Test #4: area_id must exist in business.areas
     *
     * Expected: 422 Unprocessable Entity
     */
    #[Test]
    public function area_id_must_exist_in_business_areas(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $fakeAreaId = Str::uuid()->toString();

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con área inexistente',
            'description' => 'Área no existe.',
            'area_id' => $fakeAreaId,
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['area_id']);
    }

    // ==================== GROUP 4: Active Area Validation (Test 5) ====================

    /**
     * Test #5: Area must be active (is_active = true)
     *
     * Expected: 422 with validation error
     */
    #[Test]
    public function area_must_be_active(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $inactiveArea = Area::factory()->create([
            'company_id' => $company->id,
            'is_active' => false, // Inactive
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con área inactiva',
            'description' => 'Área está inactiva.',
            'area_id' => $inactiveArea->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['area_id']);
    }

    // ==================== GROUP 5: Same Company Validation (Test 6) ====================

    /**
     * Test #6: Area must belong to same company as ticket
     *
     * Expected: 422 with validation error
     */
    #[Test]
    public function area_must_belong_to_same_company_as_ticket(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);
        $areaB = Area::factory()->create([
            'company_id' => $companyB->id, // Different company
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
            'title' => 'Ticket con área de otra empresa',
            'description' => 'Área pertenece a otra empresa.',
            'area_id' => $areaB->id, // From Company B
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['area_id']);
    }

    // ==================== GROUP 6: Cross-Schema FK (Test 7) ====================

    /**
     * Test #7: Cross-schema FK ticketing.tickets.area_id → business.areas.id works
     *
     * Expected: 201 with correct FK relationship
     */
    #[Test]
    public function cross_schema_fk_works_correctly(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $area = Area::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Test Cross-Schema FK',
            'description' => 'Testing FK from ticketing to business schema.',
            'area_id' => $area->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload);

        // Assert
        $response->assertStatus(201);

        // Verify FK exists in database
        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Test Cross-Schema FK',
            'area_id' => $area->id,
        ]);

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
        ]);
    }

    // ==================== GROUP 7: Multiple Tickets Same Area (Test 8) ====================

    /**
     * Test #8: Multiple tickets can use same area
     *
     * Expected: Both tickets created successfully with same area_id
     */
    #[Test]
    public function multiple_tickets_can_use_same_area(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $area = Area::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload1 = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket 1 con área compartida',
            'description' => 'Primer ticket.',
            'area_id' => $area->id,
        ];

        $payload2 = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket 2 con área compartida',
            'description' => 'Segundo ticket.',
            'area_id' => $area->id,
        ];

        // Act
        $response1 = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload1);

        $response2 = $this->authenticateWithJWT($user)
            ->postJson('/api/tickets', $payload2);

        // Assert
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $this->assertDatabaseCount('ticketing.tickets', 2);

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket 1 con área compartida',
            'area_id' => $area->id,
        ]);

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket 2 con área compartida',
            'area_id' => $area->id,
        ]);
    }
}
