<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para crear la tabla 'refresh_tokens' en el schema 'auth'
 *
 * Tabla para almacenar refresh tokens JWT.
 * Los refresh tokens permiten renovar access tokens sin requerir login.
 *
 * Seguridad:
 * - Tokens almacenados como hash SHA-256 (nunca plain text)
 * - Expiración automática (30 días default)
 * - Revocación manual posible con razón
 * - Asociados a dispositivo específico
 *
 * Referencia: Modelado V7.0 líneas 159-183
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla refresh_tokens
        DB::statement("
            CREATE TABLE auth.refresh_tokens (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,

                -- Token seguro (hasheado, nunca en texto plano)
                token_hash VARCHAR(255) UNIQUE NOT NULL,

                -- Información del dispositivo
                device_name VARCHAR(100),
                ip_address INET NOT NULL,
                user_agent TEXT,

                -- Temporalidad
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMPTZ NOT NULL,
                last_used_at TIMESTAMPTZ,

                -- Estado
                is_revoked BOOLEAN DEFAULT FALSE,
                revoked_at TIMESTAMPTZ,
                revoke_reason VARCHAR(100),

                CONSTRAINT chk_token_expiry CHECK (expires_at > created_at)
            )
        ");

        // Comentarios
        DB::statement("
            COMMENT ON TABLE auth.refresh_tokens IS
            'Refresh tokens JWT para renovación de access tokens'
        ");

        DB::statement("COMMENT ON COLUMN auth.refresh_tokens.token_hash IS 'Hash SHA-256 del refresh token (nunca plain text)'");
        DB::statement("COMMENT ON COLUMN auth.refresh_tokens.device_name IS 'Ej: Chrome on Windows, iPhone Safari'");
        DB::statement("COMMENT ON COLUMN auth.refresh_tokens.ip_address IS 'IP del dispositivo (soporta IPv4 e IPv6)'");
        DB::statement("COMMENT ON COLUMN auth.refresh_tokens.revoke_reason IS 'Razón de revocación: manual_logout, security_breach, expired'");

        // Índices para performance
        DB::statement('CREATE INDEX idx_refresh_tokens_user_id ON auth.refresh_tokens(user_id)');
        DB::statement('CREATE INDEX idx_refresh_tokens_token_hash ON auth.refresh_tokens(token_hash)');
        DB::statement('CREATE INDEX idx_refresh_tokens_expires_at ON auth.refresh_tokens(expires_at)');
        DB::statement('CREATE INDEX idx_refresh_tokens_is_revoked ON auth.refresh_tokens(is_revoked)');
        DB::statement('CREATE INDEX idx_refresh_tokens_user_active ON auth.refresh_tokens(user_id, is_revoked)');
        DB::statement('CREATE INDEX idx_refresh_tokens_created_at ON auth.refresh_tokens(created_at)');

        // Trigger para updated_at (aunque no está en Modelado, Laravel lo espera)
        DB::statement('ALTER TABLE auth.refresh_tokens ADD COLUMN updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP');
        DB::statement("
            CREATE TRIGGER trigger_update_refresh_tokens_updated_at
            BEFORE UPDATE ON auth.refresh_tokens
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS auth.refresh_tokens CASCADE');
    }
};