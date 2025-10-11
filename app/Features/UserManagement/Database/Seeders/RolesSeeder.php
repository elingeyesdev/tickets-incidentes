<?php

namespace App\Features\UserManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Roles Seeder
 *
 * Crea los 4 roles del sistema.
 * NOTA: Estos roles ya están insertados en la migración create_roles_table.
 * Este seeder está para:
 * - Re-insertar roles en ambientes de testing
 * - Actualizar permisos de roles existentes
 * - Documentación de roles del sistema
 */
class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = $this->getRolesData();

        foreach ($roles as $role) {
            DB::table('auth.roles')->updateOrInsert(
                ['role_code' => $role['role_code']], // Condición de búsqueda
                $role // Datos a insertar/actualizar
            );
        }

        $this->command->info('✅ Roles del sistema creados/actualizados exitosamente');
    }

    /**
     * Obtener datos de roles del sistema
     *
     * IMPORTANTE: role_code en UPPERCASE_SNAKE_CASE para consistencia
     *
     * @return array
     */
    private function getRolesData(): array
    {
        return [
            [
                'role_code' => 'USER',
                'role_name' => 'Cliente',
                'description' => 'Usuario final que puede crear tickets y ver su historial',
                'is_system' => true,
            ],
            [
                'role_code' => 'AGENT',
                'role_name' => 'Agente de Soporte',
                'description' => 'Agente de soporte que responde tickets de una empresa',
                'is_system' => true,
            ],
            [
                'role_code' => 'COMPANY_ADMIN',
                'role_name' => 'Administrador de Empresa',
                'description' => 'Administrador de una empresa, gestiona agentes y configuración',
                'is_system' => true,
            ],
            [
                'role_code' => 'PLATFORM_ADMIN',
                'role_name' => 'Administrador de Plataforma',
                'description' => 'Administrador global con acceso total al sistema',
                'is_system' => true,
            ],
        ];
    }
}