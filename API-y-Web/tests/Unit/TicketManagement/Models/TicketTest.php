<?php

namespace Tests\Unit\TicketManagement\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas unitarias para el modelo Ticket
 *
 * Valida relaciones, casts y scopes del modelo Ticket.
 * Los tests de modelos documentan la estructura esperada por Feature Tests.
 *
 * Total: 8 pruebas
 */
class TicketTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test #1: Verifica que el campo status se convierte al enum TicketStatus
     */
    #[Test]
    public function status_casts_to_enum(): void
    {
        // Arrange & Act
        $ticket = Ticket::factory()->create([
            'status' => 'open',
        ]);

        // Assert
        $this->assertInstanceOf(TicketStatus::class, $ticket->status);
        $this->assertEquals(TicketStatus::OPEN, $ticket->status);
        $this->assertEquals('open', $ticket->status->value);
    }

    /**
     * Test #2: Verifica la relación belongsTo con el usuario creador
     */
    #[Test]
    public function belongs_to_creator(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $ticket = Ticket::factory()->create([
            'created_by_user_id' => $user->id,
        ]);

        // Assert
        $this->assertInstanceOf(User::class, $ticket->creator);
        $this->assertEquals($user->id, $ticket->creator->id);
        $this->assertEquals($user->email, $ticket->creator->email);
    }

    /**
     * Test #3: Verifica la relación belongsTo con el agente owner
     */
    #[Test]
    public function belongs_to_owner_agent(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();

        // Act
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'owner_agent_id' => $agent->id,
        ]);

        // Assert
        $this->assertInstanceOf(User::class, $ticket->ownerAgent);
        $this->assertEquals($agent->id, $ticket->ownerAgent->id);
        $this->assertEquals($agent->email, $ticket->ownerAgent->email);
    }

    /**
     * Test #4: Verifica la relación belongsTo con Company
     */
    #[Test]
    public function belongs_to_company(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
        ]);

        // Assert
        $this->assertInstanceOf(Company::class, $ticket->company);
        $this->assertEquals($company->id, $ticket->company->id);
        $this->assertEquals($company->name, $ticket->company->name);
    }

    /**
     * Test #5: Verifica la relación belongsTo con Category
     */
    #[Test]
    public function belongs_to_category(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
        ]);

        // Act
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        // Assert
        $this->assertInstanceOf(Category::class, $ticket->category);
        $this->assertEquals($category->id, $ticket->category->id);
        $this->assertEquals($category->name, $ticket->category->name);
    }

    /**
     * Test #6: Verifica la relación hasMany con TicketResponse
     */
    #[Test]
    public function has_many_responses(): void
    {
        // Arrange
        $ticket = Ticket::factory()->create();

        // Act - Create 3 responses for the ticket
        TicketResponse::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
        ]);

        // Assert
        $this->assertInstanceOf(Collection::class, $ticket->responses);
        $this->assertCount(3, $ticket->responses);

        foreach ($ticket->responses as $response) {
            $this->assertInstanceOf(TicketResponse::class, $response);
            $this->assertEquals($ticket->id, $response->ticket_id);
        }
    }

    /**
     * Test #7: Verifica que el scope open() filtra tickets con status='open'
     */
    #[Test]
    public function open_scope(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Create tickets with different statuses
        $openTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status' => 'open',
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status' => 'pending',
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status' => 'closed',
        ]);

        // Act
        $openTickets = Ticket::open()->get();

        // Assert
        $this->assertCount(1, $openTickets);
        $this->assertEquals($openTicket->id, $openTickets->first()->id);
        $this->assertEquals(TicketStatus::OPEN, $openTickets->first()->status);
        $this->assertEquals('open', $openTickets->first()->status->value);
    }

    /**
     * Test #8: Verifica que el scope pending() filtra tickets con status='pending'
     */
    #[Test]
    public function pending_scope(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Create tickets with different statuses
        Ticket::factory()->create([
            'company_id' => $company->id,
            'status' => 'open',
        ]);

        $pendingTicket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status' => 'pending',
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status' => 'closed',
        ]);

        // Act
        $pendingTickets = Ticket::pending()->get();

        // Assert
        $this->assertCount(1, $pendingTickets);
        $this->assertEquals($pendingTicket->id, $pendingTickets->first()->id);
        $this->assertEquals(TicketStatus::PENDING, $pendingTickets->first()->status);
        $this->assertEquals('pending', $pendingTickets->first()->status->value);
    }
}
