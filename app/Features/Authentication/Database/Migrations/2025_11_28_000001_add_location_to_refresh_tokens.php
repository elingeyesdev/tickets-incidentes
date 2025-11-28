<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para agregar columna location a refresh_tokens
 *
 * Almacena información de geolocalización del dispositivo
 * en el momento exacto del login/sesión.
 *
 * Estructura JSONB:
 * {
 *   "city": "Buenos Aires",
 *   "country": "Argentina",
 *   "country_code": "AR",
 *   "latitude": -34.6037,
 *   "longitude": -58.3816,
 *   "timezone": "America/Argentina/Buenos_Aires"
 * }
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
            ALTER TABLE auth.refresh_tokens
            ADD COLUMN location JSONB DEFAULT NULL
        ');

        // Comentario de columna
        DB::statement("
            COMMENT ON COLUMN auth.refresh_tokens.location IS
            'Información de geolocalización en JSON: city, country, country_code, latitude, longitude, timezone'
        ");

        // Índice para búsquedas por país (útil para seguridad)
        DB::statement("
            CREATE INDEX idx_refresh_tokens_location_country
            ON auth.refresh_tokens USING GIN (location)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE auth.refresh_tokens DROP COLUMN IF EXISTS location');
        DB::statement('DROP INDEX IF EXISTS idx_refresh_tokens_location_country');
    }
};
