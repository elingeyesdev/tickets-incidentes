<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para crear la tabla 'users' en el schema 'auth'
 *
 * Tabla principal de usuarios del sistema.
 * Contiene información de autenticación y estado de la cuenta.
 *
 * Referencia: Modelado V7.0 líneas 42-74
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
            CREATE TABLE auth.users (
                -- Identificadores
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                user_code VARCHAR(20) UNIQUE NOT NULL,

                -- Información de autenticación (CRÍTICA)
                email CITEXT UNIQUE NOT NULL,
                email_verified BOOLEAN DEFAULT FALSE,
                email_verified_at TIMESTAMPTZ,
                password_hash VARCHAR(255),
                auth_provider VARCHAR(20) DEFAULT 'local',
                external_auth_id VARCHAR(255),

                -- Seguridad (CRÍTICA)
                password_reset_token VARCHAR(255),
                password_reset_expires TIMESTAMPTZ,

                -- Estado del sistema (CRÍTICO)
                status auth.user_status DEFAULT 'active' NOT NULL,
                last_login_at TIMESTAMPTZ,
                last_login_ip INET,

                -- Términos y condiciones
                terms_accepted BOOLEAN DEFAULT FALSE,
                terms_accepted_at TIMESTAMPTZ,
                terms_version VARCHAR(10),

                -- Auditoría (CRÍTICA)
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMPTZ
            )
        ");

        // Comentarios de tabla
        DB::statement("
            COMMENT ON TABLE auth.users IS
            'Usuarios del sistema - Información de autenticación y estado'
        ");

        DB::statement("COMMENT ON COLUMN auth.users.user_code IS 'Código único: USR-2025-00001'");
        DB::statement("COMMENT ON COLUMN auth.users.email IS 'Email único del usuario'");
        DB::statement("COMMENT ON COLUMN auth.users.password_hash IS 'Hash bcrypt de la contraseña (NULL si usa OAuth)'");
        DB::statement("COMMENT ON COLUMN auth.users.auth_provider IS 'Proveedor de auth: local, google, microsoft'");
        DB::statement("COMMENT ON COLUMN auth.users.external_auth_id IS 'Google ID, Microsoft ID, etc.'");
        DB::statement("COMMENT ON COLUMN auth.users.last_login_ip IS 'IP del último acceso (soporta IPv4 e IPv6)'");

        // Índices para performance
        DB::statement('CREATE INDEX idx_users_status ON auth.users(status)');
        DB::statement('CREATE INDEX idx_users_email_verified ON auth.users(email_verified)');
        DB::statement('CREATE INDEX idx_users_auth_provider ON auth.users(auth_provider)');
        DB::statement('CREATE INDEX idx_users_last_login ON auth.users(last_login_at)');
        DB::statement('CREATE INDEX idx_users_created_at ON auth.users(created_at)');
        DB::statement('CREATE INDEX idx_users_status_verified ON auth.users(status, email_verified)');

        // Índice full-text para búsqueda por email
        DB::statement("CREATE INDEX idx_users_email_search ON auth.users USING gin(to_tsvector('english', email))");

        // Trigger para updated_at
        DB::statement("
            CREATE TRIGGER trigger_update_users_updated_at
            BEFORE UPDATE ON auth.users
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS auth.users CASCADE');
    }
};