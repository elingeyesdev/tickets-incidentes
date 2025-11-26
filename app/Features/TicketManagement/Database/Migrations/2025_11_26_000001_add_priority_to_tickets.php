<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear ENUM para prioridad (solo si no existe)
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ticket_priority') THEN
                    CREATE TYPE ticketing.ticket_priority AS ENUM ('low', 'medium', 'high');
                END IF;
            END$$;
        ");

        // Agregar columna priority a tickets
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->string('priority', 20)
                ->default('medium')
                ->after('description')
                ->comment('Prioridad: low, medium, high');
        });

        // Índice parcial para búsquedas de alta prioridad
        DB::statement(
            "CREATE INDEX IF NOT EXISTS idx_tickets_priority ON ticketing.tickets(priority)
             WHERE priority = 'high'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ticketing.idx_tickets_priority');

        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->dropColumn('priority');
        });

        DB::statement('DROP TYPE IF EXISTS ticketing.ticket_priority');
    }
};
