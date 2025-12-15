<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\AreaIntegration;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Updating Ticket Area
 *
 * Tests the endpoint PATCH /api/tickets/:code with area_id field
 *
 * Coverage:
 * - Can change ticket area_id
 * - Can remove area (set to null)
 * - Area must be active when updating
 * - Area must belong to same company
 * - Area updated is returned in response
 *
 * Database Schema:
 * - ticketing.tickets.area_id (UUID, nullable, FK to business.areas)
 *
 * Validation: Same as create - FK integrity, active status, same company
 */
class UpdateTicketAreaTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Change Area (Tests 1-2) ====================

    /**
     * Test #1: Can change ticket area_id
     *
     * Expected: 200 OK with updated area_id
     * Database: Ticket area_id should be updated
     */
    #[Test]
    public function can_change_ticket_area_id(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $area1 = Area::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name' => 'Area 1',
        ]);

        $area2 = Area::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name' => 'Area 2',
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'area_id' => $area1->id,
        ]);

        $payload = ['area_id' => $area2->id];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.area_id', $area2->id);

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'area_id' => $area2->id,
        ]);
    }

    /**
     * Test #2: USER (creator) can change area when ticket is OPEN
     *
     * Expected: 200 OK
     */
    #[Test]
    public function user_creator_can_change_area_when_ticket_is_open(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $area1 = Area::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $area2 = Area::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'area_id' => $area1->id,
        ]);

        $payload = ['area_id' => $area2->id];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.area_id', $area2->id);
    }

    // ==================== GROUP 2: Remove Area (Test 3) ====================

    /**
     * Test #3: Can remove area (set to null)
     *
     * Expected: 200 OK with area_id = null
     */
    #[Test]
    public function can_remove_area_set_to_null(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $area = Area::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'area_id' => $area->id,
        ]);

        $payload = ['area_id' => null];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.area_id', null);

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'area_id' => null,
        ]);
    }

    // ==================== GROUP 3: Validation (Tests 4-6) ====================

    /**
     * Test #4: Area must be active when updating
     *
     * Expected: 422 Unprocessable Entity
     */
    #[Test]
    public function area_must_be_active_when_updating(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $activeArea = Area::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $inactiveArea = Area::factory()->create(['company_id' => $company->id, 'is_active' => false]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'area_id' => $activeArea->id,
        ]);

        $payload = ['area_id' => $inactiveArea->id];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['area_id']);

        // Verify area unchanged
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'area_id' => $activeArea->id,
        ]);
    }

    /**
     * Test #5: Area must belong to same company
     *
     * Expected: 422 with validation error
     */
    #[Test]
    public function area_must_belong_to_same_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $companyA->id);

        $user = User::factory()->withRole('USER')->create();
        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);

        $areaA = Area::factory()->create(['company_id' => $companyA->id, 'is_active' => true]);
        $areaB = Area::factory()->create(['company_id' => $companyB->id, 'is_active' => true]);

        $ticket = Ticket::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'area_id' => $areaA->id,
        ]);

        $payload = ['area_id' => $areaB->id]; // From Company B

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['area_id']);

        // Verify area unchanged
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'area_id' => $areaA->id,
        ]);
    }

    /**
     * Test #6: Invalid UUID for area_id fails validation
     *
     * Expected: 422 Unprocessable Entity
     */
    #[Test]
    public function invalid_uuid_for_area_id_fails_validation(): void
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
        ]);

        $fakeAreaId = Str::uuid()->toString();
        $payload = ['area_id' => $fakeAreaId];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['area_id']);
    }

    // ==================== GROUP 4: Response (Test 7) ====================

    /**
     * Test #7: Updated area is returned in response
     *
     * Expected: 200 OK with area object in response
     */
    #[Test]
    public function updated_area_is_returned_in_response(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $area1 = Area::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $area2 = Area::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name' => 'New Area Name',
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'area_id' => $area1->id,
        ]);

        $payload = ['area_id' => $area2->id];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.area_id', $area2->id);

        // Verify area object if loaded
        if ($response->json('data.area')) {
            $response->assertJsonPath('data.area.id', $area2->id);
            $response->assertJsonPath('data.area.name', 'New Area Name');
        }
    }

    // ==================== GROUP 5: Partial Update (Test 8) ====================

    /**
     * Test #8: Can update area without changing other fields
     *
     * Expected: 200 OK with only area changed
     */
    #[Test]
    public function can_update_area_without_changing_other_fields(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $area1 = Area::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $area2 = Area::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'title' => 'Original Title',
            'description' => 'Original Description',
            'priority' => 'high',
            'area_id' => $area1->id,
        ]);

        $payload = ['area_id' => $area2->id]; // Only updating area

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.area_id', $area2->id);
        $response->assertJsonPath('data.title', 'Original Title');
        $response->assertJsonPath('data.description', 'Original Description');
        $response->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'area_id' => $area2->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'priority' => 'high',
        ]);
    }
}
