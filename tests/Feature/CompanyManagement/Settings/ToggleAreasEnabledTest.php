<?php

declare(strict_types=1);

namespace Tests\Feature\CompanyManagement\Settings;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Toggling Areas Enabled Setting
 *
 * Tests the endpoint PATCH /api/companies/me/settings/areas-enabled
 *
 * Coverage:
 * - Authentication (only COMPANY_ADMIN and PLATFORM_ADMIN)
 * - Authorization via CompanyPolicy->manageAreas()
 * - Enable areas (enabled: true)
 * - Disable areas (enabled: false)
 * - company_id extraction from JWT token
 * - Validation (enabled is required and must be boolean)
 * - Persistence in settings JSONB field
 * - Response includes new state
 *
 * Expected Status Codes:
 * - 200: Setting toggled successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (USER, AGENT)
 * - 422: Validation errors
 *
 * Database: business.companies.settings (JSONB)
 */
class ToggleAreasEnabledTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication & Authorization (Tests 1-5) ====================

    /**
     * Test #1: Unauthenticated user cannot toggle areas_enabled
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_toggle_areas_enabled(): void
    {
        // Arrange
        $payload = ['enabled' => true];

        // Act - No authenticateWithJWT() call
        $response = $this->patchJson('/api/companies/me/settings/areas-enabled', $payload);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test #2: USER cannot toggle areas_enabled
     *
     * Expected: 403 Forbidden (middleware blocks)
     */
    #[Test]
    public function user_cannot_toggle_areas_enabled(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $payload = ['enabled' => true];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #3: AGENT cannot toggle areas_enabled
     *
     * Expected: 403 Forbidden (middleware blocks)
     */
    #[Test]
    public function agent_cannot_toggle_areas_enabled(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $payload = ['enabled' => true];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #4: COMPANY_ADMIN can toggle areas_enabled
     *
     * Expected: 200 OK with updated setting
     */
    #[Test]
    public function company_admin_can_toggle_areas_enabled(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $payload = ['enabled' => true];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.areas_enabled', true);
    }

    /**
     * Test #5: PLATFORM_ADMIN can toggle areas_enabled
     *
     * Expected: 200 OK
     */
    #[Test]
    public function platform_admin_can_toggle_areas_enabled(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $payload = ['enabled' => true];

        // Act
        $response = $this->authenticateWithJWT($platformAdmin)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    // ==================== GROUP 2: Enable Areas (Test 6) ====================

    /**
     * Test #6: Can enable areas (enabled: true)
     *
     * Expected: 200 OK with areas_enabled = true
     * Database: settings.areas_enabled should be true
     */
    #[Test]
    public function can_enable_areas(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $payload = ['enabled' => true];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Areas enabled successfully');
        $response->assertJsonPath('data.areas_enabled', true);

        // Verify in database
        $company = Company::find($companyId);
        $this->assertTrue($company->hasAreasEnabled());
    }

    // ==================== GROUP 3: Disable Areas (Test 7) ====================

    /**
     * Test #7: Can disable areas (enabled: false)
     *
     * Expected: 200 OK with areas_enabled = false
     * Database: settings.areas_enabled should be false
     */
    #[Test]
    public function can_disable_areas(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // First enable
        $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => true]);

        // Then disable
        $payload = ['enabled' => false];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Areas disabled successfully');
        $response->assertJsonPath('data.areas_enabled', false);

        // Verify in database
        $company = Company::find($companyId);
        $this->assertFalse($company->hasAreasEnabled());
    }

    // ==================== GROUP 4: Validation (Tests 8-9) ====================

    /**
     * Test #8: enabled field is required
     *
     * Expected: 422 Unprocessable Entity
     */
    #[Test]
    public function enabled_field_is_required(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Empty payload (missing enabled)
        $payload = [];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['enabled']);
    }

    /**
     * Test #9: enabled must be boolean
     *
     * Expected: 422 for non-boolean values
     */
    #[Test]
    public function enabled_must_be_boolean(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Case 1: String value
        $payload = ['enabled' => 'yes'];
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('enabled');

        // Case 2: Integer value (not boolean)
        $payload = ['enabled' => 1];
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('enabled');

        // Case 3: Null value
        $payload = ['enabled' => null];
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('enabled');
    }

    // ==================== GROUP 5: Company ID from JWT (Test 10) ====================

    /**
     * Test #10: company_id is extracted from JWT token
     *
     * Verifies that the endpoint updates the company from JWT, not query params.
     *
     * Expected: 200 OK with correct company updated
     */
    #[Test]
    public function company_id_is_extracted_from_jwt_token(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $payload = ['enabled' => true];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', $payload);

        // Assert
        $response->assertStatus(200);

        // Verify the correct company was updated
        $company = Company::find($companyId);
        $this->assertTrue($company->hasAreasEnabled());
    }

    // ==================== GROUP 6: Persistence (Test 11) ====================

    /**
     * Test #11: Change persists in settings JSONB field
     *
     * Verifies that areas_enabled is correctly stored in the JSONB settings column.
     *
     * Expected: 200 OK and database has correct JSONB value
     */
    #[Test]
    public function change_persists_in_settings_jsonb_field(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Act - Enable
        $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => true])
            ->assertStatus(200);

        // Assert - Check database
        $company = Company::find($companyId);
        $this->assertNotNull($company->settings);
        $this->assertArrayHasKey('areas_enabled', $company->settings);
        $this->assertTrue($company->settings['areas_enabled']);

        // Act - Disable
        $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => false])
            ->assertStatus(200);

        // Assert - Check database again
        $company->refresh();
        $this->assertFalse($company->settings['areas_enabled']);
    }

    // ==================== GROUP 7: Response Includes New State (Test 12) ====================

    /**
     * Test #12: Response includes the new state
     *
     * Verifies that the response always returns the current state after toggling.
     *
     * Expected: data.areas_enabled matches the enabled value sent
     */
    #[Test]
    public function response_includes_the_new_state(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Act - Enable
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => true]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.areas_enabled', true);

        // Act - Disable
        $response = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => false]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.areas_enabled', false);
    }

    // ==================== GROUP 8: Idempotence (Test 13) ====================

    /**
     * Test #13: Toggling is idempotent
     *
     * Verifies that setting areas_enabled to the same value multiple times works correctly.
     *
     * Expected: 200 OK each time
     */
    #[Test]
    public function toggling_is_idempotent(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Act - Enable twice
        $response1 = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => true]);
        $response2 = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => true]);

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $company = Company::find($companyId);
        $this->assertTrue($company->hasAreasEnabled());

        // Act - Disable twice
        $response3 = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => false]);
        $response4 = $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => false]);

        // Assert
        $response3->assertStatus(200);
        $response4->assertStatus(200);

        $company->refresh();
        $this->assertFalse($company->hasAreasEnabled());
    }
}
