<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Categories;

use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Deleting Ticket Categories
 *
 * Tests the endpoint DELETE /api/tickets/categories/:id
 *
 * Coverage:
 * - Authentication and permissions (COMPANY_ADMIN only)
 * - Deletion of unused categories
 * - Prevention of deletion when category has open tickets
 * - Prevention of deletion when category has pending tickets
 * - Allow deletion when category only has closed tickets
 * - Error messages showing active tickets count
 * - USER role cannot delete
 *
 * Expected Status Codes:
 * - 200: Category deleted successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions
 * - 404: Category not found
 * - 422: Cannot delete (has active tickets)
 *
 * Business Rules:
 * - Category can be deleted if it has NO tickets
 * - Category can be deleted if it ONLY has CLOSED tickets
 * - Category CANNOT be deleted if it has OPEN tickets
 * - Category CANNOT be deleted if it has PENDING tickets
 * - Error message should show count of active tickets preventing deletion
 *
 * Database Schema: ticketing.categories
 * - id: UUID
 * - company_id: UUID
 * - name: VARCHAR(100)
 *
 * Related: ticketing.tickets
 * - status: ENUM (OPEN, PENDING, pending, RESOLVED, CLOSED)
 */
class DeleteCategoryTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Successful Deletions (Tests 1, 4) ====================

    /**
     * Test #1: Company admin can delete unused category
     *
     * Verifies that COMPANY_ADMIN can successfully delete a category that has no tickets.
     *
     * Expected: 200 OK or 204 No Content
     * Database: Category should be removed from database
     */
    #[Test]
    public function company_admin_can_delete_unused_category(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create category
        $createPayload = ['name' => 'Categoría Sin Usar'];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Verify category exists
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Categoría Sin Usar',
        ]);

        // Act - Delete category
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/categories/{$categoryId}");

        // Assert
        $this->assertContains($response->status(), [200, 204]);

        // Verify category is deleted
        $this->assertDatabaseMissing('ticketing.categories', [
            'id' => $categoryId,
        ]);
    }

    /**
     * Test #4: Can delete category with only closed tickets
     *
     * Verifies that a category can be deleted if it only has tickets with status CLOSED.
     * CLOSED tickets are considered inactive and don't prevent deletion.
     *
     * Expected: 200 OK or 204 No Content
     * Database: Category should be deleted despite having closed tickets
     */
    #[Test]
    public function can_delete_category_with_only_closed_tickets(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create category
        $createPayload = ['name' => 'Categoría con Tickets Cerrados'];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Create multiple closed tickets for this category
        $closedTicket1 = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'closed',
        ]);
        $closedTicket2 = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'closed',
        ]);

        // Act - Delete category
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/categories/{$categoryId}");

        // Assert - Should succeed
        $this->assertContains($response->status(), [200, 204]);

        // Verify category is deleted
        $this->assertDatabaseMissing('ticketing.categories', [
            'id' => $categoryId,
        ]);
    }

    // ==================== GROUP 2: Prevented Deletions (Tests 2-3) ====================

    /**
     * Test #2: Cannot delete category with open tickets
     *
     * Verifies that a category cannot be deleted if it has tickets with status OPEN.
     * Open tickets are considered active and prevent deletion.
     *
     * Expected: 422 Unprocessable Entity with error message
     * Database: Category should remain in database
     */
    #[Test]
    public function cannot_delete_category_with_open_tickets(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create category
        $createPayload = ['name' => 'Categoría con Tickets Abiertos'];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Create open ticket for this category
        $openTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'open',
        ]);

        // Act - Try to delete category
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/categories/{$categoryId}");

        // Assert - Should fail
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Cannot delete category with active tickets',
        ]);

        // Verify category still exists
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Categoría con Tickets Abiertos',
        ]);
    }

    /**
     * Test #3: Cannot delete category with pending tickets
     *
     * Verifies that a category cannot be deleted if it has tickets with status PENDING.
     * Pending tickets are considered active and prevent deletion.
     *
     * Expected: 422 Unprocessable Entity with error message
     * Database: Category should remain in database
     */
    #[Test]
    public function cannot_delete_category_with_pending_tickets(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create category
        $createPayload = ['name' => 'Categoría con Tickets Pendientes'];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Create pending ticket for this category
        $pendingTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'pending',
        ]);

        // Act - Try to delete category
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/categories/{$categoryId}");

        // Assert - Should fail
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Cannot delete category with active tickets',
        ]);

        // Verify category still exists
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Categoría con Tickets Pendientes',
        ]);
    }

    // ==================== GROUP 3: Error Messages (Test 5) ====================

    /**
     * Test #5: Error message shows active tickets count
     *
     * Verifies that when deletion is prevented due to active tickets,
     * the error message includes the count of active tickets.
     *
     * Expected: 422 with error message including count (e.g., "3 active tickets")
     * Database: Category should remain in database
     */
    #[Test]
    public function error_message_shows_active_tickets_count(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create category
        $createPayload = ['name' => 'Categoría con Múltiples Tickets'];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Create multiple active tickets (mix of OPEN, PENDING, pending)
        $openTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'open',
        ]);
        $pendingTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'pending',
        ]);
        $inProgressTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'pending',
        ]);

        // Also create closed ticket (shouldn't count as active)
        $closedTicket = Ticket::factory()->create([
            'category_id' => $categoryId,
            'status' => 'closed',
        ]);

        // Act - Try to delete category
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/categories/{$categoryId}");

        // Assert - Should fail with count
        $response->assertStatus(422);

        // Error message should mention "3 active tickets" (not 4, because closed doesn't count)
        $responseContent = $response->json();
        $this->assertStringContainsString('3', json_encode($responseContent));
        $this->assertStringContainsString('active', json_encode($responseContent));

        // Verify category still exists
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Categoría con Múltiples Tickets',
        ]);
    }

    // ==================== GROUP 4: Permissions (Test 6) ====================

    /**
     * Test #6: User cannot delete category
     *
     * Verifies that users with USER role cannot delete categories.
     * Only COMPANY_ADMIN should have this permission.
     *
     * Expected: 403 Forbidden
     * Database: Category should remain in database
     */
    #[Test]
    public function user_cannot_delete_category(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $user = User::factory()->withRole('USER')->create();

        // Create category as admin
        $createPayload = ['name' => 'Categoría a Proteger'];
        $createResponse = $this->authenticateWithJWT($admin)
            ->postJson('/api/tickets/categories', $createPayload);
        $categoryId = $createResponse->json('data.id');

        // Act - User tries to delete
        $response = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/categories/{$categoryId}");

        // Assert
        $response->assertStatus(403);

        // Verify category still exists
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'name' => 'Categoría a Proteger',
        ]);
    }
}
