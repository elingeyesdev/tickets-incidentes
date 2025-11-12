<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Responses;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Deleting Ticket Responses
 *
 * Tests the endpoint DELETE /api/tickets/:code/responses/:id
 *
 * Coverage:
 * - Author can delete own response within 30 minutes
 * - Cannot delete after 30 minutes
 * - Permission checks
 * - Cascade deletion of attachments
 * - Soft/hard delete behavior
 * - Ticket closed validation
 * - Authentication checks
 */
class DeleteResponseTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Ventana de Tiempo (Tests 1-2) ====================

    /**
     * Test #1: Author can delete own response within 30 minutes
     * Verifies that response author can delete their response if created within 30 minutes
     * Expected: 200 OK or 204 No Content (deleted successfully)
     */
    #[Test]
    public function author_can_delete_own_response_within_30_minutes(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        $response = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(10), // Created 10 minutes ago
        ]);

        // Act
        $deleteResponse = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/responses/{$response->id}");

        // Assert
        $this->assertIn($deleteResponse->getStatusCode(), [200, 204]);
    }

    /**
     * Test #2: Cannot delete response after 30 minutes
     * Verifies that response cannot be deleted if older than 30 minutes
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_delete_response_after_30_minutes(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        $response = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(40), // Created 40 minutes ago (past limit)
        ]);

        // Act
        $deleteResponse = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/responses/{$response->id}");

        // Assert
        $deleteResponse->assertStatus(403);
    }

    // ==================== GROUP 2: Permisos de Eliminación (Tests 3-4) ====================

    /**
     * Test #3: User cannot delete other user's response
     * Verifies that User A cannot delete User B's response
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_delete_other_user_response(): void
    {
        // Arrange
        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userA->id,
            'status' => 'open',
        ]);

        $response = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $userB->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(10),
        ]);

        // Act
        $deleteResponse = $this->authenticateWithJWT($userA)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/responses/{$response->id}");

        // Assert
        $deleteResponse->assertStatus(403);
    }

    /**
     * Test #4: Cannot delete response if ticket is closed
     * Verifies that no response can be deleted if ticket status=closed
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_delete_response_if_ticket_closed(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'closed', // Ticket is closed
        ]);

        $response = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(10),
        ]);

        // Act
        $deleteResponse = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/responses/{$response->id}");

        // Assert
        $deleteResponse->assertStatus(403);
    }

    // ==================== GROUP 3: Cascade Deletion (Test 5) ====================

    /**
     * Test #5: Deleting response cascades to attachments
     * Verifies that when a response is deleted, all its attachments are also deleted
     * Expected: 200/204 and all attachments removed from database
     */
    #[Test]
    public function deleting_response_cascades_to_attachments(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        $response = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(10),
        ]);

        // Create attachments for this response
        $attachments = TicketAttachment::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
            'response_id' => $response->id,
        ]);

        // Verify attachments exist
        $this->assertCount(3, $response->attachments);

        // Act
        $deleteResponse = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/responses/{$response->id}");

        // Assert
        $this->assertIn($deleteResponse->getStatusCode(), [200, 204]);

        // Verify response is deleted
        $this->assertDatabaseMissing('ticketing_ticket_responses', [
            'id' => $response->id,
        ]);

        // Verify attachments are also deleted (cascade)
        foreach ($attachments as $attachment) {
            $this->assertDatabaseMissing('ticketing_ticket_attachments', [
                'id' => $attachment->id,
            ]);
        }
    }

    // ==================== GROUP 4: 404 Behavior (Test 6) ====================

    /**
     * Test #6: Deleted response returns 404
     * Verifies that accessing a deleted response returns 404 Not Found
     * Expected: 404 after deletion
     */
    #[Test]
    public function deleted_response_returns_404(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        $response = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(10),
        ]);

        $responseId = $response->id;
        $ticketCode = $ticket->ticket_code;

        // Act - Delete response
        $deleteResponse = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticketCode}/responses/{$responseId}");

        // Assert - Delete succeeds
        $this->assertIn($deleteResponse->getStatusCode(), [200, 204]);

        // Act - Try to access deleted response
        $getResponse = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticketCode}/responses/{$responseId}");

        // Assert - Returns 404
        $getResponse->assertStatus(404);
    }

    // ==================== GROUP 5: Autenticación (Test 7) ====================

    /**
     * Test #7: Unauthenticated user cannot delete
     * Verifies that requests without JWT token return 401
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_delete(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        $response = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(10),
        ]);

        // Act - No JWT
        $deleteResponse = $this->deleteJson(
            "/api/tickets/{$ticket->ticket_code}/responses/{$response->id}"
        );

        // Assert
        $deleteResponse->assertStatus(401);
    }
}
