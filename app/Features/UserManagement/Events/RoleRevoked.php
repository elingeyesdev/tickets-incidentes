<?php

namespace App\Features\UserManagement\Events;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Role Revoked Event
 *
 * Disparado cuando se revoca un rol de un usuario.
 * Acciones relacionadas:
 * - Actualizar permisos en cache
 * - Invalidar tokens si es necesario
 * - Enviar notificación al usuario
 * - Registrar en auditoría
 * - Bloquear acceso a recursos del rol
 */
class RoleRevoked
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario al que se revocó el rol
     * @param UserRole $userRole Rol revocado
     * @param string|null $reason Razón de la revocación
     * @param string|null $revokedById ID del usuario que revocó el rol
     */
    public function __construct(
        public User $user,
        public UserRole $userRole,
        public ?string $reason = null,
        public ?string $revokedById = null
    ) {}
}