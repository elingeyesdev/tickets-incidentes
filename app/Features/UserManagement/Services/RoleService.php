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
     * Obtener rol por ID
     *
     * @param string $roleId
     * @return Role
     * @throws NotFoundException
     */
    public function getRoleById(string $roleId): Role
    {
        $role = Role::find($roleId);

        if (!$role) {
            throw NotFoundException::resource('Rol', $roleId);
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
        $role = Role::where('name', $roleName)->first();

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
        return Role::byPriority()->get();
    }

    /**
     * Obtener roles que requieren empresa
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompanyRoles()
    {
        return Role::requiresCompany()->byPriority()->get();
    }

    /**
     * Obtener roles globales (no requieren empresa)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGlobalRoles()
    {
        return Role::global()->byPriority()->get();
    }

    /**
     * Asignar rol a usuario
     *
     * @param string $userId
     * @param string $roleId
     * @param string|null $companyId
     * @param string|null $assignedById
     * @return UserRole
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function assignRoleToUser(
        string $userId,
        string $roleId,
        ?string $companyId = null,
        ?string $assignedById = null
    ): UserRole {
        // Validar que el usuario existe
        $user = User::find($userId);
        if (!$user) {
            throw NotFoundException::resource('Usuario', $userId);
        }

        // Validar que el rol existe
        $role = $this->getRoleById($roleId);

        // Validar que si el rol requiere empresa, se proporcione company_id
        if ($role->requiresCompany() && !$companyId) {
            throw ValidationException::withField(
                'company_id',
                "El rol {$role->name} requiere contexto de empresa"
            );
        }

        // Validar que si el rol NO requiere empresa, NO se proporcione company_id
        if (!$role->requiresCompany() && $companyId) {
            throw ValidationException::withField(
                'company_id',
                "El rol {$role->name} no puede tener contexto de empresa"
            );
        }

        // Verificar que el usuario no tenga ya ese rol en esa empresa
        $existingRole = UserRole::where('user_id', $userId)
            ->where('role_id', $roleId)
            ->where('company_id', $companyId)
            ->first();

        if ($existingRole) {
            // Si existe pero está revocado, reactivarlo
            if ($existingRole->isRevoked()) {
                $existingRole->activate();
                return $existingRole;
            }

            throw ValidationException::withField(
                'role_id',
                'El usuario ya tiene este rol asignado'
            );
        }

        // Crear asignación de rol
        return UserRole::create([
            'user_id' => $userId,
            'role_id' => $roleId,
            'company_id' => $companyId,
            'is_active' => true,
            'assigned_by_id' => $assignedById,
        ]);
    }

    /**
     * Revocar rol de usuario
     *
     * @param string $userId
     * @param string $roleId
     * @param string|null $companyId
     * @param string|null $revokedById
     * @return bool
     * @throws NotFoundException
     */
    public function revokeRoleFromUser(
        string $userId,
        string $roleId,
        ?string $companyId = null,
        ?string $revokedById = null
    ): bool {
        $userRole = UserRole::where('user_id', $userId)
            ->where('role_id', $roleId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$userRole) {
            throw NotFoundException::resource('Asignación de rol', "{$userId}-{$roleId}");
        }

        $userRole->revoke($revokedById);

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
    public function userHasRole(string $userId, string $roleName, ?string $companyId = null): bool
    {
        $query = UserRole::where('user_id', $userId)
            ->active()
            ->byRoleName($roleName);

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
     * @param array $roleIds
     * @param string|null $companyId
     * @param string|null $changedById
     * @return array
     * @throws ValidationException
     */
    public function syncUserRoles(
        string $userId,
        array $roleIds,
        ?string $companyId = null,
        ?string $changedById = null
    ): array {
        return DB::transaction(function () use ($userId, $roleIds, $companyId, $changedById) {
            // Obtener roles actuales
            $currentRoles = UserRole::where('user_id', $userId)
                ->where('company_id', $companyId)
                ->active()
                ->get();

            $currentRoleIds = $currentRoles->pluck('role_id')->toArray();

            // Revocar roles que ya no están en la nueva lista
            foreach ($currentRoles as $currentRole) {
                if (!in_array($currentRole->role_id, $roleIds)) {
                    $currentRole->revoke($changedById);
                }
            }

            // Asignar nuevos roles
            $newRoles = [];
            foreach ($roleIds as $roleId) {
                if (!in_array($roleId, $currentRoleIds)) {
                    $newRoles[] = $this->assignRoleToUser($userId, $roleId, $companyId, $changedById);
                }
            }

            return [
                'revoked' => count($currentRoleIds) - count(array_intersect($currentRoleIds, $roleIds)),
                'assigned' => count($newRoles),
            ];
        });
    }

    /**
     * Obtener usuarios con un rol específico
     *
     * @param string $roleId
     * @param string|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByRole(string $roleId, ?string $companyId = null)
    {
        $query = UserRole::where('role_id', $roleId)
            ->active()
            ->with('user.profile');

        if ($companyId) {
            $query->forCompany($companyId);
        }

        return $query->get()->pluck('user');
    }
}