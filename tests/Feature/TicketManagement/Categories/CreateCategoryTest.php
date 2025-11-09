<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Categories;

use App\Features\TicketManagement\Models\Category;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating Ticket Categories
 *
 * Tests the endpoint POST /api/v1/tickets/categories
 *
 * Coverage:
 * - Authentication (unauthenticated, USER, COMPANY_ADMIN)
 * - Required fields validation (name)
 * - Name validation (length 3-100, uniqueness per company)
 * - Description validation (optional, max 500)
 * - Company ID inference from JWT token
 * - Permissions by role (only COMPANY_ADMIN can create)
 *
 * Expected Status Codes:
 * - 201: Category created successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (USER role)
 * - 422: Validation errors
 *
 * Database Schema: ticketing.categories
 * - id: UUID (auto-generated)
 * - company_id: UUID (from JWT, FK to business.companies)
 * - name: VARCHAR(100) NOT NULL
 * - description: TEXT
 * - is_active: BOOLEAN DEFAULT TRUE
 * - created_at: TIMESTAMPTZ
 *
 * CONSTRAINT: uq_company_category_name UNIQUE (company_id, name)
 */
class CreateCategoryTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication (Tests 1-3) ====================

    /**
     * Test #1: Unauthenticated user cannot create category
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     * Database: No category should be created
     */
    #[Test]
    public function unauthenticated_user_cannot_create_category(): void
    {
        // Arrange
        $payload = [
            'name' => 'Soporte Técnico',
            'description' => 'Problemas técnicos del sistema',
        ];

        // Act - No authenticateWithJWT() call
        $response = $this->postJson('/api/v1/tickets/categories', $payload);

        // Assert
        $response->assertStatus(401);

        $this->assertDatabaseMissing('ticketing.categories', [
            'name' => 'Soporte Técnico',
        ]);
    }

    /**
     * Test #2: User cannot create category
     *
     * Verifies that users with USER role are forbidden from creating categories.
     * Only COMPANY_ADMIN role should be able to create categories.
     *
     * Expected: 403 Forbidden
     * Database: No category should be created
     */
    #[Test]
    public function user_cannot_create_category(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        $payload = [
            'name' => 'Categoría por Usuario',
            'description' => 'No debería permitirse',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets/categories', $payload);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseMissing('ticketing.categories', [
            'name' => 'Categoría por Usuario',
        ]);
    }

    /**
     * Test #3: Company admin can create category
     *
     * Verifies that users with COMPANY_ADMIN role can successfully create categories.
     *
     * Expected: 201 Created with category data
     * Database: Category should be persisted with correct company_id
     * Response: Should include id, company_id, name, description, is_active, created_at
     */
    #[Test]
    public function company_admin_can_create_category(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $payload = [
            'name' => 'Soporte Técnico',
            'description' => 'Problemas técnicos del sistema',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Soporte Técnico');
        $response->assertJsonPath('data.description', 'Problemas técnicos del sistema');
        $response->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('ticketing.categories', [
            'name' => 'Soporte Técnico',
            'description' => 'Problemas técnicos del sistema',
            'is_active' => true,
        ]);
    }

    // ==================== GROUP 2: Validation - Required Fields (Test 4) ====================

    /**
     * Test #4: Validates name is required
     *
     * Verifies that the 'name' field is required when creating a category.
     *
     * Expected: 422 Unprocessable Entity with validation error for 'name'
     * Database: No category should be created
     */
    #[Test]
    public function validates_name_is_required(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Empty payload (missing name)
        $payload = [
            'description' => 'Category without name',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // ==================== GROUP 3: Validation - Name Length (Test 5) ====================

    /**
     * Test #5: Validates name length (min 3, max 100)
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
            ->postJson('/api/v1/tickets/categories', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');

        // Case 2: Name too long (101 chars, max is 100)
        $payload = ['name' => str_repeat('A', 101)];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');

        // Case 3: Valid name length (between 3 and 100)
        $payload = ['name' => 'Categoría Válida'];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload);
        $response->assertStatus(201);
    }

    // ==================== GROUP 4: Validation - Name Uniqueness (Tests 6-7) ====================

    /**
     * Test #6: Validates name is unique per company
     *
     * Verifies that category names must be unique within the same company.
     * Attempting to create a category with a duplicate name in the same company should fail.
     *
     * Expected: 422 with validation error for 'name' (duplicate)
     * Database: Only one category with that name should exist for the company
     */
    #[Test]
    public function validates_name_is_unique_per_company(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create first category
        $payload = ['name' => 'Soporte Técnico'];
        $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload)
            ->assertStatus(201);

        // Act - Try to create duplicate category in same company
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test #7: Name uniqueness is per company, not global
     *
     * Verifies that category names only need to be unique within each company,
     * not globally across all companies. Two different companies can have
     * categories with the same name.
     *
     * Expected: 201 for both categories (different companies)
     * Database: Two categories with same name but different company_id should exist
     */
    #[Test]
    public function name_uniqueness_is_per_company_not_global(): void
    {
        // Arrange
        $adminCompanyA = $this->createCompanyAdmin();
        $adminCompanyB = $this->createCompanyAdmin();

        $payload = ['name' => 'Soporte Técnico'];

        // Act - Create category in Company A
        $responseA = $this->authenticateWithJWT($adminCompanyA)
            ->postJson('/api/v1/tickets/categories', $payload);

        // Act - Create category with same name in Company B
        $responseB = $this->authenticateWithJWT($adminCompanyB)
            ->postJson('/api/v1/tickets/categories', $payload);

        // Assert - Both should succeed
        $responseA->assertStatus(201);
        $responseB->assertStatus(201);

        // Verify both categories exist with same name but different companies
        $this->assertDatabaseCount('ticketing.categories', 2);
    }

    // ==================== GROUP 5: Validation - Description (Test 8) ====================

    /**
     * Test #8: Description is optional but validated
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
            ->postJson('/api/v1/tickets/categories', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('data.description', null);

        // Case 2: Description too long (501 chars, max is 500)
        $payload = [
            'name' => 'Descripción Muy Larga',
            'description' => str_repeat('A', 501),
        ];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('description');

        // Case 3: Valid description length (200 chars)
        $payload = [
            'name' => 'Con Descripción Válida',
            'description' => str_repeat('Descripción válida. ', 10), // ~200 chars
        ];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload);
        $response->assertStatus(201);
    }

    // ==================== GROUP 6: Company ID Inference (Test 9) ====================

    /**
     * Test #9: Company ID is inferred from JWT token
     *
     * Verifies that company_id is extracted from the JWT token (immutable):
     * - Request does NOT include company_id in payload
     * - Response should contain company_id matching the authenticated admin's company
     * - If request tries to pass a different company_id, it should be IGNORED
     *
     * Expected: company_id matches the admin's company from JWT
     * Database: Category should be created with company_id from JWT, not from payload
     */
    #[Test]
    public function company_id_is_inferred_from_jwt_token(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Get admin's company ID from their role
        $adminCompanyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $payload = [
            'name' => 'Categoría con Company ID del JWT',
            'description' => 'Testing company_id extraction from JWT',
            // Intentionally NOT including company_id in payload
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.company_id', $adminCompanyId);

        $this->assertDatabaseHas('ticketing.categories', [
            'name' => 'Categoría con Company ID del JWT',
            'company_id' => $adminCompanyId,
        ]);

        // Additional test: Try to pass a different company_id (should be IGNORED)
        $fakeCompanyId = Str::uuid()->toString();
        $payload = [
            'name' => 'Intento Otro Company ID',
            'company_id' => $fakeCompanyId, // Should be ignored
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $payload);

        // Response should use company_id from JWT, NOT the one in payload
        if ($response->status() === 201) {
            $response->assertJsonPath('data.company_id', $adminCompanyId);
            $this->assertDatabaseHas('ticketing.categories', [
                'name' => 'Intento Otro Company ID',
                'company_id' => $adminCompanyId, // From JWT, not payload
            ]);
        }
    }
}
