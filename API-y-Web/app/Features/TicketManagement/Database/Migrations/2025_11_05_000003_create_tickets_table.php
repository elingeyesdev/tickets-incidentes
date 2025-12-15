<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla ticketing.tickets - Centro del sistema de soporte
     * Estados: open -> pending (primera respuesta) -> resolved -> closed
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE ticketing.tickets (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                ticket_code VARCHAR(20) UNIQUE NOT NULL,

                -- Relaciones
                created_by_user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,
                company_id UUID NOT NULL REFERENCES business.companies(id) ON DELETE CASCADE,
                category_id UUID REFERENCES ticketing.categories(id) ON DELETE SET NULL,

                -- Contenido
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,

                -- Ciclo de vida
                status ticketing.ticket_status NOT NULL DEFAULT 'open',
                owner_agent_id UUID REFERENCES auth.users(id) ON DELETE SET NULL,
                last_response_author_type VARCHAR(20) DEFAULT 'none',

                -- Auditoría de ciclo de vida
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                first_response_at TIMESTAMPTZ,
                resolved_at TIMESTAMPTZ,
                closed_at TIMESTAMPTZ
            )
        ");

        // Comentarios de tabla
        DB::statement("COMMENT ON TABLE ticketing.tickets IS 'Tickets de soporte. Estados: open (recién creado) -> pending (primera respuesta de agente) -> resolved (solucionado) -> closed (cerrado o 7 días después de resolved)'");
        DB::statement("COMMENT ON COLUMN ticketing.tickets.ticket_code IS 'Código único del ticket (TKT-2025-00001)'");
        DB::statement("COMMENT ON COLUMN ticketing.tickets.created_by_user_id IS 'Usuario que creó el ticket'");
        DB::statement("COMMENT ON COLUMN ticketing.tickets.owner_agent_id IS 'Agente asignado (se asigna automáticamente al primer agente que responde)'");
        DB::statement("COMMENT ON COLUMN ticketing.tickets.last_response_author_type IS 'Tipo del autor de la última respuesta: none (sin respuestas), user (cliente), agent (agente). Se actualiza automáticamente por trigger.'");
        DB::statement("COMMENT ON COLUMN ticketing.tickets.status IS 'Estado actual del ticket'");
        DB::statement("COMMENT ON COLUMN ticketing.tickets.first_response_at IS 'Timestamp de la primera respuesta de un agente (para métrica de SLA)'");

        // Índices críticos para performance
        DB::statement("CREATE INDEX idx_tickets_company_id_status ON ticketing.tickets(company_id, status)");
        DB::statement("CREATE INDEX idx_tickets_created_by_user_id ON ticketing.tickets(created_by_user_id)");
        DB::statement("CREATE INDEX idx_tickets_owner_agent_id ON ticketing.tickets(owner_agent_id) WHERE owner_agent_id IS NOT NULL");
        DB::statement("CREATE INDEX idx_tickets_created_at ON ticketing.tickets(created_at DESC)");
        DB::statement("CREATE INDEX idx_tickets_status ON ticketing.tickets(status) WHERE status IN ('open', 'pending')");
        DB::statement("CREATE INDEX idx_tickets_category_id ON ticketing.tickets(category_id)");

        // Trigger para actualizar updated_at automáticamente
        DB::statement("
            CREATE TRIGGER trigger_update_tickets_updated_at
            BEFORE UPDATE ON ticketing.tickets
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");

        // Constraint: category solo se puede eliminar con SET NULL
        DB::statement("COMMENT ON CONSTRAINT uq_company_category_name ON ticketing.categories IS 'Garantiza que el nombre de categoría sea único por empresa'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS ticketing.tickets CASCADE');
    }
};