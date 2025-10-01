<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para crear el schema 'auth' de PostgreSQL
 *
 * Este schema contendrá todas las tablas relacionadas con autenticación y usuarios:
 * - users
 * - user_profiles
 * - roles
 * - user_roles
 * - refresh_tokens (Authentication feature)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear schema auth en PostgreSQL
        DB::statement('CREATE SCHEMA IF NOT EXISTS auth');

        // Comentario descriptivo del schema
        DB::statement("COMMENT ON SCHEMA auth IS 'Schema para autenticación y gestión de usuarios'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar schema auth (CASCADE elimina todas las tablas dentro)
        DB::statement('DROP SCHEMA IF EXISTS auth CASCADE');
    }
};