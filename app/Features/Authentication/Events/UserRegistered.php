<?php

namespace App\Features\Authentication\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Registered Event
 *
 * Disparado cuando un usuario se registra en el sistema.
 * Este evento inicia el proceso de verificación de email.
 *
 * Listeners:
 * - SendVerificationEmail: Envía email de verificación
 */
class UserRegistered
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario registrado
     * @param string $verificationToken Token de verificación de email
     */
    public function __construct(
        public User $user,
        public string $verificationToken
    ) {}
}