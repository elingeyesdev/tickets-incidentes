<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Tickets\CRUD;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Updating Tickets
 *
 * Tests the endpoint PUT /api/tickets/:code
 *
 * Coverage:
 * - Authentication (unauthenticated)
 * - Permissions by role (USER can update own tickets when status=open, AGENT can update title/category)
 * - Status restrictions (USER cannot update when status=pending or resolved)
 * - Validation (title length, category exists)
 * - Partial updates preserve unchanged fields
 * - Status change restrictions (cannot manually change to pending)
 * - Company isolation (AGENT/COMPANY_ADMIN cannot update other company tickets)
 *
 * Expected Status Codes:
 * - 200: Ticket updated successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (wrong user, wrong status, wrong company)
 * - 422: Validation errors
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
class UpdateTicketTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Autenticación (Test 1) ====================

    /**
     * Test #1: Unauthenticated user cannot update ticket
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_update_ticket(): void
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
            'status' => 'open',
        ]);

        $payload = [
            'title' => 'Título actualizado sin autenticación',
        ];

        // Act - No authenticateWithJWT() call
        $response = $this->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(401);
    }

    // ==================== GROUP 2: Permisos USER (Tests 2-5) ====================

    /**
     * Test #2: User can update own ticket when status open
     *
     * Verifies that USER role can update their own ticket when status is 'open'.
     *
     * Expected: 200 OK with updated ticket
     */
    #[Test]
    public function user_can_update_own_ticket_when_status_open(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $categoryOld = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name' => 'Soporte Técnico',
        ]);
        $categoryNew = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name' => 'Ventas',
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryOld->id,
            'created_by_user_id' => $user->id,
            'title' => 'Título original',
            'status' => 'open',
        ]);

        $payload = [
            'title' => 'Título actualizado por usuario',
            'category_id' => $categoryNew->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Título actualizado por usuario');
        $response->assertJsonPath('data.category_id', $categoryNew->id);

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Título actualizado por usuario',
            'category_id' => $categoryNew->id,
        ]);
    }

    /**
     * Test #3: User cannot update ticket when status pending
     *
     * Verifies that USER role cannot update ticket when status is 'pending'
     * (agent has already responded).
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_update_ticket_when_status_pending(): void
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
            'title' => 'Ticket en pending',
            'status' => 'pending', // Agent has responded
        ]);

        $payload = [
            'title' => 'Intento de actualización en pending',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(403);

        // Verify title was NOT updated
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket en pending',
        ]);
    }

    /**
     * Test #4: User cannot update ticket when status resolved
     *
     * Verifies that USER role cannot update ticket when status is 'resolved'.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_update_ticket_when_status_resolved(): void
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
            'title' => 'Ticket resuelto',
            'status' => 'resolved',
        ]);

        $payload = [
            'title' => 'Intento de actualización en resolved',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(403);

        // Verify title was NOT updated
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket resuelto',
        ]);
    }

    /**
     * Test #5: User cannot update other user ticket
     *
     * Verifies that USER role cannot update tickets created by other users.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_update_other_user_ticket(): void
    {
        // Arrange
        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // User A creates a ticket
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'title' => 'Ticket de Usuario A',
            'status' => 'open',
        ]);

        $payload = [
            'title' => 'Intento de actualización por Usuario B',
        ];

        // Act - User B tries to update User A's ticket
        $response = $this->authenticateWithJWT($userB)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(403);

        // Verify title was NOT updated
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket de Usuario A',
        ]);
    }

    // ==================== GROUP 3: Permisos AGENT (Tests 6-7) ====================

    /**
     * Test #6: Agent can update ticket title and category
     *
     * Verifies that AGENT role can update ticket title and category.
     *
     * Expected: 200 OK with updated ticket
     */
    #[Test]
    public function agent_can_update_ticket_title_and_category(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();
        $categoryOld = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name' => 'Soporte Técnico',
        ]);
        $categoryNew = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name' => 'Ventas',
        ]);

        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryOld->id,
            'created_by_user_id' => $user->id,
            'title' => 'Título original',
            'status' => 'open',
        ]);

        $payload = [
            'title' => 'Título actualizado por agente',
            'category_id' => $categoryNew->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Título actualizado por agente');
        $response->assertJsonPath('data.category_id', $categoryNew->id);

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Título actualizado por agente',
            'category_id' => $categoryNew->id,
        ]);
    }

    /**
     * Test #7: Agent cannot manually change status to pending
     *
     * Verifies that AGENT cannot manually change status to 'pending'.
     * Status changes should only happen via specific actions (like responding to ticket).
     *
     * Expected: Status field is ignored, only title/category updated
     */
    #[Test]
    public function agent_cannot_manually_change_status_to_pending(): void
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
            'title' => 'Título original',
            'status' => 'open',
        ]);

        $payload = [
            'title' => 'Título actualizado',
            'status' => 'pending', // Attempting to manually change status
        ];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Título actualizado');

        // Verify status did NOT change (still 'open')
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Título actualizado',
            'status' => 'open', // Should remain unchanged
        ]);
    }

    // ==================== GROUP 4: Validaciones (Tests 8-9) ====================

    /**
     * Test #8: Validates updated title length
     *
     * Verifies that updating title validates length constraints (max 255).
     *
     * Expected: 422 Validation Error for title > 255 chars
     */
    #[Test]
    public function validates_updated_title_length(): void
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
            'status' => 'open',
        ]);

        $payload = [
            'title' => str_repeat('A', 300), // 300 chars, exceeds max 255
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    /**
     * Test #9: Validates updated category exists
     *
     * Verifies that updating category_id validates the category exists.
     *
     * Expected: 422 Validation Error for non-existent category
     */
    #[Test]
    public function validates_updated_category_exists(): void
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
            'status' => 'open',
        ]);

        $fakeCategoryId = Str::uuid()->toString();

        $payload = [
            'category_id' => $fakeCategoryId, // Non-existent category
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('category_id');
    }

    // ==================== GROUP 5: Actualización Parcial (Test 10) ====================

    /**
     * Test #10: Partial update preserves unchanged fields
     *
     * Verifies that updating only title preserves category_id and other fields.
     *
     * Expected: Only title changes, category_id remains the same
     */
    #[Test]
    public function partial_update_preserves_unchanged_fields(): void
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
            'title' => 'Título original',
            'status' => 'open',
        ]);

        $payload = [
            'title' => 'Solo actualizo el título',
            // NOT updating category_id
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Solo actualizo el título');
        $response->assertJsonPath('data.category_id', $category->id); // Should remain unchanged
        $response->assertJsonPath('data.last_response_author_type', $ticket->last_response_author_type);

        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Solo actualizo el título',
            'category_id' => $category->id, // Preserved
        ]);
    }

    // ==================== GROUP 6: Permisos Empresa (Tests 11-12) ====================

    /**
     * Test #11: Agent cannot update other company ticket
     *
     * Verifies that AGENT role cannot update tickets from other companies.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function agent_cannot_update_other_company_ticket(): void
    {
        // Arrange
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $agent = User::factory()->withRole('AGENT', $companyA->id)->create();
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // Create ticket in Company B
        $userB = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $userB->id,
            'title' => 'Ticket en Empresa B',
            'status' => 'open',
        ]);

        $payload = [
            'title' => 'Intento de actualización desde otra empresa',
        ];

        // Act - Agent from Company A tries to update Company B ticket
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(403);

        // Verify title was NOT updated
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket en Empresa B',
        ]);
    }

    /**
     * Test #12: Company admin from different company cannot update
     *
     * Verifies that COMPANY_ADMIN role cannot update tickets from other companies.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function company_admin_from_different_company_cannot_update(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        // Assign admin to Company A
        $admin->assignRole('COMPANY_ADMIN', $companyA->id);

        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // Create ticket in Company B
        $userB = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $userB->id,
            'title' => 'Ticket en Empresa B',
            'status' => 'open',
        ]);

        $payload = [
            'title' => 'Intento de actualización por admin de otra empresa',
        ];

        // Act - Admin from Company A tries to update Company B ticket
        $response = $this->authenticateWithJWT($admin)
            ->patchJson("/api/tickets/{$ticket->ticket_code}", $payload);

        // Assert
        $response->assertStatus(403);

        // Verify title was NOT updated
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'title' => 'Ticket en Empresa B',
        ]);
    }
}
