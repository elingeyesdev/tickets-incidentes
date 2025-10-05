<?php

namespace App\Features\UserManagement\Events;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Role Assigned Event
 *
 * Disparado cuando se asigna un rol a un usuario.
 * Acciones relacionadas:
 * - Actualizar permisos en cache
 * - Enviar notificación al usuario
 * - Registrar en auditoría
 * - Sincronizar con sistemas externos
 */
class RoleAssigned
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario al que se asignó el rol
     * @param UserRole $userRole Rol asignado
     * @param string|null $assignedById ID del usuario que asignó el rol
     */
    public function __construct(
        public User $user,
        public UserRole $userRole,
        public ?string $assignedById = null
    ) {}
}