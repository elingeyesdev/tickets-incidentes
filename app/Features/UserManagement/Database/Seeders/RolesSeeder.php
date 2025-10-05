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
                ['name' => $role['name']], // Condición de búsqueda
                $role // Datos a insertar/actualizar
            );
        }

        $this->command->info('✅ Roles del sistema creados/actualizados exitosamente');
    }

    /**
     * Obtener datos de roles del sistema
     *
     * @return array
     */
    private function getRolesData(): array
    {
        return [
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'USER',
                'display_name' => 'Usuario',
                'description' => 'Usuario final que puede crear tickets y ver su historial',
                'permissions' => json_encode([
                    'tickets.create',
                    'tickets.view_own',
                    'profile.edit',
                ]),
                'requires_company' => false,
                'default_dashboard' => '/dashboard/user',
                'priority' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'AGENT',
                'display_name' => 'Agente',
                'description' => 'Agente de soporte que responde tickets de una empresa',
                'permissions' => json_encode([
                    'tickets.*',
                    'users.view',
                    'macros.use',
                    'company.view',
                ]),
                'requires_company' => true,
                'default_dashboard' => '/dashboard/agent',
                'priority' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'COMPANY_ADMIN',
                'display_name' => 'Administrador de Empresa',
                'description' => 'Administrador de una empresa, gestiona agentes y configuración',
                'permissions' => json_encode([
                    'company.*',
                    'users.manage',
                    'agents.manage',
                    'tickets.*',
                    'macros.*',
                    'categories.*',
                ]),
                'requires_company' => true,
                'default_dashboard' => '/dashboard/company-admin',
                'priority' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'PLATFORM_ADMIN',
                'display_name' => 'Administrador de Plataforma',
                'description' => 'Administrador global con acceso total al sistema',
                'permissions' => json_encode(['*']),
                'requires_company' => false,
                'default_dashboard' => '/dashboard/admin',
                'priority' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
    }
}