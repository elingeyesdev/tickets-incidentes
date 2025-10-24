<?php

namespace App\Features\UserManagement\Services;

use App\Features\UserManagement\Models\Role;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Exceptions\AuthorizationException;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * Role Service
 *
 * Servicio de gestión de roles y permisos.
 * Maneja asignación de roles a usuarios con contexto de empresa.
 */
class RoleService
{
    /**
     * Obtener rol por código
     *
     * @param string $roleCode
     * @return Role
     * @throws NotFoundException
     */
    public function getRoleByCode(string $roleCode): Role
    {
        $role = Role::findByCode($roleCode);

        if (!$role) {
            throw NotFoundException::resource('Rol', $roleCode);
        }

        return $role;
    }

    /**
     * Obtener rol por nombre
     *
     * @param string $roleName
     * @return Role
     * @throws NotFoundException
     */
    public function getRoleByName(string $roleName): Role
    {
        $role = Role::where('role_name', $roleName)->first();

        if (!$role) {
            throw NotFoundException::resource('Rol', $roleName);
        }

        return $role;
    }

    /**
     * Obtener todos los roles
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllRoles()
    {
        return Role::orderBy('role_code')->get();
    }

    /**
     * Obtener roles que requieren empresa
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompanyRoles()
    {
        return Role::requiresCompany()->orderBy('role_code')->get();
    }

    /**
     * Obtener roles globales (no requieren empresa)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGlobalRoles()
    {
        return Role::global()->orderBy('role_code')->get();
    }

    /**
     * Asignar rol a usuario (V10.1 - Lógica inteligente)
     * CREA nuevo rol O REACTIVA si existe inactivo
     *
     * @param string $userId
     * @param string $roleCode
     * @param string|null $companyId
     * @param string|null $assignedBy
     * @return array{success: bool, message: string, role: UserRole, wasReactivated: bool}
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function assignRoleToUser(
        string $userId,
        string $roleCode,
        ?string $companyId = null,
        ?string $assignedBy = null
    ): array {
        // Validar que el usuario existe
        $user = User::find($userId);
        if (!$user) {
            throw NotFoundException::resource('Usuario', $userId);
        }

        // Validar que el rol existe
        $role = $this->getRoleByCode($roleCode);

        // Validar que si el rol requiere empresa, se proporcione company_id
        if ($role->requiresCompany() && !$companyId) {
            throw ValidationException::withField(
                'company_id',
                "{$role->role_name} role requires company context"
            );
        }

        // Validar que si el rol NO requiere empresa, NO se proporcione company_id
        if (!$role->requiresCompany() && $companyId) {
            throw ValidationException::withField(
                'company_id',
                "{$role->role_name} role cannot have company context"
            );
        }

        // Verificar que el usuario no tenga ya ese rol en esa empresa
        $existingRole = UserRole::where('user_id', $userId)
            ->where('role_code', $roleCode)
            ->where('company_id', $companyId)
            ->first();

        if ($existingRole) {
            // Si existe pero está revocado, reactivarlo
            if ($existingRole->isRevoked()) {
                $existingRole->update([
                    'is_active' => true,
                    'revoked_at' => null,
                    'revocation_reason' => null,
                ]);

                return [
                    'success' => true,
                    'message' => "Rol {$roleCode} reactivado exitosamente",
                    'role' => $existingRole->fresh(),
                    'wasReactivated' => true,
                ];
            }

            throw ValidationException::withField(
                'role_code',
                'El usuario ya tiene este rol asignado'
            );
        }

        // Crear asignación de rol
        $newRole = UserRole::create([
            'user_id' => $userId,
            'role_code' => $roleCode,
            'company_id' => $companyId,
            'is_active' => true,
            'assigned_by' => $assignedBy,
        ]);

        return [
            'success' => true,
            'message' => "Rol {$roleCode} asignado exitosamente",
            'role' => $newRole,
            'wasReactivated' => false,
        ];
    }

    /**
     * Revocar rol de usuario
     *
     * @param string $userId
     * @param string $roleCode
     * @param string|null $companyId
     * @param string|null $revokedBy
     * @return bool
     * @throws NotFoundException
     */
    public function revokeRoleFromUser(
        string $userId,
        string $roleCode,
        ?string $companyId = null,
        ?string $revokedBy = null
    ): bool {
        $userRole = UserRole::where('user_id', $userId)
            ->where('role_code', $roleCode)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$userRole) {
            throw NotFoundException::resource('Asignación de rol', "{$userId}-{$roleCode}");
        }

        $userRole->revoke();

        return true;
    }

    /**
     * Remover rol por ID (V10.1 - nuevo método)
     * Soft delete reversible - puede reactivarse con assignRole
     *
     * @param string $roleId
     * @param string|null $reason
     * @return bool
     * @throws NotFoundException
     */
    public function removeRoleById(string $roleId, ?string $reason = null): bool
    {
        $userRole = UserRole::find($roleId);

        if (!$userRole) {
            throw NotFoundException::resource('Asignación de rol', $roleId);
        }

        // Soft delete con razón
        $userRole->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Obtener todos los roles de un usuario
     *
     * @param string $userId
     * @param bool $activeOnly
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserRoles(string $userId, bool $activeOnly = true)
    {
        $query = UserRole::where('user_id', $userId)
            ->with(['role']);

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Obtener roles de un usuario en una empresa específica
     *
     * @param string $userId
     * @param string $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserRolesInCompany(string $userId, string $companyId)
    {
        return UserRole::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->active()
            ->with(['role'])
            ->get();
    }

    /**
     * Obtener roles globales de un usuario (sin contexto de empresa)
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserGlobalRoles(string $userId)
    {
        return UserRole::where('user_id', $userId)
            ->whereNull('company_id')
            ->active()
            ->with(['role'])
            ->get();
    }

    /**
     * Verificar si un usuario tiene un rol específico
     *
     * @param string $userId
     * @param string $roleName
     * @param string|null $companyId
     * @return bool
     */
    public function userHasRole(string $userId, string $roleCode, ?string $companyId = null): bool
    {
        $query = UserRole::where('user_id', $userId)
            ->where('role_code', $roleCode)
            ->active();

        if ($companyId) {
            $query->forCompany($companyId);
        }

        return $query->exists();
    }

    /**
     * Verificar si un usuario tiene un permiso específico
     *
     * @param string $userId
     * @param string $permission
     * @param string|null $companyId
     * @return bool
     */
    public function userHasPermission(string $userId, string $permission, ?string $companyId = null): bool
    {
        $query = UserRole::where('user_id', $userId)
            ->active()
            ->with('role');

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->orWhereNull('company_id'); // Roles globales también aplican
            });
        }

        $userRoles = $query->get();

        foreach ($userRoles as $userRole) {
            if ($userRole->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener todos los permisos de un usuario
     *
     * @param string $userId
     * @param string|null $companyId
     * @return array
     */
    public function getUserPermissions(string $userId, ?string $companyId = null): array
    {
        $query = UserRole::where('user_id', $userId)
            ->active()
            ->with('role');

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->orWhereNull('company_id');
            });
        }

        $userRoles = $query->get();

        $permissions = [];
        foreach ($userRoles as $userRole) {
            $permissions = array_merge($permissions, $userRole->getPermissions());
        }

        return array_unique($permissions);
    }

    /**
     * Cambiar roles de un usuario en una empresa
     * (Revoca todos los roles anteriores y asigna los nuevos)
     *
     * @param string $userId
     * @param array $roleCodes
     * @param string|null $companyId
     * @param string|null $changedBy
     * @return array
     * @throws ValidationException
     */
    public function syncUserRoles(
        string $userId,
        array $roleCodes,
        ?string $companyId = null,
        ?string $changedBy = null
    ): array {
        return DB::transaction(function () use ($userId, $roleCodes, $companyId, $changedBy) {
            // Obtener roles actuales
            $currentRoles = UserRole::where('user_id', $userId)
                ->where('company_id', $companyId)
                ->active()
                ->get();

            $currentRoleCodes = $currentRoles->pluck('role_code')->toArray();

            // Revocar roles que ya no están en la nueva lista
            foreach ($currentRoles as $currentRole) {
                if (!in_array($currentRole->role_code, $roleCodes)) {
                    $currentRole->revoke();
                }
            }

            // Asignar nuevos roles
            $newRoles = [];
            foreach ($roleCodes as $roleCode) {
                if (!in_array($roleCode, $currentRoleCodes)) {
                    $newRoles[] = $this->assignRoleToUser($userId, $roleCode, $companyId, $changedBy);
                }
            }

            return [
                'revoked' => count($currentRoleCodes) - count(array_intersect($currentRoleCodes, $roleCodes)),
                'assigned' => count($newRoles),
            ];
        });
    }

    /**
     * Obtener usuarios con un rol específico
     *
     * @param string $roleCode
     * @param string|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByRole(string $roleCode, ?string $companyId = null)
    {
        $query = UserRole::where('role_code', $roleCode)
            ->active()
            ->with('user.profile');

        if ($companyId) {
            $query->forCompany($companyId);
        }

        return $query->get()->pluck('user');
    }
}