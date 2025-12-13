<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Features\UserManagement\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * SpatieUserRolesSyncSeeder
 * 
 * Sincroniza los roles existentes de auth.user_roles con Spatie.
 * 
 * Este seeder lee la tabla auth.user_roles (tu sistema actual) y
 * asigna los roles correspondientes en Spatie a cada usuario.
 * 
 * IMPORTANTE:
 * - Spatie NO almacena company_id - solo el rol base
 * - El company_id sigue viniendo del JWT (active_role.company_id)
 * - Un usuario con múltiples roles en diferentes empresas tendrá
 *   esos roles asignados UNA vez en Spatie (ej: solo 1x COMPANY_ADMIN)
 */
class SpatieUserRolesSyncSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Obtener todos los user_roles con sus códigos de rol
        // La tabla usa role_code (VARCHAR) como FK, NO role_id
        $userRoles = DB::table('auth.user_roles')
            ->select('user_id', 'role_code')
            ->where('is_active', true)
            ->distinct()
            ->get();

        $this->command->info("Found {$userRoles->count()} active user-role assignments to sync");

        // Agrupar por usuario para asignar todos los roles de una vez
        $userRolesGrouped = $userRoles->groupBy('user_id');

        $syncedUsers = 0;
        $errors = 0;

        foreach ($userRolesGrouped as $userId => $roles) {
            try {
                $user = User::find($userId);
                
                if (!$user) {
                    $this->command->warn("⚠️ User {$userId} not found, skipping...");
                    $errors++;
                    continue;
                }

                // Obtener códigos únicos de roles para este usuario
                $roleCodes = $roles->pluck('role_code')->unique()->toArray();

                // Usar el método de Spatie directamente en el modelo
                // Evitamos syncRoles() porque hay conflicto con assignRole() personalizado
                // Primero limpiamos roles de Spatie existentes
                DB::table('model_has_roles')
                    ->where('model_id', $userId)
                    ->where('model_type', User::class)
                    ->delete();

                // Luego asignamos cada rol de Spatie
                foreach ($roleCodes as $roleCode) {
                    $spatieRole = Role::findByName($roleCode, 'web');
                    if ($spatieRole) {
                        DB::table('model_has_roles')->insert([
                            'role_id' => $spatieRole->id,
                            'model_type' => User::class,
                            'model_id' => $userId,
                        ]);
                    }
                }

                $syncedUsers++;
                
                if ($this->command->getOutput()->isVerbose()) {
                    $this->command->info("  ✓ User {$user->email}: " . implode(', ', $roleCodes));
                }

            } catch (\Exception $e) {
                $this->command->error("❌ Error syncing user {$userId}: " . $e->getMessage());
                $errors++;
            }
        }

        // Clear Spatie permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->newLine();
        $this->command->info("═══════════════════════════════════════════════════════");
        $this->command->info("✅ Spatie roles sync completed!");
        $this->command->info("   Users synced: {$syncedUsers}");
        if ($errors > 0) {
            $this->command->warn("   Errors: {$errors}");
        }
        $this->command->info("═══════════════════════════════════════════════════════");
    }
}
