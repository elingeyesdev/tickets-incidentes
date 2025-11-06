<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla ticketing.ticket_ratings
     * Calificaciones de satisfacción del cliente sobre tickets
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE ticketing.ticket_ratings (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                ticket_id UUID NOT NULL UNIQUE REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
                customer_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,

                -- Guardamos el agente al momento de la calificación (histórico)
                rated_agent_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,

                rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
                comment TEXT,

                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Comentarios de tabla
        DB::statement("COMMENT ON TABLE ticketing.ticket_ratings IS 'Calificaciones de satisfacción del cliente sobre la resolución del ticket.'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_ratings.ticket_id IS 'Referencia única al ticket (un ticket solo puede tener una calificación)'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_ratings.customer_id IS 'Cliente que calificó'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_ratings.rated_agent_id IS 'Agente que fue calificado (almacenado históricamente, independiente de cambios futuros)'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_ratings.rating IS 'Calificación numérica de 1 a 5 estrellas'");

        // Índices
        DB::statement("CREATE INDEX idx_ticket_ratings_ticket_id ON ticketing.ticket_ratings(ticket_id)");
        DB::statement("CREATE INDEX idx_ticket_ratings_customer_id ON ticketing.ticket_ratings(customer_id)");
        DB::statement("CREATE INDEX idx_ticket_ratings_agent_id ON ticketing.ticket_ratings(rated_agent_id)");
        DB::statement("CREATE INDEX idx_ticket_ratings_rating ON ticketing.ticket_ratings(rating)");
        DB::statement("CREATE INDEX idx_ticket_ratings_created_at ON ticketing.ticket_ratings(created_at DESC)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS ticketing.ticket_ratings CASCADE');
    }
};