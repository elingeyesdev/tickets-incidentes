<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear schema business para gestión de empresas
        DB::statement('CREATE SCHEMA IF NOT EXISTS business');

        // Crear enums en schema business
        // Eliminar primero para hacer la migración idempotente (puede ejecutarse múltiples veces)
        DB::statement("DROP TYPE IF EXISTS business.request_status CASCADE");
        DB::statement("
            CREATE TYPE business.request_status AS ENUM (
                'pending',
                'approved',
                'rejected'
            )
        ");

        DB::statement("DROP TYPE IF EXISTS business.publication_status CASCADE");
        DB::statement("
            CREATE TYPE business.publication_status AS ENUM (
                'draft',
                'published',
                'archived'
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar enums
        DB::statement('DROP TYPE IF EXISTS business.publication_status');
        DB::statement('DROP TYPE IF EXISTS business.request_status');

        // Eliminar schema (CASCADE elimina todos los objetos dentro de él)
        DB::statement('DROP SCHEMA IF EXISTS business CASCADE');
    }
};
