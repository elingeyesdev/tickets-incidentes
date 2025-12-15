<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para crear la tabla 'user_profiles' en el schema 'auth'
 *
 * Tabla de perfiles de usuarios (relación 1:1 con users).
 * Contiene información personal y preferencias del usuario.
 *
 * IMPORTANTE:
 * - PK es user_id (NO hay campo 'id' separado)
 * - display_name NO se almacena, se calcula en queries o accesor
 *
 * Referencia: Modelado V7.0 líneas 76-100
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla usando DB::statement para control total
        DB::statement("
            CREATE TABLE auth.user_profiles (
                -- Relación 1:1 con users (PK + FK)
                user_id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,

                -- Información personal
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                -- display_name se calcula en queries, NO se almacena
                phone_number VARCHAR(20),
                avatar_url VARCHAR(500),

                -- Preferencias de usuario
                theme VARCHAR(20) DEFAULT 'light',
                language VARCHAR(10) DEFAULT 'es',
                timezone VARCHAR(50) DEFAULT 'UTC',

                -- Configuración de notificaciones
                push_web_notifications BOOLEAN DEFAULT TRUE,
                notifications_tickets BOOLEAN DEFAULT TRUE,

                -- Auditoría
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Comentarios
        DB::statement("
            COMMENT ON TABLE auth.user_profiles IS
            'Perfiles de usuarios - Información personal y preferencias (relación 1:1 con users)'
        ");

        DB::statement("COMMENT ON COLUMN auth.user_profiles.user_id IS 'FK a auth.users (también es PK)'");
        DB::statement("COMMENT ON COLUMN auth.user_profiles.theme IS 'Tema de interfaz: light, dark'");
        DB::statement("COMMENT ON COLUMN auth.user_profiles.language IS 'Idioma preferido: es, en'");

        // Índices para búsqueda por nombre
        DB::statement('CREATE INDEX idx_user_profiles_first_name ON auth.user_profiles(first_name)');
        DB::statement('CREATE INDEX idx_user_profiles_last_name ON auth.user_profiles(last_name)');
        DB::statement('CREATE INDEX idx_user_profiles_full_name ON auth.user_profiles(first_name, last_name)');

        // Índice full-text para búsqueda por nombre completo
        DB::statement("
            CREATE INDEX idx_user_profiles_name_search ON auth.user_profiles
            USING gin(to_tsvector('spanish', first_name || ' ' || last_name))
        ");

        // Trigger para updated_at
        DB::statement("
            CREATE TRIGGER trigger_update_user_profiles_updated_at
            BEFORE UPDATE ON auth.user_profiles
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS auth.user_profiles CASCADE');
    }
};
