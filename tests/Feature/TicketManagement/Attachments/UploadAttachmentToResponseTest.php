<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Attachments;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Uploading Attachments to Responses
 *
 * Tests the endpoint POST /api/tickets/:code/responses/:id/attachments
 *
 * Coverage:
 * - Upload attachment to specific response (response_id populated)
 * - Response detail includes attachments
 * - Response validation (must belong to ticket)
 * - Author-only upload permission
 * - 30-minute time window after response creation
 * - Agent cannot upload to user response
 * - Max 5 attachments applies to entire ticket (ticket + responses combined)
 * - Authentication required
 */
class UploadAttachmentToResponseTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Operaciones Exitosas (Tests 1-2) ====================

    /**
     * Test #1: Can upload attachment to specific response
     * Verifies that attachment is uploaded with response_id populated
     * Expected: 200 OK with response_id in DB and JSON
     */
    #[Test]
    public function can_upload_attachment_to_specific_response(): void
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

        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => 'Test response content',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        // Act
        $uploadResponse = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
                'response_id' => $response->id,
            ]);

        // Assert
        $uploadResponse->assertStatus(201);
        $uploadResponse->assertJsonPath('data.response_id', $response->id);
        $uploadResponse->assertJsonPath('data.ticket_id', $ticket->id);
        $uploadResponse->assertJsonPath('data.file_name', 'test.pdf');

        // Verify response_id is populated in database
        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
            'response_id' => $response->id,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'test.pdf',
        ]);
    }

    /**
     * Test #2: Attachment linked to response appears in response detail
     * Verifies that GET /responses/:id includes attachments array
     * Expected: 200 OK with attachments included
     */
    #[Test]
    public function attachment_linked_to_response_appears_in_response_detail(): void
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

        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => 'Response with attachment',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        // Upload attachment to response
        $file = UploadedFile::fake()->create('document.pdf', 150);
        $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
                'response_id' => $response->id,
            ]);

        // Act - Get response detail
        $detailResponse = $this->authenticateWithJWT($user)
            ->getJson("/api/tickets/{$ticket->ticket_code}/responses/{$response->id}");

        // Assert
        $detailResponse->assertStatus(200);
        $detailResponse->assertJsonStructure([
            'data' => [
                'id',
                'content',
                'attachments' => [
                    '*' => [
                        'id',
                        'file_name',
                        'response_id',
                    ],
                ],
            ],
        ]);
        $detailResponse->assertJsonPath('data.attachments.0.file_name', 'document.pdf');
        $detailResponse->assertJsonPath('data.attachments.0.response_id', $response->id);
    }

    // ==================== GROUP 2: Validaciones de Respuesta (Tests 3-4) ====================

    /**
     * Test #3: Validates response belongs to ticket
     * Verifies that response from different ticket returns 422
     * Expected: 422 Unprocessable Entity
     */
    #[Test]
    public function validates_response_belongs_to_ticket(): void
    {
        // Arrange
        Storage::fake('local');

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        // Ticket A
        $ticketA = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // Ticket B with response
        $ticketB = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        $responseFromTicketB = TicketResponse::create([
            'ticket_id' => $ticketB->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => 'Response from ticket B',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        // Act - Try to upload to response from different ticket
        $uploadResponse = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticketA->ticket_code}/attachments", [
                'file' => $file,
                'response_id' => $responseFromTicketB->id,
            ]);

        // Assert
        $uploadResponse->assertStatus(422);
    }

    /**
     * Test #4: Author of response can upload attachment
     * Verifies that only the author_id of the response can upload
     * Expected: 200 OK for author, 403 for non-author
     */
    #[Test]
    public function author_of_response_can_upload_attachment(): void
    {
        // Arrange
        Storage::fake('local');

        $author = User::factory()->withRole('USER')->create();
        $otherUser = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $author->id,
            'status' => 'open',
        ]);

        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $author->id,
            'author_type' => 'user',
            'content' => 'Author response',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        // Act - Author uploads
        $authorUpload = $this->authenticateWithJWT($author)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
                'response_id' => $response->id,
            ]);

        // Assert - Author succeeds
        $authorUpload->assertStatus(201);

        // Act - Other user tries to upload
        $file2 = UploadedFile::fake()->create('test2.pdf', 100);
        $otherUserUpload = $this->authenticateWithJWT($otherUser)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file2,
                'response_id' => $response->id,
            ]);

        // Assert - Other user fails
        $otherUserUpload->assertStatus(403);
    }

    // ==================== GROUP 3: Restricciones de Tiempo y Rol (Tests 5-6) ====================

    /**
     * Test #5: Cannot upload to response after 30 minutes
     * Verifies that 30-minute window is enforced from response creation
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_upload_to_response_after_30_minutes(): void
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

        // Create response 35 minutes ago (past 30-minute limit)
        $oldResponse = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => 'Old response',
            'created_at' => Carbon::now()->subMinutes(35),
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        // Act
        $uploadResponse = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
                'response_id' => $oldResponse->id,
            ]);

        // Assert
        $uploadResponse->assertStatus(403);
    }

    /**
     * Test #6: Agent cannot upload to user response
     * Verifies that agent cannot upload to response with author_type='user'
     * Expected: 403 Forbidden
     */
    #[Test]
    public function agent_cannot_upload_to_user_response(): void
    {
        // Arrange
        Storage::fake('local');

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $category = Category::factory()->create(['company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);

        // User creates response
        $userResponse = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => 'User response',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        // Act - Agent tries to upload to user response
        $uploadResponse = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
                'response_id' => $userResponse->id,
            ]);

        // Assert
        $uploadResponse->assertStatus(403);
    }

    // ==================== GROUP 4: Límite Global de Attachments (Test 7) ====================

    /**
     * Test #7: Max 5 attachments applies to entire ticket
     * Verifies that limit counts ticket + responses combined
     * Expected: 422 on 6th attachment
     */
    #[Test]
    public function max_5_attachments_applies_to_entire_ticket(): void
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

        // Create response
        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => 'Test response',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        // Upload 3 attachments directly to ticket (response_id = null)
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->create("ticket_file_{$i}.pdf", 100);
            $this->authenticateWithJWT($user)
                ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                    'file' => $file,
                ]);
        }

        // Upload 2 attachments to response (total = 5)
        for ($i = 1; $i <= 2; $i++) {
            $file = UploadedFile::fake()->create("response_file_{$i}.pdf", 100);
            $uploadResponse = $this->authenticateWithJWT($user)
                ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                    'file' => $file,
                    'response_id' => $response->id,
                ]);
            $uploadResponse->assertStatus(201);
        }

        // Act - Try to upload 6th attachment
        $file6 = UploadedFile::fake()->create("file_6.pdf", 100);
        $sixthUploadResponse = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file6,
                'response_id' => $response->id,
            ]);

        // Assert - 6th upload fails
        $sixthUploadResponse->assertStatus(422);

        // Verify total count in DB is 5
        $totalAttachments = TicketAttachment::where('ticket_id', $ticket->id)->count();
        $this->assertEquals(5, $totalAttachments);
    }

    // ==================== GROUP 5: Autenticación (Test 8) ====================

    /**
     * Test #8: Unauthenticated user cannot upload
     * Verifies that requests without JWT token return 401
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_upload(): void
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

        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => 'Test response',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        // Act - No JWT authentication
        $uploadResponse = $this->postJson(
            "/api/tickets/{$ticket->ticket_code}/attachments",
            ['file' => $file, 'response_id' => $response->id]
        );

        // Assert
        $uploadResponse->assertStatus(401);
    }
}
