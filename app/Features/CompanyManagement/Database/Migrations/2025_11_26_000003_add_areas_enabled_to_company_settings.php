<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega el campo `areas_enabled` al JSONB settings de companies.
     * Por defecto está en FALSE (las empresas deben activarlo explícitamente).
     */
    public function up(): void
    {
        // Actualizar todas las empresas existentes para agregar areas_enabled: false
        DB::unprepared("
            UPDATE business.companies
            SET settings = COALESCE(settings, '{}'::jsonb) || '{\"areas_enabled\": false}'::jsonb
            WHERE settings IS NULL OR NOT (settings ? 'areas_enabled')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover areas_enabled de settings
        DB::unprepared("
            UPDATE business.companies
            SET settings = settings - 'areas_enabled'
            WHERE settings ? 'areas_enabled'
        ");
    }
};
