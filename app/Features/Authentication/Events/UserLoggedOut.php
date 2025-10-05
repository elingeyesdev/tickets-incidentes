<?php

namespace App\Features\Authentication\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Logged Out Event
 *
 * Disparado cuando un usuario cierra sesión.
 */
class UserLoggedOut
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario que cerró sesión
     * @param array $context Contexto adicional ['all_devices' => bool, 'session_id' => string]
     */
    public function __construct(
        public User $user,
        public array $context = []
    ) {}
}