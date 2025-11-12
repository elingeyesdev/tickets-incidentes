<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Responses;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Updating Ticket Responses
 *
 * Tests the endpoint PUT /api/tickets/:code/responses/:id
 *
 * Coverage:
 * - Author can update own response within 30 minutes
 * - Cannot update after 30 minutes (time window)
 * - Content length validation
 * - Permission checks
 * - Timestamp preservation (created_at, updated_at)
 * - Ticket closed validation
 */
class UpdateResponseTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Ventana de Tiempo 30 Minutos (Tests 1-2) ====================

    /**
     * Test #1: Author can update own response within 30 minutes
     * Verifies that response author can update their response if created within 30 minutes
     * Expected: 200 OK with updated content
     */
    #[Test]
    public function author_can_update_own_response_within_30_minutes(): void
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

        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(15), // Created 15 minutes ago
        ]);

        // Act
        $updateResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'Updated content within time window.',
            ]);

        // Assert
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.response_content', 'Updated content within time window.');
    }

    /**
     * Test #2: Cannot update response after 30 minutes
     * Verifies that response cannot be updated if older than 30 minutes
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_update_response_after_30_minutes(): void
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

        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(35), // Created 35 minutes ago (past limit)
        ]);

        // Act
        $updateResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'Too late to update.',
            ]);

        // Assert
        $updateResponse->assertStatus(403);
    }

    // ==================== GROUP 2: Validaciones de Contenido (Test 3) ====================

    /**
     * Test #3: Validates updated content length
     * Verifies that updated content respects min 1 and max 5000 chars
     * Expected: 422 for invalid lengths
     */
    #[Test]
    public function validates_updated_content_length(): void
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

        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(15),
        ]);

        // Act - Empty content
        $emptyResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => '',
            ]);

        // Assert - Empty fails
        $emptyResponse->assertStatus(422);

        // Act - 1 character (minimum valid)
        $minResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'a',
            ]);

        // Assert - 1 char passes
        $minResponse->assertStatus(200);

        // Act - Content too long (5001 chars)
        $longResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => str_repeat('a', 5001),
            ]);

        // Assert - Too long fails
        $longResponse->assertStatus(422);

        // Act - 5000 characters (maximum valid)
        $maxResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => str_repeat('b', 5000),
            ]);

        // Assert - 5000 chars passes
        $maxResponse->assertStatus(200);

        // Act - Valid content (normal length)
        $validResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'Valid updated content.',
            ]);

        // Assert - Valid passes
        $validResponse->assertStatus(200);
    }

    // ==================== GROUP 3: Permisos de Actualización (Tests 4-6) ====================

    /**
     * Test #4: User cannot update other user's response
     * Verifies that User A cannot update User B's response
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_update_other_user_response(): void
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

        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $userB->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(15),
        ]);

        // Act
        $updateResponse = $this->authenticateWithJWT($userA)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'Trying to update other user response.',
            ]);

        // Assert
        $updateResponse->assertStatus(403);
    }

    /**
     * Test #5: Agent cannot update other agent's response
     * Verifies that Agent A cannot update Agent B's response
     * Expected: 403 Forbidden
     */
    #[Test]
    public function agent_cannot_update_other_agent_response(): void
    {
        // Arrange
        $agentA = User::factory()->withRole('AGENT')->create();
        $agentB = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agentA->assignRole('AGENT', $company->id);
        $agentB->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $agentB->id,
            'author_type' => 'agent',
            'created_at' => now()->subMinutes(15),
        ]);

        // Act
        $updateResponse = $this->authenticateWithJWT($agentA)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'Trying to update other agent response.',
            ]);

        // Assert
        $updateResponse->assertStatus(403);
    }

    /**
     * Test #6: Cannot update response if ticket is closed
     * Verifies that no response can be updated if ticket status=closed
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_update_response_if_ticket_closed(): void
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

        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(15),
        ]);

        // Act
        $updateResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'Cannot update closed ticket response.',
            ]);

        // Assert
        $updateResponse->assertStatus(403);
    }

    // ==================== GROUP 4: Actualización Parcial (Test 7) ====================

    /**
     * Test #7: Partial update works
     * Verifies that updating only response_content works correctly
     * Expected: 200 with only that field updated
     */
    #[Test]
    public function partial_update_works(): void
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

        $originalContent = 'Original content.';
        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'response_content' => $originalContent,
            'created_at' => now()->subMinutes(15),
        ]);

        // Act
        $updateResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'Updated only this field.',
            ]);

        // Assert
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.response_content', 'Updated only this field.');
    }

    // ==================== GROUP 5: Integridad de Timestamps (Tests 8-9) ====================

    /**
     * Test #8: Updating preserves original created_at
     * Verifies that created_at timestamp is NOT changed during update
     * Expected: 200 with created_at unchanged
     */
    #[Test]
    public function updating_preserves_original_created_at(): void
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

        $originalCreatedAt = now()->subMinutes(15);
        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => $originalCreatedAt,
        ]);

        // Act
        $updateResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'Updated content.',
            ]);

        // Assert
        $updateResponse->assertStatus(200);

        $ticketResponse->refresh();

        // created_at should NOT change
        $this->assertEquals(
            $originalCreatedAt->format('Y-m-d H:i'),
            $ticketResponse->created_at->format('Y-m-d H:i')
        );
    }

    /**
     * Test #9: Updating sets updated_at timestamp
     * Verifies that updated_at is set/changed when response is updated
     * Expected: 200 with updated_at changed
     */
    #[Test]
    public function updating_sets_updated_at_timestamp(): void
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

        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(15),
        ]);

        $originalUpdatedAt = $ticketResponse->updated_at;

        // Act
        sleep(1); // Ensure time passes

        $updateResponse = $this->authenticateWithJWT($user)
            ->putJson("/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}", [
                'response_content' => 'Updated content.',
            ]);

        // Assert
        $updateResponse->assertStatus(200);

        $ticketResponse->refresh();

        // updated_at should be different (newer)
        $this->assertGreaterThan($originalUpdatedAt, $ticketResponse->updated_at);
    }

    // ==================== GROUP 6: Autenticación (Test 10) ====================

    /**
     * Test #10: Unauthenticated user cannot update
     * Verifies that requests without JWT token return 401
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_update(): void
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

        $ticketResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(15),
        ]);

        // Act - No JWT
        $updateResponse = $this->putJson(
            "/api/tickets/{$ticket->ticket_code}/responses/{$ticketResponse->id}",
            ['response_content' => 'No auth attempt.']
        );

        // Assert
        $updateResponse->assertStatus(401);
    }
}
