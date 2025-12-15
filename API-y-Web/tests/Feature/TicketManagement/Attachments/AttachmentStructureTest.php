<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Attachments;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\AuthorType;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Attachment Structure Validation
 *
 * Tests the database structure and relationships of ticket attachments.
 *
 * Coverage:
 * - ticket_id is NOT NULL (required constraint)
 * - response_id is NULLABLE (optional constraint)
 * - response_id must reference valid response if provided
 * - Multiple attachments per ticket relationship (1:N)
 *
 * Database Schema: ticketing.ticket_attachments
 * - id: UUID (auto-generated)
 * - ticket_id: UUID NOT NULL (FK to ticketing.tickets)
 * - response_id: UUID NULLABLE (FK to ticketing.ticket_responses)
 * - uploaded_by_user_id: UUID NOT NULL (FK to auth.users)
 * - file_name: VARCHAR(255) NOT NULL
 * - file_path: VARCHAR(500) NOT NULL
 * - file_type: VARCHAR(100) NULLABLE
 * - file_size_bytes: BIGINT NULLABLE
 * - created_at: TIMESTAMPTZ
 */
class AttachmentStructureTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test #1: ticket_id must be NOT NULL
     *
     * Validates that every attachment MUST have a ticket_id.
     * An attachment cannot exist without being linked to a ticket.
     *
     * Expected: Database constraint violation when trying to create attachment without ticket_id
     * Database: Should NOT persist attachment
     */
    #[Test]
    public function attachment_must_have_ticket_id_not_null(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        // Attempt to create attachment without ticket_id should fail
        $this->expectException(QueryException::class);

        TicketAttachment::create([
            'ticket_id' => null, // ← NOT NULL constraint violation
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'test.pdf',
            'file_path' => 'tickets/attachments/test.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 12345,
        ]);
    }

    /**
     * Test #2: response_id can be NULL
     *
     * Validates that an attachment CAN exist without a response_id.
     * This occurs when a file is uploaded directly to the ticket
     * (not attached to a specific response).
     *
     * Expected: Attachment created successfully with response_id = null
     * Database: Should persist attachment with null response_id
     */
    #[Test]
    public function attachment_can_exist_without_response_id(): void
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
        ]);

        // Act
        // Create attachment directly on ticket (no response association)
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null, // ← NULLABLE, this is valid
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'direct_attachment.pdf',
            'file_path' => 'tickets/attachments/direct_attachment.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 54321,
        ]);

        // Assert
        $this->assertNull($attachment->response_id);
        $this->assertNotNull($attachment->ticket_id);
        $this->assertEquals($ticket->id, $attachment->ticket_id);

        // Verify persistence in database
        $this->assertDatabaseHas('ticketing.ticket_attachments', [
            'id' => $attachment->id,
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'file_name' => 'direct_attachment.pdf',
        ]);

        // Verify relationship works correctly
        $this->assertInstanceOf(Ticket::class, $attachment->ticket);
        $this->assertNull($attachment->response);
    }

    /**
     * Test #3: response_id must reference valid response from same ticket
     *
     * Validates that if response_id is provided:
     * - The response must exist in the database
     * - The response must belong to the SAME ticket
     *
     * Expected: Cannot create attachment with response_id from different ticket
     * Database: Should NOT persist attachment with mismatched response_id
     */
    #[Test]
    public function attachment_response_id_must_reference_valid_response(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->withRole('USER')->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create TWO separate tickets
        $ticket1 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        $ticket2 = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // Create responses for EACH ticket
        $responseTicket1 = TicketResponse::create([
            'ticket_id' => $ticket1->id,
            'author_id' => $agent->id,
            'author_type' => AuthorType::AGENT,
            'content' => 'Response for ticket 1',
        ]);

        $responseTicket2 = TicketResponse::create([
            'ticket_id' => $ticket2->id,
            'author_id' => $agent->id,
            'author_type' => AuthorType::AGENT,
            'content' => 'Response for ticket 2',
        ]);

        // Act & Assert
        // Attempt to create attachment for ticket1 with response from ticket2
        $this->expectException(QueryException::class);

        TicketAttachment::create([
            'ticket_id' => $ticket1->id,
            'response_id' => $responseTicket2->id, // ← Response belongs to ticket2, not ticket1
            'uploaded_by_user_id' => $agent->id,
            'file_name' => 'mismatched.pdf',
            'file_path' => 'tickets/attachments/mismatched.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 11111,
        ]);

        // Note: PostgreSQL check constraint or trigger should prevent this
        // If no DB-level constraint exists, this test documents the expected behavior
        // for when such constraint is added
    }

    /**
     * Test #4: Multiple attachments per ticket relationship (1:N)
     *
     * Validates that a single ticket can have multiple attachments.
     * Tests the 1:N relationship between tickets and attachments.
     *
     * Expected: All 5 attachments should be created successfully
     * Database: Should persist 5 attachments with same ticket_id
     * Relationship: ticket.attachments should return collection of 5
     */
    #[Test]
    public function multiple_attachments_per_ticket_relationship(): void
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
        ]);

        // Act
        // Create 5 attachments for the SAME ticket
        $createdAttachments = [];
        foreach (range(1, 5) as $i) {
            $attachment = TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'response_id' => null,
                'uploaded_by_user_id' => $user->id,
                'file_name' => "file{$i}.pdf",
                'file_path' => "tickets/attachments/file{$i}.pdf",
                'file_type' => 'application/pdf',
                'file_size_bytes' => 1000 * $i,
            ]);

            $createdAttachments[] = $attachment;
        }

        // Assert
        // Verify all attachments were created
        $this->assertCount(5, $createdAttachments);

        // Verify all have the same ticket_id
        foreach ($createdAttachments as $attachment) {
            $this->assertEquals($ticket->id, $attachment->ticket_id);
        }

        // Verify database count
        $dbAttachments = TicketAttachment::where('ticket_id', $ticket->id)->get();
        $this->assertCount(5, $dbAttachments);

        // Verify all attachments belong to the ticket
        $this->assertTrue(
            $dbAttachments->every(fn ($a) => $a->ticket_id === $ticket->id)
        );

        // Verify relationship from ticket side
        $ticket->refresh();
        $this->assertCount(5, $ticket->attachments);

        // Verify each attachment is in the database
        foreach (range(1, 5) as $i) {
            $this->assertDatabaseHas('ticketing.ticket_attachments', [
                'ticket_id' => $ticket->id,
                'file_name' => "file{$i}.pdf",
            ]);
        }
    }
}
