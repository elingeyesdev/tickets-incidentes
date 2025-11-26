<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Reminders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Sending Ticket Reminders
 *
 * Tests the endpoint POST /api/tickets/:code/remind
 *
 * Coverage:
 * - Authentication (unauthenticated, USER, AGENT)
 * - Authorization (only AGENT of same company can send)
 * - Ticket not found returns 404
 * - Success returns 200 with message
 *
 * Expected Status Codes:
 * - 200: Reminder sent successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (USER, AGENT from other company)
 * - 404: Ticket not found
 *
 * Business Rule: Only AGENTs can send reminders to ticket creators
 */
class SendTicketReminderTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication & Authorization (Tests 1-5) ====================

    /**
     * Test #1: Unauthenticated user cannot send reminder
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_send_reminder(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // Act - No authenticateWithJWT() call
        $response = $this->postJson("/api/tickets/{$ticket->ticket_code}/remind");

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test #2: USER cannot send reminder
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function user_cannot_send_reminder(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/remind");

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #3: AGENT can send reminder to ticket creator
     *
     * Expected: 200 OK with success message
     */
    #[Test]
    public function agent_can_send_reminder_to_ticket_creator(): void
    {
        // Arrange
        Mail::fake();

        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/remind");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Recordatorio enviado exitosamente');
    }

    /**
     * Test #4: AGENT cannot send reminder to ticket from another company
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function agent_cannot_send_reminder_to_ticket_from_another_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $agentA = User::factory()->create();
        $agentA->assignRole('AGENT', $companyA->id);

        $user = User::factory()->withRole('USER')->create();
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($agentA)
            ->postJson("/api/tickets/{$ticketB->ticket_code}/remind");

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test #5: COMPANY_ADMIN can send reminder
     *
     * Expected: 200 OK (COMPANY_ADMIN has AGENT permissions in their company)
     */
    #[Test]
    public function company_admin_can_send_reminder(): void
    {
        // Arrange
        Mail::fake();

        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $companyId]);

        $ticket = Ticket::factory()->create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/tickets/{$ticket->ticket_code}/remind");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Recordatorio enviado exitosamente');
    }

    // ==================== GROUP 2: Ticket Not Found (Test 6) ====================

    /**
     * Test #6: Ticket not found returns 404
     *
     * Expected: 404 Not Found
     */
    #[Test]
    public function ticket_not_found_returns_404(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        // Act - Use non-existent ticket code
        $response = $this->authenticateWithJWT($agent)
            ->postJson('/api/tickets/TKT-2025-99999/remind');

        // Assert
        $response->assertStatus(404);
    }

    // ==================== GROUP 3: Email Sending (Test 7) ====================

    /**
     * Test #7: Reminder sends email to ticket creator
     *
     * Expected: 200 OK and email is sent
     */
    #[Test]
    public function reminder_sends_email_to_ticket_creator(): void
    {
        // Arrange
        Mail::fake();

        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/remind");

        // Assert
        $response->assertStatus(200);

        Mail::assertSent(\App\Features\TicketManagement\Mail\TicketReminderMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    // ==================== GROUP 4: Multiple Reminders (Test 8) ====================

    /**
     * Test #8: Can send multiple reminders to same ticket
     *
     * Expected: 200 OK each time (idempotent)
     */
    #[Test]
    public function can_send_multiple_reminders_to_same_ticket(): void
    {
        // Arrange
        Mail::fake();

        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // Act - Send first reminder
        $response1 = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/remind");

        // Act - Send second reminder
        $response2 = $this->authenticateWithJWT($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/remind");

        // Assert - Both should succeed
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Verify 2 emails sent
        Mail::assertSent(\App\Features\TicketManagement\Mail\TicketReminderMail::class, 2);
    }
}
