<?php

namespace App\Features\UserManagement\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Suspended Event
 *
 * Disparado cuando un usuario es suspendido.
 * Acciones relacionadas:
 * - Invalidar tokens de sesión
 * - Enviar notificación al usuario
 * - Bloquear acceso a recursos
 * - Registrar en auditoría
 */
class UserSuspended
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario suspendido
     * @param string|null $reason Razón de la suspensión
     * @param string|null $suspendedById ID del admin que suspendió
     */
    public function __construct(
        public User $user,
        public ?string $reason = null,
        public ?string $suspendedById = null
    ) {}
}