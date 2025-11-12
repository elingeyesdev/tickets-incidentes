<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Attachments;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Listing Ticket Attachments
 *
 * Tests the endpoint GET /api/tickets/:code/attachments
 *
 * Coverage:
 * - User can list attachments from own ticket
 * - Agent can list attachments from any company ticket
 * - Attachments include uploader information (uploaded_by_user_id, uploaded_by_name)
 * - Attachments include response context (response_id)
 * - User cannot list attachments from other user's ticket
 * - Unauthenticated user cannot list attachments
 */
class ListAttachmentsTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Permisos (Tests 1-2) ====================

    /**
     * Test #1: User can list attachments from own ticket
     * Verifies that a ticket owner can list all attachments (from ticket + responses)
     * Expected: 200 OK with array of attachments
     */
    #[Test]
    public function user_can_list_attachments_from_own_ticket(): void
    {
        // Arrange
        Storage::fake('local');

        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => $agent->id,
        ]);

        // Create attachment linked to ticket (no response)
        $ticketAttachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'screenshot.png',
            'file_url' => 'tickets/attachments/screenshot.png',
            'file_type' => 'image/png',
            'file_size_bytes' => 1024,
        ]);

        // Create response and attachment linked to response
        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'response_content' => 'Please check this solution',
        ]);

        $responseAttachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => $response->id,
            'uploaded_by_user_id' => $agent->id,
            'file_name' => 'solution.pdf',
            'file_url' => 'tickets/attachments/solution.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 2048,
        ]);

        // Act
        $httpResponse = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}/attachments");

        // Assert
        $httpResponse->assertStatus(200);
        $httpResponse->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'ticket_id',
                    'response_id',
                    'uploaded_by_user_id',
                    'uploaded_by_name',
                    'file_name',
                    'file_url',
                    'file_type',
                    'file_size_bytes',
                    'created_at',
                ],
            ],
        ]);

        // Verify both attachments are returned
        $httpResponse->assertJsonCount(2, 'data');

        // Verify ticket attachment (response_id = null)
        $httpResponse->assertJsonPath('data.0.id', $ticketAttachment->id);
        $httpResponse->assertJsonPath('data.0.response_id', null);
        $httpResponse->assertJsonPath('data.0.uploaded_by_user_id', $user->id);
        $httpResponse->assertJsonPath('data.0.file_name', 'screenshot.png');

        // Verify response attachment (response_id != null)
        $httpResponse->assertJsonPath('data.1.id', $responseAttachment->id);
        $httpResponse->assertJsonPath('data.1.response_id', $response->id);
        $httpResponse->assertJsonPath('data.1.uploaded_by_user_id', $agent->id);
        $httpResponse->assertJsonPath('data.1.file_name', 'solution.pdf');
    }

    /**
     * Test #2: Agent can list attachments from any company ticket
     * Verifies that AGENT can view all attachments from any ticket in their company
     * Expected: 200 OK with attachments
     */
    #[Test]
    public function agent_can_list_attachments_from_any_company_ticket(): void
    {
        // Arrange
        Storage::fake('local');

        $agent = User::factory()->withRole('AGENT')->create();
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Create attachments
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'user-document.pdf',
            'file_url' => 'tickets/attachments/user-document.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 3072,
        ]);

        // Act - Agent accesses ticket created by user
        $response = $this->authenticateWithJWT($agent)
            ->getJson("/api/tickets/{$ticket->ticket_code}/attachments");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.file_name', 'user-document.pdf');
    }

    // ==================== GROUP 2: Información Completa (Tests 3-4) ====================

    /**
     * Test #3: Attachments include uploader information
     * Verifies that each attachment includes uploaded_by_user_id and uploaded_by_name
     * Expected: 200 with complete uploader information
     */
    #[Test]
    public function attachments_include_uploader_information(): void
    {
        // Arrange
        Storage::fake('local');

        $user = User::factory()->withRole('USER')->create([
            'email' => 'john.doe@example.com',
        ]);
        $user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Create attachment
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'test-file.txt',
            'file_url' => 'tickets/attachments/test-file.txt',
            'file_type' => 'text/plain',
            'file_size_bytes' => 512,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}/attachments");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.uploaded_by_user_id', $user->id);
        $response->assertJsonPath('data.0.uploaded_by_name', 'John Doe');
    }

    /**
     * Test #4: Attachments include response context
     * Verifies that attachments include response_id information
     * If response_id is null, it's a general ticket attachment
     * If response_id is not null, it's linked to a specific response
     * Expected: 200 with response context
     */
    #[Test]
    public function attachments_include_response_context(): void
    {
        // Arrange
        Storage::fake('local');

        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
            'owner_agent_id' => $agent->id,
        ]);

        // Create general attachment (no response)
        $generalAttachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'general-file.txt',
            'file_url' => 'tickets/attachments/general-file.txt',
            'file_type' => 'text/plain',
            'file_size_bytes' => 256,
        ]);

        // Create response and linked attachment
        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'response_content' => 'Here is the solution',
        ]);

        $responseAttachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => $response->id,
            'uploaded_by_user_id' => $agent->id,
            'file_name' => 'solution-document.pdf',
            'file_url' => 'tickets/attachments/solution-document.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 4096,
        ]);

        // Act
        $httpResponse = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}/attachments");

        // Assert
        $httpResponse->assertStatus(200);
        $httpResponse->assertJsonCount(2, 'data');

        // Verify general attachment has null response_id
        $httpResponse->assertJsonPath('data.0.response_id', null);
        $httpResponse->assertJsonPath('data.0.file_name', 'general-file.txt');

        // Verify response attachment has valid response_id
        $httpResponse->assertJsonPath('data.1.response_id', $response->id);
        $httpResponse->assertJsonPath('data.1.file_name', 'solution-document.pdf');
    }

    // ==================== GROUP 3: Permisos de Lectura (Test 5) ====================

    /**
     * Test #5: User cannot list attachments from other user's ticket
     * Verifies that User A cannot view attachments from User B's ticket
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_list_attachments_from_other_user_ticket(): void
    {
        // Arrange
        Storage::fake('local');

        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticketB = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userB->id,
            'status' => 'open',
        ]);

        TicketAttachment::create([
            'ticket_id' => $ticketB->id,
            'response_id' => null,
            'uploaded_by_user_id' => $userB->id,
            'file_name' => 'private-file.txt',
            'file_url' => 'tickets/attachments/private-file.txt',
            'file_type' => 'text/plain',
            'file_size_bytes' => 128,
        ]);

        // Act - User A tries to access User B's ticket attachments
        $response = $this->authenticateWithJWT($userA)
            ->getJson("/api/tickets/{$ticketB->ticket_code}/attachments");

        // Assert
        $response->assertStatus(403);
    }

    // ==================== GROUP 4: Autenticación (Test 6) ====================

    /**
     * Test #6: Unauthenticated user cannot list attachments
     * Verifies that requests without JWT token return 401
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_list(): void
    {
        // Arrange
        Storage::fake('local');

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Act - No JWT token
        $response = $this->getJson("/api/tickets/{$ticket->ticket_code}/attachments");

        // Assert
        $response->assertStatus(401);
    }
}
