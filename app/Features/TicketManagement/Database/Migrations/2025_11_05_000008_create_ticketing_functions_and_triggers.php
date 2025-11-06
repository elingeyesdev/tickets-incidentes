<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea índices adicionales para reportes y métricas
     * La función assign_ticket_owner_function se crea en la migración 000002
     */
    public function up(): void
    {
        // Crear índices para búsquedas comunes en reportes y análisis
        DB::statement("CREATE INDEX idx_ticket_views_by_agent_rating ON ticketing.ticket_ratings(rated_agent_id, rating) WHERE rating >= 4");
        DB::statement("CREATE INDEX idx_tickets_resolved_closed_at ON ticketing.tickets(resolved_at, closed_at) WHERE status IN ('resolved', 'closed')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ticketing.idx_ticket_views_by_agent_rating CASCADE');
        DB::statement('DROP INDEX IF EXISTS ticketing.idx_tickets_resolved_closed_at CASCADE');
    }
};
