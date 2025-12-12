<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration para actualizar el enum 'type' de la tabla service_api_keys.
 * 
 * Cambia de: live, test
 * A: production, development, testing
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL requiere un enfoque diferente para alterar enums
        // Primero eliminar cualquier constraint existente
        DB::statement("ALTER TABLE service_api_keys DROP CONSTRAINT IF EXISTS service_api_keys_type_check");
        
        // Cambiar la columna a VARCHAR
        DB::statement("ALTER TABLE service_api_keys ALTER COLUMN type TYPE VARCHAR(20)");
        
        // Actualizar valores existentes
        DB::table('service_api_keys')
            ->where('type', 'live')
            ->update(['type' => 'production']);
            
        DB::table('service_api_keys')
            ->where('type', 'test')
            ->update(['type' => 'development']);
        
        // Agregar constraint check con los nuevos valores
        DB::statement("ALTER TABLE service_api_keys ADD CONSTRAINT service_api_keys_type_check CHECK (type IN ('production', 'development', 'testing'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar constraint
        DB::statement("ALTER TABLE service_api_keys DROP CONSTRAINT IF EXISTS service_api_keys_type_check");
        
        // Rever valores
        DB::table('service_api_keys')
            ->where('type', 'production')
            ->update(['type' => 'live']);
            
        DB::table('service_api_keys')
            ->whereIn('type', ['development', 'testing'])
            ->update(['type' => 'test']);
    }
};
