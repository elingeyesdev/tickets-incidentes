<?php

namespace Tests\Unit\TicketManagement\Models;

use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas unitarias para campos específicos del modelo Ticket
 *
 * Valida casts específicos de campos críticos para UI y tracking.
 *
 * Total: 1 prueba
 */
class TicketFieldsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test #9: Verifica que el campo last_response_author_type se castea como string
     *
     * Campo crítico para UI que indica quién respondió último:
     * - 'none': Sin respuestas
     * - 'user': Última respuesta del cliente
     * - 'agent': Última respuesta del agente
     */
    #[Test]
    public function casts_last_response_author_type_as_string(): void
    {
        // Arrange & Act - Create ticket with last_response_author_type as 'agent'
        $ticket = Ticket::factory()->create([
            'last_response_author_type' => 'agent',
        ]);

        // Assert - Should be string, not enum
        $this->assertIsString($ticket->last_response_author_type);
        $this->assertEquals('agent', $ticket->last_response_author_type);

        // Test with 'user' value
        $ticket2 = Ticket::factory()->create([
            'last_response_author_type' => 'user',
        ]);

        $this->assertIsString($ticket2->last_response_author_type);
        $this->assertEquals('user', $ticket2->last_response_author_type);

        // Test with 'none' value (default)
        $ticket3 = Ticket::factory()->create([
            'last_response_author_type' => 'none',
        ]);

        $this->assertIsString($ticket3->last_response_author_type);
        $this->assertEquals('none', $ticket3->last_response_author_type);

        // Verify it persists correctly in database
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'last_response_author_type' => 'agent',
        ]);
    }
}
