<?php

namespace App\Features\Authentication\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Password Reset Requested Event
 *
 * Disparado cuando un usuario solicita reset de contraseña.
 *
 * Listeners:
 * - SendPasswordResetEmail: Envía email con token de reset
 */
class PasswordResetRequested
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario que solicitó el reset
     * @param string $resetToken Token de reset de contraseña
     */
    public function __construct(
        public User $user,
        public string $resetToken
    ) {}
}