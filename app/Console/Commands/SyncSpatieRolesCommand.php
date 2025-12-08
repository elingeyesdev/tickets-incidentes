<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Features\UserManagement\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sincroniza roles de auth.user_roles con Spatie Permission.
 * 
 * Uso:
 *   php artisan spatie:sync-roles           # Sincronizar todos los usuarios
 *   php artisan spatie:sync-roles --user=UUID  # Sincronizar un usuario específico
 */
class SyncSpatieRolesCommand extends Command
{
    protected $signature = 'spatie:sync-roles {--user= : UUID del usuario específico a sincronizar}';
    protected $description = 'Sincroniza roles de auth.user_roles con tablas de Spatie Permission';

    public function handle(): int
    {
        $userId = $this->option('user');

        // Reset cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        if ($userId) {
            return $this->syncSingleUser($userId);
        }

        return $this->syncAllUsers();
    }

    private function syncSingleUser(string $userId): int
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuario no encontrado: {$userId}");
            return 1;
        }

        $this->syncUserRoles($user);
        $this->info("✅ Roles sincronizados para: {$user->email}");
        
        return 0;
    }

    private function syncAllUsers(): int
    {
        $userRoles = DB::table('auth.user_roles')
            ->select('user_id', 'role_code')
            ->where('is_active', true)
            ->distinct()
            ->get()
            ->groupBy('user_id');

        $this->info("Sincronizando {$userRoles->count()} usuarios...");

        $bar = $this->output->createProgressBar($userRoles->count());

        foreach ($userRoles as $userId => $roles) {
            $user = User::find($userId);
            if ($user) {
                $roleCodes = $roles->pluck('role_code')->unique()->toArray();
                $this->assignSpatieRoles($user, $roleCodes);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Sincronización completada");

        // Clear cache again
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return 0;
    }

    private function syncUserRoles(User $user): void
    {
        $roleCodes = DB::table('auth.user_roles')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('role_code')
            ->unique()
            ->toArray();

        $this->assignSpatieRoles($user, $roleCodes);
    }

    private function assignSpatieRoles(User $user, array $roleCodes): void
    {
        // Limpiar roles existentes de Spatie
        DB::table('model_has_roles')
            ->where('model_uuid', $user->id)
            ->where('model_type', User::class)
            ->delete();

        // Asignar nuevos roles
        foreach ($roleCodes as $roleCode) {
            $spatieRole = Role::where('name', $roleCode)->where('guard_name', 'web')->first();
            if ($spatieRole) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $spatieRole->id,
                    'model_type' => User::class,
                    'model_uuid' => $user->id,
                ]);
            }
        }
    }
}
