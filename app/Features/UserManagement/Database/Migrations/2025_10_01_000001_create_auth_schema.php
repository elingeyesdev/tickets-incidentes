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
 *
 * IMPORTANTE: También crea el ENUM TYPE auth.user_status
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

        // Crear ENUM TYPE para status de usuarios
        // Según Modelado V7.0 línea 31
        DB::statement("
            CREATE TYPE auth.user_status AS ENUM (
                'active',
                'suspended',
                'deleted'
            )
        ");

        // Comentario del TYPE
        DB::statement("
            COMMENT ON TYPE auth.user_status IS
            'Estados posibles de un usuario: active (activo), suspended (suspendido), deleted (eliminado)'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar TYPE primero
        DB::statement('DROP TYPE IF EXISTS auth.user_status CASCADE');

        // Eliminar schema auth (CASCADE elimina todas las tablas dentro)
        DB::statement('DROP SCHEMA IF EXISTS auth CASCADE');
    }
};