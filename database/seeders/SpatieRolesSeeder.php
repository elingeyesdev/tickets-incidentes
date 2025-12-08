<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * SpatieRolesSeeder
 * 
 * Crea los roles en el sistema Spatie Permission.
 * Estos roles reflejan los mismos roles definidos en auth.roles.
 * 
 * NOTA: Spatie NO maneja company_id. El company_id se obtiene del JWT
 * mediante JWTHelper::getActiveCompanyId() o JWTHelper::getCompanyIdFromJWT().
 * 
 * Spatie solo verifica: "¿El usuario tiene este rol?" (sí/no)
 * JWT maneja: "¿En qué empresa está actuando este usuario?"
 */
class SpatieRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear los 4 roles del sistema (sin company_id - eso lo maneja JWT)
        $roles = [
            [
                'name' => 'PLATFORM_ADMIN',
                'guard_name' => 'web',
            ],
            [
                'name' => 'COMPANY_ADMIN', 
                'guard_name' => 'web',
            ],
            [
                'name' => 'AGENT',
                'guard_name' => 'web',
            ],
            [
                'name' => 'USER',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                $roleData
            );
        }

        $this->command->info('✅ Spatie roles created: PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER');

        // Opcional: Crear permisos básicos si los necesitas en el futuro
        // Por ahora solo usamos roles (sin permisos granulares)
        $permissions = [
            // Platform Admin permissions
            'manage-platform',
            'manage-companies',
            'view-all-companies',
            
            // Company Admin permissions
            'manage-company',
            'manage-agents',
            'manage-users',
            'manage-categories',
            'manage-areas',
            'manage-slas',
            'manage-announcements',
            'view-reports',
            
            // Agent permissions
            'manage-tickets',
            'view-tickets',
            'respond-tickets',
            
            // User permissions
            'create-tickets',
            'view-own-tickets',
            'view-announcements',
            'view-help-center',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $this->command->info('✅ Spatie permissions created');

        // Asignar permisos a roles
        $platformAdmin = Role::findByName('PLATFORM_ADMIN', 'web');
        $platformAdmin->givePermissionTo([
            'manage-platform',
            'manage-companies', 
            'view-all-companies',
        ]);

        $companyAdmin = Role::findByName('COMPANY_ADMIN', 'web');
        $companyAdmin->givePermissionTo([
            'manage-company',
            'manage-agents',
            'manage-users',
            'manage-categories',
            'manage-areas',
            'manage-slas',
            'manage-announcements',
            'view-reports',
            'manage-tickets',
            'view-tickets',
            'respond-tickets',
        ]);

        $agent = Role::findByName('AGENT', 'web');
        $agent->givePermissionTo([
            'manage-tickets',
            'view-tickets',
            'respond-tickets',
        ]);

        $user = Role::findByName('USER', 'web');
        $user->givePermissionTo([
            'create-tickets',
            'view-own-tickets',
            'view-announcements',
            'view-help-center',
        ]);

        $this->command->info('✅ Permissions assigned to roles');
    }
}
