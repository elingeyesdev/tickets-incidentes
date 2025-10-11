<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para crear la tabla 'roles' en el schema 'auth'
 *
 * Tabla catálogo de roles del sistema (FIJOS).
 * Los 4 roles base son: platform_admin, company_admin, agent, user
 *
 * IMPORTANTE:
 * - Estructura SIMPLE (sin permisos en BD)
 * - Permisos se manejan en código con Laravel Policies
 * - role_code es la clave para consultas (VARCHAR)
 * - Solo created_at (no updated_at - roles no se modifican)
 *
 * Referencia: Modelado V7.0 líneas 117-135
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla de roles
        DB::statement("
            CREATE TABLE auth.roles (
                -- Identificadores
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                role_code VARCHAR(50) UNIQUE NOT NULL,
                role_name VARCHAR(100) NOT NULL,
                description TEXT,

                is_system BOOLEAN DEFAULT TRUE,

                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Comentarios
        DB::statement("
            COMMENT ON TABLE auth.roles IS
            'Catálogo de roles fijos del sistema'
        ");

        DB::statement("COMMENT ON COLUMN auth.roles.role_code IS 'Código del rol: platform_admin, company_admin, agent, user'");
        DB::statement("COMMENT ON COLUMN auth.roles.role_name IS 'Nombre legible del rol'");
        DB::statement("COMMENT ON COLUMN auth.roles.is_system IS 'Roles del sistema que no se pueden eliminar'");

        // Índices
        DB::statement('CREATE INDEX idx_roles_role_code ON auth.roles(role_code)');
        DB::statement('CREATE INDEX idx_roles_is_system ON auth.roles(is_system)');

        // Insertar roles fijos del sistema
        // IMPORTANTE: role_code en UPPERCASE_SNAKE_CASE para consistencia
        DB::statement("
            INSERT INTO auth.roles (role_code, role_name, description, is_system) VALUES
            ('PLATFORM_ADMIN', 'Administrador de Plataforma', 'Acceso total al sistema', true),
            ('COMPANY_ADMIN', 'Administrador de Empresa', 'Gestiona una empresa específica', true),
            ('AGENT', 'Agente de Soporte', 'Atiende tickets de soporte de una empresa', true),
            ('USER', 'Cliente', 'Usuario que crea tickets', true)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS auth.roles CASCADE');
    }
};