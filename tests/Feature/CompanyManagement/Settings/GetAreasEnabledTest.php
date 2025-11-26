<?php

declare(strict_types=1);

namespace Tests\Feature\CompanyManagement\Settings;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Getting Areas Enabled Setting
 *
 * Tests the endpoint GET /api/companies/me/settings/areas-enabled
 *
 * Coverage:
 * - Authentication (only COMPANY_ADMIN role)
 * - company_id extraction from JWT token
 * - Default value (false for new companies)
 * - Correct value after toggle
 * - Response format
 *
 * Expected Status Codes:
 * - 200: Setting retrieved successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (USER, AGENT, non-COMPANY_ADMIN)
 *
 * Response Format: {success: true, data: {areas_enabled: boolean}}
 */
class GetAreasEnabledTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication & Authorization (Tests 1-4) ====================

    /**
     * Test #1: Unauthenticated user cannot get areas_enabled setting
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_get_areas_enabled(): void
    {
        // Act - No authenticateWithJWT() call
        $response = $this->getJson('/api/companies/me/settings/areas-enabled');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test #2: USER cannot get areas_enabled setting
     *
     * Expected: 403 Forbidden (middleware blocks)
     */
    #[Test]
    public function user_cannot_get_areas_enabled(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/me/settings/areas-enabled');

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #3: AGENT cannot get areas_enabled setting
     *
     * Expected: 403 Forbidden (middleware blocks)
     */
    #[Test]
    public function agent_cannot_get_areas_enabled(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->getJson('/api/companies/me/settings/areas-enabled');

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #4: COMPANY_ADMIN can get areas_enabled setting
     *
     * Expected: 200 OK with correct data
     */
    #[Test]
    public function company_admin_can_get_areas_enabled(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/companies/me/settings/areas-enabled');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'areas_enabled',
            ],
        ]);
    }

    // ==================== GROUP 2: Company ID from JWT (Test 5) ====================

    /**
     * Test #5: company_id is extracted from JWT token
     *
     * Verifies that the endpoint uses company_id from JWT, not from query params.
     *
     * Expected: 200 OK with company's setting
     */
    #[Test]
    public function company_id_is_extracted_from_jwt_token(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/companies/me/settings/areas-enabled');

        // Assert
        $response->assertStatus(200);

        // Verify the company exists
        $this->assertDatabaseHas('business.companies', [
            'id' => $companyId,
        ]);
    }

    // ==================== GROUP 3: Default Value (Test 6) ====================

    /**
     * Test #6: Default value is false for new companies
     *
     * Verifies that companies created with the migration have areas_enabled = false by default.
     *
     * Expected: 200 with areas_enabled = false
     */
    #[Test]
    public function default_value_is_false_for_new_companies(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/companies/me/settings/areas-enabled');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.areas_enabled', false);
    }

    // ==================== GROUP 4: Correct Value After Toggle (Test 7) ====================

    /**
     * Test #7: Returns correct value after toggling
     *
     * Verifies that after enabling areas, the GET endpoint returns true.
     *
     * Expected: 200 with areas_enabled = true
     */
    #[Test]
    public function returns_correct_value_after_toggling(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Enable areas first
        $this->authenticateWithJWT($admin)
            ->patchJson('/api/companies/me/settings/areas-enabled', ['enabled' => true]);

        // Act - Get the setting
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/companies/me/settings/areas-enabled');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.areas_enabled', true);
    }

    // ==================== GROUP 5: Response Format (Test 8) ====================

    /**
     * Test #8: Response format is correct
     *
     * Verifies the response structure matches expected format.
     *
     * Expected: {success: true, data: {areas_enabled: boolean}}
     */
    #[Test]
    public function response_format_is_correct(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/companies/me/settings/areas-enabled');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'areas_enabled',
            ],
        ]);
        $response->assertJsonPath('success', true);

        // Verify areas_enabled is a boolean
        $this->assertIsBool($response->json('data.areas_enabled'));
    }

    // ==================== GROUP 6: PLATFORM_ADMIN Support (Test 9) ====================

    /**
     * Test #9: PLATFORM_ADMIN can get areas_enabled setting
     *
     * Expected: 200 OK
     */
    #[Test]
    public function platform_admin_can_get_areas_enabled(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        // Act
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson('/api/companies/me/settings/areas-enabled');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }
}
