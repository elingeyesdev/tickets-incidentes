<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Attachments;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Deleting Attachments
 *
 * Tests the endpoint DELETE /api/tickets/:code/attachments/:id
 *
 * Coverage:
 * - Uploader can delete attachment within 30-minute window
 * - Cannot delete after 30 minutes (403)
 * - Deleting removes file from storage
 * - User cannot delete other user's attachment (403)
 * - Agent cannot delete user's attachment (403)
 * - Cannot delete from closed ticket (403)
 * - Deleted attachment returns 404 on subsequent requests
 * - Unauthenticated user cannot delete (401)
 *
 * Database Schema: ticketing.ticket_attachments
 * - Deletion removes record and file from storage
 * - 30-minute edit window enforced from created_at
 * - Uploader-only permission enforced by uploaded_by_user_id
 *
 * Expected Status Codes:
 * - 200: Attachment deleted successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (not uploader, after 30 minutes, closed ticket)
 * - 404: Attachment not found
 */
class DeleteAttachmentTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Operaciones Exitosas (Tests 1-3) ====================

    /**
     * Test #1: Uploader can delete attachment within 30 minutes
     *
     * Verifies that the user who uploaded the attachment can delete it
     * within the 30-minute edit window.
     *
     * Expected: 200 OK
     * Database: Attachment record should be deleted
     * Storage: File should be removed from storage
     */
    #[Test]
    public function uploader_can_delete_attachment_within_30_minutes(): void
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

        // Create attachment with created_at set to 5 minutes ago (within 30-minute window)
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'deletable.pdf',
            'file_url' => 'tickets/attachments/deletable.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'created_at' => Carbon::now()->subMinutes(5), // ← Within 30-minute window
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/attachments/{$attachment->id}");

        // Assert
        $response->assertStatus(200);

        // Verify attachment is deleted from database
        $this->assertDatabaseMissing('ticketing.ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    /**
     * Test #2: Cannot delete attachment after 30 minutes
     *
     * Verifies that attachments cannot be deleted after the 30-minute
     * edit window has expired.
     *
     * Expected: 403 Forbidden (at 35 minutes)
     * Database: Attachment record should remain
     */
    #[Test]
    public function cannot_delete_attachment_after_30_minutes(): void
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

        // Create attachment with created_at set to 35 minutes ago (past 30-minute limit)
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'old-attachment.pdf',
            'file_url' => 'tickets/attachments/old-attachment.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'created_at' => Carbon::now()->subMinutes(35), // ← Outside 30-minute window
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/attachments/{$attachment->id}");

        // Assert
        $response->assertStatus(403);

        // Verify attachment still exists in database
        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    /**
     * Test #3: Deleting attachment removes file from storage
     *
     * Verifies that when an attachment is deleted, the physical file
     * is also removed from storage (storage/app/public/tickets/attachments/).
     *
     * Expected: 200 OK and file no longer exists in storage
     * Storage: File should be deleted from disk
     */
    #[Test]
    public function deleting_attachment_removes_file_from_storage(): void
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

        // Create attachment
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'storage-test.pdf',
            'file_url' => 'tickets/attachments/storage-test.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 2048,
            'created_at' => Carbon::now()->subMinutes(10),
        ]);

        // Create fake file in storage
        Storage::disk('public')->put('tickets/attachments/storage-test.pdf', 'fake file content');

        // Verify file exists before deletion
        Storage::disk('public')->assertExists('tickets/attachments/storage-test.pdf');

        // Act
        $response = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/attachments/{$attachment->id}");

        // Assert
        $response->assertStatus(200);

        // Verify file is deleted from storage
        Storage::disk('public')->assertMissing('tickets/attachments/storage-test.pdf');

        // Verify attachment is deleted from database
        $this->assertDatabaseMissing('ticketing.ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    // ==================== GROUP 2: Validaciones de Permisos (Tests 4-5) ====================

    /**
     * Test #4: User cannot delete other user's attachment
     *
     * Verifies that User A cannot delete an attachment uploaded by User B.
     *
     * Expected: 403 Forbidden
     * Database: Attachment should remain
     */
    #[Test]
    public function user_cannot_delete_other_user_attachment(): void
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

        // User B creates ticket and uploads attachment
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $userB->id,
            'status' => 'open',
        ]);

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $userB->id, // ← Uploaded by User B
            'file_name' => 'user-b-file.pdf',
            'file_url' => 'tickets/attachments/user-b-file.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        // Act - User A tries to delete User B's attachment
        $response = $this->authenticateWithJWT($userA)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/attachments/{$attachment->id}");

        // Assert
        $response->assertStatus(403);

        // Verify attachment still exists
        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    /**
     * Test #5: Agent cannot delete user's attachment
     *
     * Verifies that an AGENT cannot delete an attachment uploaded by a USER.
     * Attachments are uploader-only, not role-based.
     *
     * Expected: 403 Forbidden
     * Database: Attachment should remain
     */
    #[Test]
    public function agent_cannot_delete_user_attachment(): void
    {
        // Arrange
        Storage::fake('public');

        $agent = User::factory()->withRole('AGENT')->create();
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

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

        // User uploads attachment
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id, // ← Uploaded by USER
            'file_name' => 'user-attachment.pdf',
            'file_url' => 'tickets/attachments/user-attachment.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        // Act - Agent tries to delete user's attachment
        $response = $this->authenticateWithJWT($agent)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/attachments/{$attachment->id}");

        // Assert
        $response->assertStatus(403);

        // Verify attachment still exists
        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    // ==================== GROUP 3: Validaciones de Estado (Test 6) ====================

    /**
     * Test #6: Cannot delete attachment if ticket closed
     *
     * Verifies that attachments cannot be deleted from closed tickets,
     * even within the 30-minute edit window.
     *
     * Expected: 403 Forbidden
     * Database: Attachment should remain
     */
    #[Test]
    public function cannot_delete_attachment_if_ticket_closed(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create closed ticket
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'closed', // ← Ticket is closed
            'closed_at' => now()->subDay(),
        ]);

        // Create attachment within 30-minute window
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'closed-ticket-file.pdf',
            'file_url' => 'tickets/attachments/closed-ticket-file.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'created_at' => Carbon::now()->subMinutes(5), // ← Within 30-minute window
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/attachments/{$attachment->id}");

        // Assert
        $response->assertStatus(403);

        // Verify attachment still exists
        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    // ==================== GROUP 4: Validaciones de Estado POST-DELETE (Test 7) ====================

    /**
     * Test #7: Deleted attachment returns 404
     *
     * Verifies that after an attachment is successfully deleted,
     * attempting to retrieve or delete it again returns 404.
     *
     * Expected: 404 Not Found on subsequent DELETE request
     */
    #[Test]
    public function deleted_attachment_returns_404(): void
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

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'to-be-deleted.pdf',
            'file_url' => 'tickets/attachments/to-be-deleted.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        // Act - First delete (should succeed)
        $firstDelete = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/attachments/{$attachment->id}");

        // Assert - First delete succeeds
        $firstDelete->assertStatus(200);

        // Act - Second delete (should fail with 404)
        $secondDelete = $this->authenticateWithJWT($user)
            ->deleteJson("/api/tickets/{$ticket->ticket_code}/attachments/{$attachment->id}");

        // Assert - Second delete fails with 404
        $secondDelete->assertStatus(404);
    }

    // ==================== GROUP 5: Autenticación (Test 8) ====================

    /**
     * Test #8: Unauthenticated user cannot delete
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     * Database: Attachment should remain
     */
    #[Test]
    public function unauthenticated_user_cannot_delete(): void
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

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'protected-file.pdf',
            'file_url' => 'tickets/attachments/protected-file.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        // Act - No JWT authentication
        $response = $this->deleteJson("/api/tickets/{$ticket->ticket_code}/attachments/{$attachment->id}");

        // Assert
        $response->assertStatus(401);

        // Verify attachment still exists
        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }
}
