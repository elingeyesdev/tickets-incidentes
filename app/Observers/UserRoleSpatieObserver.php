<?php

namespace App\Observers;

use App\Features\UserManagement\Models\UserRole;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Observer para sincronizar auth.user_roles con Spatie Permission.
 * 
 * Cada vez que se crea, actualiza o elimina un UserRole en el sistema
 * Helpdesk, este observer actualiza automáticamente las tablas de Spatie.
 * 
 * Esto asegura que:
 * 1. Las directivas Blade @role/@hasrole funcionen correctamente
 * 2. El middleware spatie.role funcione en web.php
 * 3. Los roles estén siempre sincronizados entre ambos sistemas
 */
class UserRoleSpatieObserver
{
    /**
     * Handle the UserRole "created" event.
     */
    public function created(UserRole $userRole): void
    {
        $this->syncUserSpatieRoles($userRole->user_id);
    }

    /**
     * Handle the UserRole "updated" event.
     */
    public function updated(UserRole $userRole): void
    {
        $this->syncUserSpatieRoles($userRole->user_id);
    }

    /**
     * Handle the UserRole "deleted" event.
     */
    public function deleted(UserRole $userRole): void
    {
        $this->syncUserSpatieRoles($userRole->user_id);
    }

    /**
     * Sincroniza todos los roles de un usuario con Spatie.
     */
    private function syncUserSpatieRoles(string $userId): void
    {
        try {
            // Obtener roles activos del usuario en auth.user_roles
            $roleCodes = DB::table('auth.user_roles')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->pluck('role_code')
                ->unique()
                ->toArray();

            // Limpiar roles Spatie existentes
            DB::table('model_has_roles')
                ->where('model_uuid', $userId)
                ->where('model_type', User::class)
                ->delete();

            // Asignar roles en Spatie
            foreach ($roleCodes as $roleCode) {
                $spatieRole = SpatieRole::where('name', $roleCode)
                    ->where('guard_name', 'web')
                    ->first();
                    
                if ($spatieRole) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $spatieRole->id,
                        'model_type' => User::class,
                        'model_uuid' => $userId,
                    ]);
                }
            }

            // Limpiar cache de Spatie
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

        } catch (\Exception $e) {
            // Log error but don't fail the original operation
            \Log::warning('Failed to sync Spatie roles for user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
