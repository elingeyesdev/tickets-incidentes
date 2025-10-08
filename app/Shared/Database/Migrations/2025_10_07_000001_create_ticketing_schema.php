<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration to create the ticketing schema for PostgreSQL
 *
 * This migration creates:
 * - Schema: ticketing
 * - ENUMs: ticket_status, author_type
 *
 * This schema will contain all ticketing-related tables in the future.
 * For now, we only create the schema structure to prepare the database architecture.
 *
 * According to: Modelado V7.0 líneas 22-25, 34-35
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create ticketing schema
        DB::statement('CREATE SCHEMA IF NOT EXISTS ticketing');

        // Add schema description
        DB::statement("
            COMMENT ON SCHEMA ticketing IS
            'Schema for ticket management system - tickets, responses, attachments, ratings'
        ");

        // Create ENUM for ticket status
        // According to Modelado V7.0 línea 34
        // Drop first to make migration idempotent
        DB::statement("DROP TYPE IF EXISTS ticketing.ticket_status CASCADE");
        DB::statement("
            CREATE TYPE ticketing.ticket_status AS ENUM (
                'open',
                'pending',
                'resolved',
                'closed'
            )
        ");

        DB::statement("
            COMMENT ON TYPE ticketing.ticket_status IS
            'Ticket lifecycle states: open (new), pending (agent responded), resolved (marked as solved), closed (final state)'
        ");

        // Create ENUM for author type
        // According to Modelado V7.0 línea 35
        DB::statement("DROP TYPE IF EXISTS ticketing.author_type CASCADE");
        DB::statement("
            CREATE TYPE ticketing.author_type AS ENUM (
                'user',
                'agent'
            )
        ");

        DB::statement("
            COMMENT ON TYPE ticketing.author_type IS
            'Type of author for ticket responses: user (customer) or agent (support staff)'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop ENUMs first
        DB::statement('DROP TYPE IF EXISTS ticketing.author_type CASCADE');
        DB::statement('DROP TYPE IF EXISTS ticketing.ticket_status CASCADE');

        // Drop schema (CASCADE drops all objects within it)
        DB::statement('DROP SCHEMA IF EXISTS ticketing CASCADE');
    }
};
