<?php

namespace App\Features\UserManagement\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Deleted Event
 *
 * Disparado cuando un usuario es eliminado (soft delete).
 * Acciones relacionadas:
 * - Anonimizar datos personales (GDPR compliance)
 * - Invalidar todos los tokens
 * - Archivar registros relacionados
 * - Notificar a administradores
 */
class UserDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario eliminado
     * @param string|null $reason Razón de la eliminación
     * @param string|null $deletedById ID del admin que eliminó
     */
    public function __construct(
        public User $user,
        public ?string $reason = null,
        public ?string $deletedById = null
    ) {}
}