<?php

namespace App\Features\Authentication\Listeners;

use App\Features\Authentication\Events\UserRegistered;
use App\Features\Authentication\Jobs\SendEmailVerificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Send Verification Email Listener
 *
 * Escucha el evento UserRegistered y dispara el job
 * para enviar el email de verificación.
 */
class SendVerificationEmail implements ShouldQueue
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