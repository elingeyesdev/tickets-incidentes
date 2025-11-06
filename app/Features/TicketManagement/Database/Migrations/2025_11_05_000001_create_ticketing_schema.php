<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea el schema 'ticketing' y sus tipos ENUM
     * Este es el primer paso para la feature de Ticketing
     */
    public function up(): void
    {
        // Crear schema ticketing
        DB::statement('CREATE SCHEMA IF NOT EXISTS ticketing');

        // Comentar el schema
        DB::statement("COMMENT ON SCHEMA ticketing IS 'Gestión de tickets de soporte del sistema helpdesk'");

        // Crear ENUM: ticket_status
        DB::statement("DROP TYPE IF EXISTS ticketing.ticket_status CASCADE");
        DB::statement("
            CREATE TYPE ticketing.ticket_status AS ENUM (
                'open',
                'pending',
                'resolved',
                'closed'
            )
        ");
        DB::statement("COMMENT ON TYPE ticketing.ticket_status IS 'Estados de ticket: open (recién creado), pending (con respuesta de agente), resolved (solucionado), closed (cerrado)'");

        // Crear ENUM: author_type
        DB::statement("DROP TYPE IF EXISTS ticketing.author_type CASCADE");
        DB::statement("
            CREATE TYPE ticketing.author_type AS ENUM (
                'user',
                'agent'
            )
        ");
        DB::statement("COMMENT ON TYPE ticketing.author_type IS 'Tipo de autor: user (cliente), agent (agente de soporte)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TYPE IF EXISTS ticketing.author_type CASCADE');
        DB::statement('DROP TYPE IF EXISTS ticketing.ticket_status CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS ticketing CASCADE');
    }
};