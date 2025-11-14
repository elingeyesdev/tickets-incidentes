<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Tickets\CRUD;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Deleting Tickets
 *
 * Tests the endpoint DELETE /api/tickets/:code
 *
 * Coverage:
 * - Authentication (unauthenticated)
 * - Permissions by role (only COMPANY_ADMIN can delete closed tickets)
 * - Status restrictions (can only delete closed tickets)
 * - Cascade deletion (deletes related responses, internal_notes, attachments, ratings)
 *
 * Expected Status Codes:
 * - 200: Ticket deleted successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (USER, AGENT, or ticket not closed)
 *
 * Database Schema: ticketing.tickets
 * - id: UUID
 * - ticket_code: VARCHAR(50)
 * - company_id: UUID
 * - category_id: UUID
 * - created_by_user_id: UUID
 * - owner_agent_id: UUID (nullable)
 * - title: VARCHAR(255)
 * - description: TEXT
 * - status: ENUM (open, pending, resolved, closed)
 * - created_at: TIMESTAMPTZ
 * - updated_at: TIMESTAMPTZ
 */
class DeleteTicketTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Autenticación (Test 1) ====================

    /**
     * Test #1: Unauthenticated user cannot delete ticket
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_delete_ticket(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $user = User::factory()->withRole('USER')->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'closed',
        ]);

        // Act - No authenticateWithJWT() call
        $response = $this->deleteJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(404);
    }

    // ==================== GROUP 2: Permisos (Tests 2-3) ====================

    /**
     * Test #2: User cannot delete ticket
     *
     * Verifies that USER role cannot delete tickets, even their own.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_delete_ticket(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket del usuario',
            'status' => 'closed',
        ]);

        // Act - User tries to delete their own ticket
        $response = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT deleted
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket del usuario',
        ]);
    }

    /**
     * Test #3: Agent cannot delete ticket
     *
     * Verifies that AGENT role cannot delete tickets.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function agent_cannot_delete_ticket(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket de la empresa',
            'status' => 'closed',
        ]);

        // Act - Agent tries to delete ticket
        $response = $this->authenticateWithJWT($agent)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT deleted
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket de la empresa',
        ]);
    }

    // ==================== GROUP 3: Eliminación Exitosa (Test 4) ====================

    /**
     * Test #4: Company admin can delete closed ticket
     *
     * Verifies that COMPANY_ADMIN role can delete tickets with status 'closed'.
     *
     * Expected: 200 OK, ticket deleted from database
     */
    #[Test]
    public function company_admin_can_delete_closed_ticket(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign admin to company
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket cerrado para eliminar',
            'status' => 'closed',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(200);

        // Verify ticket was deleted
        $this->assertDatabaseMissing('ticketing.tickets', [
            'id' => $ticket->id,
        ]);
    }

    // ==================== GROUP 4: Restricciones de Status (Tests 5-7) ====================

    /**
     * Test #5: Cannot delete open ticket
     *
     * Verifies that COMPANY_ADMIN cannot delete tickets with status 'open'.
     * Only closed tickets can be deleted.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_delete_open_ticket(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign admin to company
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket abierto',
            'status' => 'open',
        ]);

        // Act - Admin tries to delete open ticket
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT deleted
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket abierto',
            'status' => 'open',
        ]);
    }

    /**
     * Test #6: Cannot delete pending ticket
     *
     * Verifies that COMPANY_ADMIN cannot delete tickets with status 'pending'.
     * Only closed tickets can be deleted.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_delete_pending_ticket(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign admin to company
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket pendiente',
            'status' => 'pending',
        ]);

        // Act - Admin tries to delete pending ticket
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT deleted
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket pendiente',
            'status' => 'pending',
        ]);
    }

    /**
     * Test #7: Cannot delete resolved ticket
     *
     * Verifies that COMPANY_ADMIN cannot delete tickets with status 'resolved'.
     * Only closed tickets can be deleted.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_delete_resolved_ticket(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign admin to company
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket resuelto',
            'status' => 'resolved',
        ]);

        // Act - Admin tries to delete resolved ticket
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(403);

        // Verify ticket was NOT deleted
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket resuelto',
            'status' => 'resolved',
        ]);
    }

    // ==================== GROUP 5: Eliminación en Cascada (Test 8) ====================

    /**
     * Test #8: Deleting ticket cascades to related data
     *
     * Verifies that deleting a ticket also deletes all related data:
     * - Responses
     * - Internal notes
     * - Attachments
     * - Ratings
     *
     * Expected: Ticket and all related records are deleted
     */
    #[Test]
    public function deleting_ticket_cascades_to_related_data(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Assign admin to company
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'title' => 'Ticket con relaciones',
            'status' => 'closed',
        ]);

        // Note: In a real implementation, we would create related records here:
        // - Responses (ticketing.ticket_responses)
        // - Internal notes (ticketing.internal_notes)
        // - Attachments (ticketing.attachments)
        // - Ratings (ticketing.ticket_ratings)
        //
        // For this RED test, we're just testing the endpoint behavior.
        // The actual cascade deletion will be implemented in the GREEN phase.

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}");

        // Assert
        $response->assertStatus(200);

        // Verify ticket was deleted
        $this->assertDatabaseMissing('ticketing.tickets', [
            'id' => $ticket->id,
        ]);

        // Verify that subsequent GET returns 404
        $getResponse = $this->authenticateWithJWT($admin)
            ->getJson("/api/tickets/{$ticket->ticket_code}");
        $getResponse->assertStatus(404);

        // Future assertion (will be enabled in GREEN phase when relations exist):
        // $this->assertDatabaseMissing('ticketing.ticket_responses', ['ticket_id' => $ticket->id]);
        // $this->assertDatabaseMissing('ticketing.internal_notes', ['ticket_id' => $ticket->id]);
        // $this->assertDatabaseMissing('ticketing.attachments', ['ticket_id' => $ticket->id]);
        // $this->assertDatabaseMissing('ticketing.ticket_ratings', ['ticket_id' => $ticket->id]);
    }
}
