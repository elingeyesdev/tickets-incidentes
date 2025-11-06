<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla ticketing.ticket_responses
     * Conversación pública entre cliente y agentes en un ticket
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE ticketing.ticket_responses (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
                author_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,

                response_content TEXT NOT NULL,

                -- Para diferenciar si responde un 'user' o 'agent'
                author_type ticketing.author_type NOT NULL,

                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Comentarios de tabla
        DB::statement("COMMENT ON TABLE ticketing.ticket_responses IS 'Conversación pública en el ticket. Visible tanto para cliente como para agentes.'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_responses.author_id IS 'Usuario que escribió la respuesta (cliente o agente)'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_responses.author_type IS 'Tipo de autor: user (cliente) o agent (agente de soporte)'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_responses.response_content IS 'Contenido de la respuesta (puede incluir HTML/Markdown)'");

        // Índices
        DB::statement("CREATE INDEX idx_ticket_responses_ticket_id ON ticketing.ticket_responses(ticket_id)");
        DB::statement("CREATE INDEX idx_ticket_responses_author_id ON ticketing.ticket_responses(author_id)");
        DB::statement("CREATE INDEX idx_ticket_responses_created_at ON ticketing.ticket_responses(created_at DESC)");
        DB::statement("CREATE INDEX idx_ticket_responses_author_agent ON ticketing.ticket_responses(author_id) WHERE author_type = 'agent'");

        // Trigger para asignación automática de owner_agent_id cuando un agente responde
        DB::statement("
            CREATE TRIGGER trigger_assign_ticket_owner
            AFTER INSERT ON ticketing.ticket_responses
            FOR EACH ROW EXECUTE FUNCTION ticketing.assign_ticket_owner_function()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS ticketing.ticket_responses CASCADE');
    }
};