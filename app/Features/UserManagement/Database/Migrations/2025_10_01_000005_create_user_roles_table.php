<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para crear la tabla 'user_roles' en el schema 'auth'
 *
 * Tabla pivot entre users y roles con contexto de empresa.
 * Permite que un usuario tenga diferentes roles en diferentes empresas.
 *
 * IMPORTANTE:
 * - FK a role_code VARCHAR (NO role_id UUID)
 * - CHECK constraint: company_admin y agent REQUIEREN company_id
 * - platform_admin y user NO requieren company_id
 *
 * Referencia: Modelado V7.0 líneas 137-157
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla user_roles
        DB::statement("
            CREATE TABLE auth.user_roles (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
                role_code VARCHAR(50) NOT NULL REFERENCES auth.roles(role_code),

                -- Contexto de empresa (solo para company_admin y agent)
                company_id UUID,

                is_active BOOLEAN DEFAULT TRUE,

                assigned_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                assigned_by UUID REFERENCES auth.users(id),
                revoked_at TIMESTAMPTZ,

                CONSTRAINT uq_user_role_context UNIQUE (user_id, role_code, company_id),
                CONSTRAINT chk_company_context CHECK (
                    (role_code IN ('company_admin', 'agent') AND company_id IS NOT NULL) OR
                    (role_code NOT IN ('company_admin', 'agent'))
                )
            )
        ");

        // Comentarios
        DB::statement("
            COMMENT ON TABLE auth.user_roles IS
            'Asignación de roles a usuarios con contexto de empresa'
        ");

        DB::statement("COMMENT ON COLUMN auth.user_roles.role_code IS 'FK a auth.roles.role_code (VARCHAR)'");
        DB::statement("COMMENT ON COLUMN auth.user_roles.company_id IS 'Contexto de empresa (NULL para platform_admin y user)'");
        DB::statement("COMMENT ON COLUMN auth.user_roles.assigned_by IS 'Usuario que asignó este rol'");

        // Índices para performance
        DB::statement('CREATE INDEX idx_user_roles_user_id ON auth.user_roles(user_id)');
        DB::statement('CREATE INDEX idx_user_roles_role_code ON auth.user_roles(role_code)');
        DB::statement('CREATE INDEX idx_user_roles_company_id ON auth.user_roles(company_id)');
        DB::statement('CREATE INDEX idx_user_roles_is_active ON auth.user_roles(is_active)');
        DB::statement('CREATE INDEX idx_user_roles_user_active ON auth.user_roles(user_id, is_active)');
        DB::statement('CREATE INDEX idx_user_roles_composite ON auth.user_roles(user_id, role_code, company_id)');

        // Trigger para updated_at (aunque no hay updated_at en Modelado, es útil)
        // NOTA: Si prefieres 100% fidelidad al Modelado, comenta estas líneas
        DB::statement('ALTER TABLE auth.user_roles ADD COLUMN updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP');
        DB::statement("
            CREATE TRIGGER trigger_update_user_roles_updated_at
            BEFORE UPDATE ON auth.user_roles
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS auth.user_roles CASCADE');
    }
};