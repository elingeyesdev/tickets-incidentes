<?php

namespace App\Features\UserManagement\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Activated Event
 *
 * Disparado cuando un usuario suspendido es reactivado.
 * Útil para:
 * - Enviar notificación de reactivación
 * - Restaurar acceso a recursos
 * - Registrar en auditoría
 */
class UserActivated
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario activado
     * @param string|null $activatedById ID del admin que activó
     */
    public function __construct(
        public User $user,
        public ?string $activatedById = null
    ) {}
}