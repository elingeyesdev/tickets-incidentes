<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla ticketing.ticket_internal_notes
     * Notas privadas entre agentes (no visibles para el cliente)
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE ticketing.ticket_internal_notes (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
                agent_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,

                note_content TEXT NOT NULL,

                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Comentarios de tabla
        DB::statement("COMMENT ON TABLE ticketing.ticket_internal_notes IS 'Notas privadas entre agentes. NO son visibles para el cliente. Útil para colaboración y seguimiento interno.'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_internal_notes.agent_id IS 'Agente que escribió la nota'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_internal_notes.note_content IS 'Contenido de la nota interna'");

        // Índices
        DB::statement("CREATE INDEX idx_ticket_internal_notes_ticket_id ON ticketing.ticket_internal_notes(ticket_id)");
        DB::statement("CREATE INDEX idx_ticket_internal_notes_agent_id ON ticketing.ticket_internal_notes(agent_id)");
        DB::statement("CREATE INDEX idx_ticket_internal_notes_created_at ON ticketing.ticket_internal_notes(created_at DESC)");

        // Trigger para actualizar updated_at automáticamente
        DB::statement("
            CREATE TRIGGER trigger_update_ticket_internal_notes_updated_at
            BEFORE UPDATE ON ticketing.ticket_internal_notes
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS ticketing.ticket_internal_notes CASCADE');
    }
};
