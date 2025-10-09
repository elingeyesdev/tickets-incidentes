<?php

namespace App\Features\Authentication\Listeners;

use App\Features\Authentication\Events\UserRegistered;
use App\Features\Authentication\Jobs\SendEmailVerificationJob;

/**
 * Send Verification Email Listener
 *
 * Escucha el evento UserRegistered y dispara el job
 * para enviar el email de verificación.
 *
 * NOTA: Este listener NO implementa ShouldQueue porque el Event
 * contiene el modelo User completo. Solo el Job debe estar queued.
 */
class SendVerificationEmail
{
    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        // Disparar job asíncrono para enviar email
        SendEmailVerificationJob::dispatch(
            $event->user,
            $event->verificationToken
        );
    }
}