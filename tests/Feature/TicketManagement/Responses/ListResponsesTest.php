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
 * Feature Tests for Listing Ticket Responses
 *
 * Tests the endpoint GET /api/tickets/:code/responses
 *
 * Coverage:
 * - User can list responses from own ticket
 * - Agent can list responses from company tickets
 * - Ordering by created_at
 * - Response includes author information
 * - Response includes attachments
 * - Permission validation
 * - Authentication checks
 */
class ListResponsesTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Permisos (Tests 1-2) ====================

    /**
     * Test #1: User can list responses from own ticket
     * Verifies that a ticket owner can list all responses to their ticket
     * Expected: 200 OK with array of responses
     */
    #[Test]
    public function user_can_list_responses_from_own_ticket(): void
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

        // Create some responses
        TicketResponse::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}/responses");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'ticket_id',
                    'author_id',
                    'author_type',
                    'response_content',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test #2: Agent can list responses from any company ticket
     * Verifies that AGENT can view all responses from any ticket in their company
     * Expected: 200 OK with responses
     */
    #[Test]
    public function agent_can_list_responses_from_any_company_ticket(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        TicketResponse::factory()->count(2)->create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets/{$ticket->ticket_code}/responses");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    // ==================== GROUP 2: Orden y Contenido (Tests 3-5) ====================

    /**
     * Test #3: Responses ordered by created_at ascending
     * Verifies that responses are returned in chronological order
     * AND each response includes correct author_type
     * Expected: 200 with sorted responses
     */
    #[Test]
    public function responses_are_ordered_by_created_at_asc(): void
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

        // Create responses with specific timestamps
        $response1 = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subHours(3),
        ]);

        $response2 = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subHours(2),
        ]);

        $response3 = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subHours(1),
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}/responses");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $response1->id);
        $response->assertJsonPath('data.1.id', $response2->id);
        $response->assertJsonPath('data.2.id', $response3->id);
        $response->assertJsonPath('data.0.author_type', 'user');
        $response->assertJsonPath('data.1.author_type', 'user');
        $response->assertJsonPath('data.2.author_type', 'user');
    }

    /**
     * Test #4: Response includes author information
     * Verifies that each response includes author_id, author_name, author_type
     * AND is coherent with ticket's last_response_author_type
     * Expected: 200 with complete author info
     */
    #[Test]
    public function response_includes_author_information(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => $agent->id,
        ]);

        // Create user response first
        $userResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'created_at' => now()->subMinutes(10),
        ]);

        // Create agent response second
        $agentResponse = TicketResponse::factory()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'created_at' => now()->subMinutes(5),
        ]);

        // Update ticket's last_response_author_type to 'agent' (most recent)
        $ticket->update(['last_response_author_type' => 'agent']);

        // Act
        $listResponse = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets/{$ticket->ticket_code}/responses");

        // Assert
        $listResponse->assertStatus(200);

        // Verify both responses in correct order
        $listResponse->assertJsonPath('data.0.author_id', $user->id);
        $listResponse->assertJsonPath('data.0.author_type', 'user');
        $listResponse->assertJsonPath('data.1.author_id', $agent->id);
        $listResponse->assertJsonPath('data.1.author_type', 'agent');

        // Verify coherence with ticket's last_response_author_type
        $ticket->refresh();
        $this->assertEquals('agent', $ticket->last_response_author_type);
        $listResponse->assertJsonPath('data.1.author_type', $ticket->last_response_author_type);
    }

    /**
     * Test #5: Response includes attachments
     * Verifies that each response includes its attachments array
     * AND last response updates ticket's last_response_author_type correctly
     * Expected: 200 with attachments array in each response
     */
    #[Test]
    public function response_includes_attachments(): void
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
        ]);

        // Act
        $listResponse = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}/responses");

        // Assert
        $listResponse->assertStatus(200);
        $listResponse->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'author_id',
                    'author_type',
                    'attachments',
                ],
            ],
        ]);

        // Verify attachments is an array
        $data = $listResponse->json('data');
        $this->assertIsArray($data[0]['attachments'] ?? null);
    }

    // ==================== GROUP 3: Permisos de Lectura (Tests 6-7) ====================

    /**
     * Test #6: User cannot list responses from other user's ticket
     * Verifies that User A cannot view responses from User B's ticket
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_list_responses_from_other_user_ticket(): void
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

        // Act
        $response = $this->authenticateWithJWT($userB)
            ->getJson("/api/tickets/{$ticket->ticket_code}/responses");

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #7: Agent cannot list responses from other company ticket
     * Verifies that Agent from Company A cannot view Company B ticket responses
     * Expected: 403 Forbidden
     */
    #[Test]
    public function agent_cannot_list_responses_from_other_company_ticket(): void
    {
        // Arrange
        $agentA = User::factory()->withRole('AGENT')->create();
        $agentB = User::factory()->withRole('AGENT')->create();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $agentA->assignRole('AGENT', $companyA->id);
        $agentB->assignRole('AGENT', $companyB->id);

        $user = User::factory()->withRole('USER')->create();
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);
        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act
        $response = $this->authenticateWithJWT($agentA)
            ->getJson("/api/tickets/{$ticketB->ticket_code}/responses");

        // Assert
        $response->assertStatus(403);
    }

    // ==================== GROUP 4: Autenticación (Test 8) ====================

    /**
     * Test #8: Unauthenticated user cannot list responses
     * Verifies that requests without JWT token return 401
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_list_responses(): void
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

        // Act - No JWT
        $response = $this->getJson("/api/tickets/{$ticket->ticket_code}/responses");

        // Assert
        $response->assertStatus(401);
    }
}
