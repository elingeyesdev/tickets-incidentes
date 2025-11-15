<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega la columna updated_at a ticket_responses
     * Necesaria según documento oficial tickets-feature-maping.md
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE ticketing.ticket_responses
            ADD COLUMN updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        ");

        // Crear trigger para auto-actualizar updated_at
        DB::statement("
            CREATE OR REPLACE FUNCTION ticketing.update_ticket_response_updated_at()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trigger_update_ticket_response_updated_at
            BEFORE UPDATE ON ticketing.ticket_responses
            FOR EACH ROW
            EXECUTE FUNCTION ticketing.update_ticket_response_updated_at()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS trigger_update_ticket_response_updated_at ON ticketing.ticket_responses");
        DB::statement("DROP FUNCTION IF EXISTS ticketing.update_ticket_response_updated_at()");
        DB::statement("ALTER TABLE ticketing.ticket_responses DROP COLUMN IF EXISTS updated_at");
    }
};
