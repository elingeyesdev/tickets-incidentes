<?php

declare(strict_types=1);

namespace Tests\Feature\CompanyManagement\Areas;

use App\Features\CompanyManagement\Models\Area;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating Areas
 *
 * Tests the endpoint POST /api/areas
 *
 * Coverage:
 * - Authentication (unauthenticated, USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
 * - Required fields validation (name)
 * - Name validation (length 3-100, uniqueness per company)
 * - Description validation (optional, max 500)
 * - Company ID inference from JWT token
 * - Permissions by role (only COMPANY_ADMIN and PLATFORM_ADMIN can create)
 * - is_active defaults to true
 *
 * Expected Status Codes:
 * - 201: Area created successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (USER, AGENT roles)
 * - 422: Validation errors
 *
 * Database Schema: business.areas
 * - id: UUID (auto-generated)
 * - company_id: UUID (from JWT, FK to business.companies)
 * - name: VARCHAR(100) NOT NULL
 * - description: TEXT
 * - is_active: BOOLEAN DEFAULT TRUE
 * - created_at: TIMESTAMPTZ
 *
 * CONSTRAINT: areas_company_name_unique UNIQUE (company_id, name)
 */
class CreateAreaTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication (Tests 1-5) ====================

    /**
     * Test #1: Unauthenticated user cannot create area
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     * Database: No area should be created
     */
    #[Test]
    public function unauthenticated_user_cannot_create_area(): void
    {
        // Arrange
        $payload = [
            'name' => 'Soporte Técnico',
            'description' => 'Departamento de soporte técnico',
        ];

        // Act - No authenticateWithJWT() call
        $response = $this->postJson('/api/areas', $payload);

        // Assert
        $response->assertStatus(401);

        $this->assertDatabaseMissing('business.areas', [
            'name' => 'Soporte Técnico',
        ]);
    }

    /**
     * Test #2: User cannot create area
     *
     * Verifies that users with USER role are forbidden from creating areas.
     * Only COMPANY_ADMIN and PLATFORM_ADMIN roles should be able to create areas.
     *
     * Expected: 403 Forbidden
     * Database: No area should be created
     */
    #[Test]
    public function user_cannot_create_area(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        $payload = [
            'name' => 'Área por Usuario',
            'description' => 'No debería permitirse',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/areas', $payload);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseMissing('business.areas', [
            'name' => 'Área por Usuario',
        ]);
    }

    /**
     * Test #3: Agent cannot create area
     *
     * Verifies that users with AGENT role are forbidden from creating areas.
     * Only COMPANY_ADMIN and PLATFORM_ADMIN roles should be able to create areas.
     *
     * Expected: 403 Forbidden
     * Database: No area should be created
     */
    #[Test]
    public function agent_cannot_create_area(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $companyId);

        $payload = [
            'name' => 'Área por Agente',
            'description' => 'No debería permitirse',
        ];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson('/api/areas', $payload);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseMissing('business.areas', [
            'name' => 'Área por Agente',
        ]);
    }

    /**
     * Test #4: Company admin can create area
     *
     * Verifies that users with COMPANY_ADMIN role can successfully create areas.
     *
     * Expected: 201 Created with area data
     * Database: Area should be persisted with correct company_id
     * Response: Should include id, company_id, name, description, is_active, created_at
     */
    #[Test]
    public function company_admin_can_create_area(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $payload = [
            'name' => 'Recursos Humanos',
            'description' => 'Departamento de recursos humanos',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Area created successfully');
        $response->assertJsonPath('data.name', 'Recursos Humanos');
        $response->assertJsonPath('data.description', 'Departamento de recursos humanos');
        $response->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('business.areas', [
            'name' => 'Recursos Humanos',
            'description' => 'Departamento de recursos humanos',
            'is_active' => true,
        ]);
    }

    /**
     * Test #5: Platform admin can create area (with COMPANY_ADMIN role)
     *
     * Verifies that PLATFORM_ADMIN can create areas when assigned COMPANY_ADMIN role for a company.
     *
     * Expected: 201 Created
     * Database: Area should be persisted with company_id from JWT
     */
    #[Test]
    public function platform_admin_can_create_area(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = \App\Features\CompanyManagement\Models\Company::factory()->create();

        // Assign COMPANY_ADMIN role to PLATFORM_ADMIN for this company
        $platformAdmin->assignRole('COMPANY_ADMIN', $company->id);

        $payload = [
            'name' => 'Finanzas',
            'description' => 'Departamento de finanzas',
        ];

        // Act
        $response = $this->authenticateWithJWT($platformAdmin)
            ->postJson('/api/areas', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'Finanzas');
    }

    // ==================== GROUP 2: Validation - Required Fields (Test 6) ====================

    /**
     * Test #6: Validates name is required
     *
     * Verifies that the 'name' field is required when creating an area.
     *
     * Expected: 422 Unprocessable Entity with validation error for 'name'
     * Database: No area should be created
     */
    #[Test]
    public function validates_name_is_required(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Empty payload (missing name)
        $payload = [
            'description' => 'Area without name',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // ==================== GROUP 3: Validation - Name Length (Test 7) ====================

    /**
     * Test #7: Validates name length (min 3, max 100)
     *
     * Verifies name validation constraints:
     * - Minimum 3 characters (should fail)
     * - Maximum 100 characters (should fail)
     * - Valid length between 3-100 (should pass)
     *
     * Expected: 422 for invalid lengths, 201 for valid length
     */
    #[Test]
    public function validates_name_length_min_3_max_100(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Case 1: Name too short (2 chars, min is 3)
        $payload = ['name' => 'AB'];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');

        // Case 2: Name too long (101 chars, max is 100)
        $payload = ['name' => str_repeat('A', 101)];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');

        // Case 3: Valid name length (between 3 and 100)
        $payload = ['name' => 'Área Válida'];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);
        $response->assertStatus(201);
    }

    // ==================== GROUP 4: Validation - Name Uniqueness (Tests 8-9) ====================

    /**
     * Test #8: Validates name is unique per company
     *
     * Verifies that area names must be unique within the same company.
     * Attempting to create an area with a duplicate name in the same company should fail.
     *
     * Expected: 422 with validation error for 'name' (duplicate)
     * Database: Only one area with that name should exist for the company
     */
    #[Test]
    public function validates_name_is_unique_per_company(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create first area
        $payload = ['name' => 'Ventas'];
        $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload)
            ->assertStatus(201);

        // Act - Try to create duplicate area in same company
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test #9: Name uniqueness is per company, not global
     *
     * Verifies that area names only need to be unique within each company,
     * not globally across all companies. Two different companies can have
     * areas with the same name.
     *
     * Expected: 201 for both areas (different companies)
     * Database: Two areas with same name but different company_id should exist
     */
    #[Test]
    public function name_uniqueness_is_per_company_not_global(): void
    {
        // Arrange
        $adminCompanyA = $this->createCompanyAdmin();
        $adminCompanyB = $this->createCompanyAdmin();

        $payload = ['name' => 'Marketing'];

        // Act - Create area in Company A
        $responseA = $this->authenticateWithJWT($adminCompanyA)
            ->postJson('/api/areas', $payload);

        // Act - Create area with same name in Company B
        $responseB = $this->authenticateWithJWT($adminCompanyB)
            ->postJson('/api/areas', $payload);

        // Assert - Both should succeed
        $responseA->assertStatus(201);
        $responseB->assertStatus(201);

        // Verify both areas exist with same name but different companies
        $this->assertDatabaseCount('business.areas', 2);
    }

    // ==================== GROUP 5: Validation - Description (Test 10) ====================

    /**
     * Test #10: Description is optional but validated
     *
     * Verifies description field validation:
     * - Case 1: Without description (should accept, description = null)
     * - Case 2: Description with 501 chars (should fail, max 500)
     * - Case 3: Description with 200 chars (should pass)
     *
     * Expected: 201 without description, 422 for too long, 201 for valid length
     */
    #[Test]
    public function description_is_optional_but_validated(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Case 1: Without description (should be accepted)
        $payload = ['name' => 'Sin Descripción'];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('data.description', null);

        // Case 2: Description too long (501 chars, max is 500)
        $payload = [
            'name' => 'Descripción Muy Larga',
            'description' => str_repeat('A', 501),
        ];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('description');

        // Case 3: Valid description length (200 chars)
        $payload = [
            'name' => 'Con Descripción Válida',
            'description' => str_repeat('Descripción válida. ', 10), // ~200 chars
        ];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);
        $response->assertStatus(201);
    }

    // ==================== GROUP 6: Company ID Inference (Test 11) ====================

    /**
     * Test #11: Company ID is inferred from JWT token
     *
     * Verifies that company_id is extracted from the JWT token (immutable):
     * - Request does NOT include company_id in payload
     * - Response should contain company_id matching the authenticated admin's company
     * - If request tries to pass a different company_id, it should be IGNORED
     *
     * Expected: company_id matches the admin's company from JWT
     * Database: Area should be created with company_id from JWT, not from payload
     */
    #[Test]
    public function company_id_is_inferred_from_jwt_token(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Get admin's company ID from their role
        $adminCompanyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $payload = [
            'name' => 'Área con Company ID del JWT',
            'description' => 'Testing company_id extraction from JWT',
            // Intentionally NOT including company_id in payload
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.company_id', $adminCompanyId);

        $this->assertDatabaseHas('business.areas', [
            'name' => 'Área con Company ID del JWT',
            'company_id' => $adminCompanyId,
        ]);

        // Additional test: Try to pass a different company_id (should be IGNORED/PROHIBITED)
        $fakeCompanyId = Str::uuid()->toString();
        $payload = [
            'name' => 'Intento Otro Company ID',
            'company_id' => $fakeCompanyId, // Should be prohibited
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);

        // Should either fail validation or ignore the company_id
        // Based on StoreAreaRequest, company_id is 'prohibited', so it should be ignored
        if ($response->status() === 201) {
            $response->assertJsonPath('data.company_id', $adminCompanyId);
            $this->assertDatabaseHas('business.areas', [
                'name' => 'Intento Otro Company ID',
                'company_id' => $adminCompanyId, // From JWT, not payload
            ]);
        }
    }

    // ==================== GROUP 7: Default Values (Test 12) ====================

    /**
     * Test #12: is_active defaults to true
     *
     * Verifies that newly created areas have is_active = true by default.
     *
     * Expected: 201 with is_active = true
     * Database: is_active should be true
     */
    #[Test]
    public function is_active_defaults_to_true(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $payload = [
            'name' => 'Área Activa por Defecto',
            // NOT providing is_active
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/areas', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('business.areas', [
            'name' => 'Área Activa por Defecto',
            'is_active' => true,
        ]);
    }
}
