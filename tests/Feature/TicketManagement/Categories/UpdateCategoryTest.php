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
 * Feature Tests for Updating Ticket Categories
 *
 * Tests the endpoint PUT /api/v1/tickets/categories/:id
 *
 * Coverage:
 * - Authentication and permissions (COMPANY_ADMIN only)
 * - Updating category fields (name, description, is_active)
 * - Name uniqueness validation on update
 * - Company isolation (cannot update categories from other companies)
 * - Partial updates (preserving unchanged fields)
 * - USER role cannot update
 *
 * Expected Status Codes:
 * - 200: Category updated successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions or accessing other company's category
 * - 404: Category not found
 * - 422: Validation errors (e.g., duplicate name)
 *
 * Database Schema: ticketing.categories
 * - id: UUID
 * - company_id: UUID (immutable)
 * - name: VARCHAR(100) NOT NULL
 * - description: TEXT
 * - is_active: BOOLEAN DEFAULT TRUE
 * - created_at: TIMESTAMPTZ
 *
 * CONSTRAINT: uq_company_category_name UNIQUE (company_id, name)
 */
class UpdateCategoryTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Basic Update Operations (Tests 1-2) ====================

    /**
     * Test #1: Company admin can update category
     *
     * Verifies that COMPANY_ADMIN can successfully update their own company's category.
     *
     * Expected: 200 OK with updated category data
     * Database: Category should be updated with new values
     */
    #[Test]
    public function company_admin_can_update_category(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create a category first
        $createPayload = ['name' => 'Soporte Original'];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Update payload
        $updatePayload = [
            'name' => 'Soporte Actualizado',
            'description' => 'Nueva descripción actualizada',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/tickets/categories/{$categoryId}", $updatePayload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Soporte Actualizado');
        $response->assertJsonPath('data.description', 'Nueva descripción actualizada');

        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Soporte Actualizado',
            'description' => 'Nueva descripción actualizada',
        ]);
    }

    /**
     * Test #2: Can toggle is_active status
     *
     * Verifies that is_active field can be toggled between true and false.
     *
     * Expected: 200 OK with updated is_active value
     * Database: is_active should be updated accordingly
     */
    #[Test]
    public function can_toggle_is_active_status(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create a category (default is_active = true)
        $createPayload = ['name' => 'Categoría Activa'];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Act - Deactivate category
        $updatePayload = ['is_active' => false];
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/tickets/categories/{$categoryId}", $updatePayload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.is_active', false);
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'is_active' => false,
        ]);

        // Act - Reactivate category
        $updatePayload = ['is_active' => true];
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/tickets/categories/{$categoryId}", $updatePayload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.is_active', true);
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'is_active' => true,
        ]);
    }

    // ==================== GROUP 2: Validation (Test 3) ====================

    /**
     * Test #3: Validates updated name uniqueness
     *
     * Verifies that when updating a category's name, the new name must still be unique
     * within the same company (excluding the current category).
     *
     * Expected: 422 when trying to use another category's name
     * Database: Original category should remain unchanged
     */
    #[Test]
    public function validates_updated_name_uniqueness(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create two categories
        $category1Response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', ['name' => 'Categoría Uno']);
        $categoryId1 = $category1Response->json('data.id');

        $category2Response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', ['name' => 'Categoría Dos']);
        $categoryId2 = $category2Response->json('data.id');

        // Act - Try to update category 2 with category 1's name
        $updatePayload = ['name' => 'Categoría Uno'];
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/tickets/categories/{$categoryId2}", $updatePayload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        // Verify category 2 still has original name
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId2,
            'name' => 'Categoría Dos',
        ]);
    }

    // ==================== GROUP 3: Company Isolation (Test 4) ====================

    /**
     * Test #4: Cannot update category from different company
     *
     * Verifies that COMPANY_ADMIN cannot update categories belonging to other companies.
     * This ensures proper company isolation and security.
     *
     * Expected: 403 Forbidden or 404 Not Found
     * Database: Category should remain unchanged
     */
    #[Test]
    public function cannot_update_category_from_different_company(): void
    {
        // Arrange
        $adminCompanyA = $this->createCompanyAdmin();
        $adminCompanyB = $this->createCompanyAdmin();

        // Create category in Company A
        $createPayload = ['name' => 'Categoría de Empresa A'];
        $createResponse = $this->authenticateWithJWT($adminCompanyA)
            ->postJson('/api/v1/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Act - Admin from Company B tries to update category from Company A
        $updatePayload = ['name' => 'Intento de Actualización'];
        $response = $this->authenticateWithJWT($adminCompanyB)
            ->putJson("/api/v1/tickets/categories/{$categoryId}", $updatePayload);

        // Assert - Should be forbidden or not found
        $this->assertContains($response->status(), [403, 404]);

        // Verify category remains unchanged
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Categoría de Empresa A',
        ]);
    }

    // ==================== GROUP 4: Partial Updates (Test 5) ====================

    /**
     * Test #5: Partial update preserves unchanged fields
     *
     * Verifies that when updating only some fields, other fields remain unchanged.
     * This tests that partial updates work correctly.
     *
     * Expected: 200 OK with updated fields, unchanged fields preserved
     * Database: Only specified fields should be updated
     */
    #[Test]
    public function partial_update_preserves_unchanged_fields(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create category with all fields
        $createPayload = [
            'name' => 'Categoría Completa',
            'description' => 'Descripción original',
        ];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Act - Update only description
        $updatePayload = ['description' => 'Descripción actualizada'];
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/tickets/categories/{$categoryId}", $updatePayload);

        // Assert - Name should remain unchanged
        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Categoría Completa');
        $response->assertJsonPath('data.description', 'Descripción actualizada');

        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Categoría Completa', // Unchanged
            'description' => 'Descripción actualizada', // Updated
        ]);

        // Act - Update only name
        $updatePayload = ['name' => 'Nuevo Nombre'];
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/tickets/categories/{$categoryId}", $updatePayload);

        // Assert - Description should remain unchanged
        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Nuevo Nombre');
        $response->assertJsonPath('data.description', 'Descripción actualizada');

        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Nuevo Nombre', // Updated
            'description' => 'Descripción actualizada', // Unchanged from previous update
        ]);
    }

    // ==================== GROUP 5: Permissions (Test 6) ====================

    /**
     * Test #6: User cannot update category
     *
     * Verifies that users with USER role cannot update categories.
     * Only COMPANY_ADMIN should have this permission.
     *
     * Expected: 403 Forbidden
     * Database: Category should remain unchanged
     */
    #[Test]
    public function user_cannot_update_category(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $user = User::factory()->withRole('USER')->create();

        // Create category as admin
        $createPayload = ['name' => 'Categoría Original'];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Act - User tries to update
        $updatePayload = ['name' => 'Intento de Usuario'];
        $response = $this->authenticateWithJWT($user)
            ->putJson("/api/v1/tickets/categories/{$categoryId}", $updatePayload);

        // Assert
        $response->assertStatus(403);

        // Verify category remains unchanged
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Categoría Original',
        ]);
    }
}
