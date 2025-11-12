<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Attachments;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\UserManagement\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Uploading Attachments to Tickets
 *
 * Tests the endpoint POST /api/tickets/:code/attachments
 *
 * Coverage:
 * - Authentication (unauthenticated user cannot upload)
 * - Permissions by role (USER can upload to own ticket, AGENT can upload to company tickets)
 * - Company isolation (AGENT from different company cannot upload)
 * - File validation (required, size max 10MB, allowed types)
 * - Maximum attachments per ticket (max 5)
 * - File storage (correct path in storage/app/public/tickets/attachments/)
 * - Metadata persistence (file_name, file_url, file_type, file_size_bytes)
 * - Field assignment (uploaded_by_user_id, response_id = null)
 * - Status restrictions (cannot upload to closed ticket)
 *
 * Expected Status Codes:
 * - 200: Attachment uploaded successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (USER to other ticket, AGENT to other company)
 * - 413: File too large (> 10MB)
 * - 422: Validation errors (file required, type not allowed, max 5 attachments)
 *
 * Database Schema: ticketing.ticket_attachments
 * - id: UUID (auto-generated)
 * - ticket_id: UUID (FK to ticketing.tickets) - REQUIRED
 * - response_id: UUID (FK to ticketing.ticket_responses) - NULLABLE
 * - uploaded_by_user_id: UUID (FK to auth.users) - REQUIRED
 * - file_name: VARCHAR(255)
 * - file_url: VARCHAR(500)
 * - file_type: VARCHAR(100)
 * - file_size_bytes: BIGINT
 * - created_at: TIMESTAMPTZ
 *
 * Storage:
 * - Disk: local (storage/app/public)
 * - Path: tickets/attachments/
 * - Max size: 10 MB
 * - Allowed types: PDF, JPG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, ZIP
 * - Max files per ticket: 5 (total including responses)
 */
class UploadAttachmentTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Permisos Básicos (Tests 1-2) ====================

    /**
     * Test #1: User can upload attachment to own ticket
     *
     * Verifies that a USER can successfully upload a file to their own ticket:
     * - File is stored in correct path
     * - Database record is created with metadata
     * - uploaded_by_user_id is set to authenticated user
     * - response_id is null (uploaded directly to ticket)
     *
     * Expected: 200 OK with attachment data
     * Database: Attachment record should be persisted
     * Storage: File should exist in storage/app/public/tickets/attachments/
     */
    #[Test]
    public function user_can_upload_attachment_to_own_ticket(): void
    {
        // Arrange
        Storage::fake('public');

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

        $file = UploadedFile::fake()->image('screenshot.jpg', 800, 600)->size(2048); // 2 MB

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'ticket_id',
                'response_id',
                'uploaded_by_user_id',
                'file_name',
                'file_url',
                'file_type',
                'file_size_bytes',
                'created_at',
            ],
        ]);

        $response->assertJsonPath('data.ticket_id', $ticket->id);
        $response->assertJsonPath('data.response_id', null);
        $response->assertJsonPath('data.uploaded_by_user_id', $user->id);

        // Verify database record
        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
        ]);

        // Verify file exists in storage
        $attachmentId = $response->json('data.id');
        $attachment = TicketAttachment::find($attachmentId);
        $fileName = basename($attachment->file_url);
        Storage::disk('public')->assertExists("tickets/attachments/{$fileName}");
    }

    /**
     * Test #2: Agent can upload attachment to any company ticket
     *
     * Verifies that an AGENT can upload files to any ticket within their company.
     *
     * Expected: 200 OK with attachment data
     * Database: Attachment record should be persisted
     * Storage: File should exist in storage
     */
    #[Test]
    public function agent_can_upload_attachment_to_any_company_ticket(): void
    {
        // Arrange
        Storage::fake('public');

        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 1024); // 1 MB

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.ticket_id', $ticket->id);
        $response->assertJsonPath('data.uploaded_by_user_id', $agent->id);

        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by_user_id' => $agent->id,
        ]);

        // Verify file exists in storage
        $attachmentId = $response->json('data.id');
        $attachment = TicketAttachment::find($attachmentId);
        $fileName = basename($attachment->file_url);
        Storage::disk('public')->assertExists("tickets/attachments/{$fileName}");
    }

    // ==================== GROUP 2: Validaciones de Archivo (Tests 3-6) ====================

    /**
     * Test #3: Validates file is required
     *
     * Verifies that the 'file' field is required when uploading an attachment.
     *
     * Expected: 422 Unprocessable Entity with validation error
     * Database: No attachment should be created
     */
    #[Test]
    public function validates_file_is_required(): void
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

        // Act - No file provided
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);

        $this->assertDatabaseMissing('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
        ]);
    }

    /**
     * Test #4: Validates file size max 10MB
     *
     * Verifies that files larger than 10 MB are rejected with 413 error.
     *
     * Expected: 413 Payload Too Large
     * Database: No attachment should be created
     */
    #[Test]
    public function validates_file_size_max_10mb(): void
    {
        // Arrange
        Storage::fake('public');

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

        // Create a file that is 15 MB (exceeds 10 MB limit)
        $file = UploadedFile::fake()->create('large-file.pdf', 15360); // 15 MB

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(413);

        $this->assertDatabaseMissing('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
        ]);
    }

    /**
     * Test #5: Validates file type allowed
     *
     * Verifies that only allowed file types are accepted:
     * - .exe should be REJECTED (422)
     * - .pdf should be ACCEPTED (200)
     * - .jpg should be ACCEPTED (200)
     *
     * Expected: 422 for disallowed types, 200 for allowed types
     */
    #[Test]
    public function validates_file_type_allowed(): void
    {
        // Arrange
        Storage::fake('public');

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

        // Case 1: .exe file should be rejected
        $exeFile = UploadedFile::fake()->create('virus.exe', 100);
        $response1 = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $exeFile,
            ]);
        $response1->assertStatus(422);
        $response1->assertJsonValidationErrors(['file']);

        // Case 2: .pdf file should be accepted
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100);
        $response2 = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $pdfFile,
            ]);
        $response2->assertStatus(200);

        // Case 3: .jpg file should be accepted
        $jpgFile = UploadedFile::fake()->image('photo.jpg', 200, 200);
        $response3 = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $jpgFile,
            ]);
        $response3->assertStatus(200);
    }

    /**
     * Test #6: Allowed file types list
     *
     * Verifies that the following file types are allowed:
     * PDF, JPG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, ZIP
     *
     * Expected: 200 for all allowed types
     */
    #[Test]
    public function allowed_file_types_list(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $allowedTypes = [
            'document.pdf',
            'image.jpg',
            'photo.png',
            'animation.gif',
            'report.doc',
            'report.docx',
            'spreadsheet.xls',
            'spreadsheet.xlsx',
            'notes.txt',
            'archive.zip',
        ];

        foreach ($allowedTypes as $index => $fileName) {
            // Create a new ticket for each file type
            $ticket = Ticket::factory()->create([
                'company_id' => $company->id,
                'category_id' => $category->id,
                'created_by_user_id' => $user->id,
                'status' => 'open',
            ]);

            $file = UploadedFile::fake()->create($fileName, 100);

            // Act
            $response = $this->authenticateWithJWT($user)
                ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                    'file' => $file,
                ]);

            // Assert
            $response->assertStatus(200, "File type {$fileName} should be allowed");
            $this->assertDatabaseHas('ticketing.ticket_attachments', [
                'ticket_id' => $ticket->id,
            ]);
        }
    }

    // ==================== GROUP 3: Metadata y Almacenamiento (Tests 7-11) ====================

    /**
     * Test #7: Validates max 5 attachments per ticket
     *
     * Verifies that a ticket cannot have more than 5 attachments total.
     * The 6th attachment should be rejected with 422 error.
     *
     * Expected: 422 for 6th attachment
     * Database: Only 5 attachments should exist
     */
    #[Test]
    public function validates_max_5_attachments_per_ticket(): void
    {
        // Arrange
        Storage::fake('public');

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

        // Create 5 attachments (max allowed)
        for ($i = 1; $i <= 5; $i++) {
            TicketAttachment::factory()->create([
                'ticket_id' => $ticket->id,
                'response_id' => null,
                'uploaded_by_user_id' => $user->id,
            ]);
        }

        // Verify we have 5 attachments
        $this->assertEquals(5, TicketAttachment::where('ticket_id', $ticket->id)->count());

        // Act - Try to upload 6th attachment
        $file = UploadedFile::fake()->create('sixth-file.pdf', 100);
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);

        // Verify still only 5 attachments
        $this->assertEquals(5, TicketAttachment::where('ticket_id', $ticket->id)->count());
    }

    /**
     * Test #8: File is stored in correct path
     *
     * Verifies that uploaded files are stored in the correct path:
     * storage/app/public/tickets/attachments/
     *
     * Expected: File exists in correct path
     */
    #[Test]
    public function file_is_stored_in_correct_path(): void
    {
        // Arrange
        Storage::fake('public');

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

        $file = UploadedFile::fake()->create('test-file.pdf', 100);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify file is stored in correct path
        $attachmentId = $response->json('data.id');
        $attachment = TicketAttachment::find($attachmentId);
        $fileName = basename($attachment->file_url);

        Storage::disk('public')->assertExists("tickets/attachments/{$fileName}");
    }

    /**
     * Test #9: Attachment record created with metadata
     *
     * Verifies that the attachment record is created with correct metadata:
     * - file_name
     * - file_url
     * - file_type
     * - file_size_bytes
     *
     * Expected: All metadata fields are populated correctly
     */
    #[Test]
    public function attachment_record_created_with_metadata(): void
    {
        // Arrange
        Storage::fake('public');

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

        $file = UploadedFile::fake()->create('report.pdf', 2048); // 2 MB

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'file_name',
                'file_url',
                'file_type',
                'file_size_bytes',
            ],
        ]);

        // Verify metadata in database
        $attachmentId = $response->json('data.id');
        $attachment = TicketAttachment::find($attachmentId);

        $this->assertNotNull($attachment->file_name);
        $this->assertNotNull($attachment->file_url);
        $this->assertNotNull($attachment->file_type);
        $this->assertNotNull($attachment->file_size_bytes);
        $this->assertGreaterThan(0, $attachment->file_size_bytes);
    }

    /**
     * Test #10: uploaded_by_user_id is set correctly
     *
     * Verifies that the uploaded_by_user_id field is set to the authenticated user.
     *
     * Expected: uploaded_by_user_id matches authenticated user
     */
    #[Test]
    public function uploaded_by_user_id_is_set_correctly(): void
    {
        // Arrange
        Storage::fake('public');

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

        $file = UploadedFile::fake()->create('document.pdf', 100);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.uploaded_by_user_id', $user->id);

        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by_user_id' => $user->id,
        ]);
    }

    /**
     * Test #11: attachment response_id is null when uploaded to ticket
     *
     * Verifies that when uploading directly to a ticket (not to a response),
     * the response_id field is NULL.
     *
     * Expected: response_id = null in database
     */
    #[Test]
    public function attachment_response_id_is_null_when_uploaded_to_ticket(): void
    {
        // Arrange
        Storage::fake('public');

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

        $file = UploadedFile::fake()->create('attachment.pdf', 100);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.response_id', null);

        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
            'response_id' => null,
        ]);
    }

    // ==================== GROUP 4: Validaciones de Acceso y Estado (Tests 12-15) ====================

    /**
     * Test #12: User cannot upload to other user ticket
     *
     * Verifies that a USER cannot upload attachments to tickets they don't own.
     *
     * Expected: 403 Forbidden
     * Database: No attachment should be created
     */
    #[Test]
    public function user_cannot_upload_to_other_user_ticket(): void
    {
        // Arrange
        Storage::fake('public');

        $userA = User::factory()->withRole('USER')->create();
        $userB = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // User B creates a ticket
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userB->id,
            'status' => 'open',
        ]);

        $file = UploadedFile::fake()->create('unauthorized.pdf', 100);

        // Act - User A tries to upload to User B's ticket
        $response = $this->authenticateWithJWT($userA)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseMissing('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by_user_id' => $userA->id,
        ]);
    }

    /**
     * Test #13: Agent cannot upload to other company ticket
     *
     * Verifies that an AGENT cannot upload attachments to tickets from a different company.
     *
     * Expected: 403 Forbidden
     * Database: No attachment should be created
     */
    #[Test]
    public function agent_cannot_upload_to_other_company_ticket(): void
    {
        // Arrange
        Storage::fake('public');

        $agentCompanyA = User::factory()->withRole('AGENT')->create();
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $agentCompanyA->assignRole('AGENT', $companyA->id);

        $companyB = Company::factory()->create(['name' => 'Company B']);
        $userCompanyB = User::factory()->withRole('USER')->create();
        $categoryB = Category::factory()->create([
            'company_id' => $companyB->id,
            'is_active' => true,
        ]);

        // Create ticket in Company B
        $ticket = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $userCompanyB->id,
            'status' => 'pending',
        ]);

        $file = UploadedFile::fake()->create('unauthorized.pdf', 100);

        // Act - Agent from Company A tries to upload to Company B ticket
        $response = $this->authenticateWithJWT($agentCompanyA)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseMissing('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by_user_id' => $agentCompanyA->id,
        ]);
    }

    /**
     * Test #14: Cannot upload to closed ticket
     *
     * Verifies that attachments cannot be uploaded to tickets with status 'closed'.
     *
     * Expected: 403 Forbidden
     * Database: No attachment should be created
     */
    #[Test]
    public function cannot_upload_to_closed_ticket(): void
    {
        // Arrange
        Storage::fake('public');

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
            'status' => 'closed', // Ticket is closed
            'closed_at' => now()->subDay(),
        ]);

        $file = UploadedFile::fake()->create('late-attachment.pdf', 100);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseMissing('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
        ]);
    }

    /**
     * Test #15: Unauthenticated user cannot upload
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     * Database: No attachment should be created
     */
    #[Test]
    public function unauthenticated_user_cannot_upload(): void
    {
        // Arrange
        Storage::fake('public');

        $company = Company::factory()->create();
        $user = User::factory()->withRole('USER')->create();
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

        $file = UploadedFile::fake()->create('unauthorized.pdf', 100);

        // Act - No authenticateWithJWT() call
        $response = $this->postJson("/api/tickets/{$ticket->ticket_code}/attachments", [
            'file' => $file,
        ]);

        // Assert
        $response->assertStatus(401);

        $this->assertDatabaseMissing('ticketing.ticket_attachments', [
            'ticket_id' => $ticket->id,
        ]);
    }
}
