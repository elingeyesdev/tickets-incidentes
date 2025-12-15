<?php

namespace App\Features\Authentication\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Password Reset Completed Event
 *
 * Disparado cuando un usuario completa exitosamente el reset de contraseña.
 * Después de este evento, todas las sesiones del usuario son invalidadas.
 */
class PasswordResetCompleted
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario que completó el reset
     */
    public function __construct(
        public User $user
    ) {}
}