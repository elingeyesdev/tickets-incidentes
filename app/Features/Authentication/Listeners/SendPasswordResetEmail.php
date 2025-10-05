<?php

namespace App\Features\Authentication\Listeners;

use App\Features\Authentication\Events\PasswordResetRequested;
use App\Features\Authentication\Jobs\SendPasswordResetEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Send Password Reset Email Listener
 *
 * Escucha el evento PasswordResetRequested y dispara el job
 * para enviar el email de reset de contraseña.
 */
class SendPasswordResetEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PasswordResetRequested $event): void
    {
        // Disparar job asíncrono para enviar email
        SendPasswordResetEmailJob::dispatch(
            $event->user,
            $event->resetToken
        );
    }
}