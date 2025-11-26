<?php

declare(strict_types=1);

namespace Tests\Feature\CompanyManagement\Areas;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Updating Areas
 *
 * Tests the endpoint PUT /api/areas/{id}
 *
 * Coverage:
 * - Authentication (unauthenticated, USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
 * - Authorization (only same company COMPANY_ADMIN or PLATFORM_ADMIN)
 * - Field updates (name, description, is_active)
 * - Name uniqueness validation per company
 * - company_id cannot be changed
 *
 * Expected Status Codes:
 * - 200: Area updated successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions or wrong company
 * - 404: Area not found
 * - 422: Validation errors
 *
 * Database Schema: business.areas
 */
class UpdateAreaTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication & Authorization (Tests 1-7) ====================

    /**
     * Test #1: Unauthenticated user cannot update area
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_update_area(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $area = Area::factory()->create(['company_id' => $company->id, 'name' => 'Original Name']);

        $payload = ['name' => 'Updated Name'];

        // Act - No authenticateWithJWT() call
        $response = $this->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(401);

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
            'name' => 'Original Name',
        ]);
    }

    /**
     * Test #2: USER cannot update area
     *
     * Expected: 403 Forbidden (middleware blocks)
     */
    #[Test]
    public function user_cannot_update_area(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $area = Area::factory()->create(['company_id' => $company->id, 'name' => 'Original Name']);
        $user = User::factory()->withRole('USER')->create();

        $payload = ['name' => 'Updated Name'];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
            'name' => 'Original Name',
        ]);
    }

    /**
     * Test #3: AGENT cannot update area
     *
     * Expected: 403 Forbidden (middleware blocks)
     */
    #[Test]
    public function agent_cannot_update_area(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $area = Area::factory()->create(['company_id' => $company->id, 'name' => 'Original Name']);
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $payload = ['name' => 'Updated Name'];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
            'name' => 'Original Name',
        ]);
    }

    /**
     * Test #4: COMPANY_ADMIN can update area of their company
     *
     * Expected: 200 OK with updated data
     */
    #[Test]
    public function company_admin_can_update_area_of_their_company(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create([
            'company_id' => $companyId,
            'name' => 'Original Name',
            'description' => 'Original Description',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'is_active' => false,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Area updated successfully');
        $response->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonPath('data.description', 'Updated Description');
        $response->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'is_active' => false,
        ]);
    }

    /**
     * Test #5: COMPANY_ADMIN cannot update area of another company
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function company_admin_cannot_update_area_of_another_company(): void
    {
        // Arrange
        $adminCompanyA = $this->createCompanyAdmin();
        $companyB = Company::factory()->create();

        $area = Area::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Original Name',
        ]);

        $payload = ['name' => 'Updated Name'];

        // Act
        $response = $this->authenticateWithJWT($adminCompanyA)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'You do not have permission to update this area');

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
            'name' => 'Original Name',
        ]);
    }

    /**
     * Test #6: PLATFORM_ADMIN can update area (with COMPANY_ADMIN role)
     *
     * Expected: 200 OK
     */
    #[Test]
    public function platform_admin_can_update_area_of_any_company(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        // Assign COMPANY_ADMIN role to PLATFORM_ADMIN for this company
        $platformAdmin->assignRole('COMPANY_ADMIN', $company->id);

        $area = Area::factory()->create([
            'company_id' => $company->id,
            'name' => 'Original Name',
        ]);

        $payload = ['name' => 'Updated by Platform Admin'];

        // Act
        $response = $this->authenticateWithJWT($platformAdmin)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated by Platform Admin');
    }

    /**
     * Test #7: Area not found returns 404
     *
     * Expected: 404 Not Found
     */
    #[Test]
    public function area_not_found_returns_404(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $fakeId = Str::uuid()->toString();

        $payload = ['name' => 'Updated Name'];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$fakeId}", $payload);

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Area not found');
    }

    // ==================== GROUP 2: Field Updates (Tests 8-10) ====================

    /**
     * Test #8: Can update name only
     *
     * Expected: 200 with name updated, other fields unchanged
     */
    #[Test]
    public function can_update_name_only(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create([
            'company_id' => $companyId,
            'name' => 'Original Name',
            'description' => 'Original Description',
            'is_active' => true,
        ]);

        $payload = ['name' => 'New Name Only'];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'New Name Only');
        $response->assertJsonPath('data.description', 'Original Description');
        $response->assertJsonPath('data.is_active', true);
    }

    /**
     * Test #9: Can update description only
     *
     * Expected: 200 with description updated
     */
    #[Test]
    public function can_update_description_only(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create([
            'company_id' => $companyId,
            'name' => 'Area Name',
            'description' => 'Original Description',
        ]);

        $payload = ['description' => 'New Description'];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.description', 'New Description');
        $response->assertJsonPath('data.name', 'Area Name');
    }

    /**
     * Test #10: Can update is_active only
     *
     * Expected: 200 with is_active toggled
     */
    #[Test]
    public function can_update_is_active_only(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create([
            'company_id' => $companyId,
            'is_active' => true,
        ]);

        $payload = ['is_active' => false];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.is_active', false);
    }

    // ==================== GROUP 3: Validation (Tests 11-13) ====================

    /**
     * Test #11: Name must be unique per company when updating
     *
     * Expected: 422 if duplicate name in same company
     */
    #[Test]
    public function name_must_be_unique_per_company_when_updating(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area1 = Area::factory()->create([
            'company_id' => $companyId,
            'name' => 'Existing Area',
        ]);

        $area2 = Area::factory()->create([
            'company_id' => $companyId,
            'name' => 'Another Area',
        ]);

        // Try to rename area2 to area1's name
        $payload = ['name' => 'Existing Area'];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area2->id}", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        $this->assertDatabaseHas('business.areas', [
            'id' => $area2->id,
            'name' => 'Another Area',
        ]);
    }

    /**
     * Test #12: Can update area with same name (no change)
     *
     * Expected: 200 (updating with same name is allowed)
     */
    #[Test]
    public function can_update_area_with_same_name(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create([
            'company_id' => $companyId,
            'name' => 'Same Name',
            'description' => 'Old Description',
        ]);

        $payload = [
            'name' => 'Same Name',
            'description' => 'New Description',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.description', 'New Description');
    }

    /**
     * Test #13: Description validates max 500 chars
     *
     * Expected: 422 if description > 500 chars
     */
    #[Test]
    public function description_validates_max_500_chars(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create([
            'company_id' => $companyId,
            'name' => 'Test Area',
        ]);

        $payload = ['description' => str_repeat('A', 501)];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    }

    // ==================== GROUP 4: company_id Immutability (Test 14) ====================

    /**
     * Test #14: company_id cannot be changed
     *
     * Expected: company_id should remain unchanged even if provided in payload
     */
    #[Test]
    public function company_id_cannot_be_changed(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create([
            'company_id' => $companyId,
            'name' => 'Test Area',
        ]);

        $fakeCompanyId = Str::uuid()->toString();
        $payload = [
            'name' => 'Updated Name',
            'company_id' => $fakeCompanyId, // Try to change company_id
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);

        // Assert
        // company_id is prohibited field - validation fails if provided
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['company_id']);

        // Verify area was not updated
        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
            'company_id' => $companyId,
            'name' => 'Test Area', // Original name should remain
        ]);
    }

    // ==================== GROUP 5: Name Length Validation (Test 15) ====================

    /**
     * Test #15: Name validates min 3 max 100 chars
     *
     * Expected: 422 for invalid lengths
     */
    #[Test]
    public function name_validates_min_3_max_100_chars(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create([
            'company_id' => $companyId,
            'name' => 'Valid Name',
        ]);

        // Case 1: Too short (2 chars)
        $payload = ['name' => 'AB'];
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');

        // Case 2: Too long (101 chars)
        $payload = ['name' => str_repeat('A', 101)];
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');

        // Case 3: Valid (50 chars)
        $payload = ['name' => str_repeat('A', 50)];
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/areas/{$area->id}", $payload);
        $response->assertStatus(200);
    }
}
