<?php

namespace App\Features\UserManagement\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Updated Event
 *
 * Disparado cuando se actualiza información de un usuario.
 * Útil para:
 * - Auditoría de cambios
 * - Sincronización de datos
 * - Notificaciones de cambios importantes
 */
class UserUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario actualizado
     * @param array $changes Campos que cambiaron
     * @param string|null $updatedById ID del usuario que hizo el cambio
     */
    public function __construct(
        public User $user,
        public array $changes = [],
        public ?string $updatedById = null
    ) {}
}
