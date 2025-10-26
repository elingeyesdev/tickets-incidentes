<?php

namespace App\Features\Authentication\Listeners;

use App\Features\Authentication\Events\PasswordResetRequested;
use App\Features\Authentication\Jobs\SendPasswordResetEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

/**
 * Password Reset Listener
 *
 * Escucha el evento PasswordResetRequested y:
 * 1. Genera un código de 6 dígitos
 * 2. Guarda el código en cache
 * 3. Encola el job para enviar email
 */
class PasswordResetListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PasswordResetRequested $event): void
    {
        // Generar código de 6 dígitos
        $resetCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar código en cache con TTL de 24 horas
        \Illuminate\Support\Facades\Cache::put(
            "password_reset_code:{$event->user->id}",
            $resetCode,
            now()->addHours(24)
        );

        // Encolar job para enviar email
        SendPasswordResetEmailJob::dispatch(
            $event->user,
            $event->resetToken,
            $resetCode
        );
    }
}
