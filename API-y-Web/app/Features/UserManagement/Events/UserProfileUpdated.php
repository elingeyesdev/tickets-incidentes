<?php

namespace App\Features\UserManagement\Events;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Profile Updated Event
 *
 * Disparado cuando un usuario actualiza su perfil personal.
 * Mencionado en documentación (línea 683).
 *
 * Útil para:
 * - Sincronización con otros features
 * - Actualizar cache de información de usuario
 * - Notificar cambios importantes (email, nombre)
 */
class UserProfileUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario dueño del perfil
     * @param UserProfile $profile Perfil actualizado
     * @param array $changes Campos que cambiaron
     */
    public function __construct(
        public User $user,
        public UserProfile $profile,
        public array $changes = []
    ) {}
}