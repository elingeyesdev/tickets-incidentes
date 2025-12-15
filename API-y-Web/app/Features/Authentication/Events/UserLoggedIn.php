<?php

namespace App\Features\Authentication\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Logged In Event
 *
 * Disparado cuando un usuario inicia sesión exitosamente.
 *
 * Listeners:
 * - LogLoginActivity: Registra actividad de login en audit logs
 */
class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario que inició sesión
     * @param array $deviceInfo Información del dispositivo ['name' => string, 'ip' => string, 'user_agent' => string]
     */
    public function __construct(
        public User $user,
        public array $deviceInfo = []
    ) {}
}