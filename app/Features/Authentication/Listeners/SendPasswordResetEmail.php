<?php

namespace App\Features\Authentication\Listeners;

use App\\Features\\Authentication\\Events\\PasswordResetRequested;
use App\\Features\\Authentication\\Jobs\\SendPasswordResetEmailJob;
use Illuminate\\Support\\Facades\\Cache;

/**
 * Send Password Reset Email Listener
 *
 * Escucha el evento PasswordResetRequested y:
 * 1. Genera un código de 6 dígitos
 * 2. Guarda el código en cache
 * 3. Dispara el job para enviar email
 *
 * NOTA: Se ejecuta sincrónicamente (rápido, solo genera código y dispara job)
 * El job en sí es quien se encola y envía el email
 */
class SendPasswordResetEmail
{
    /**
     * Handle the event.
     */
    public function handle(PasswordResetRequested $event): void
    {
        // Generar código de 6 dígitos
        $resetCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar código en cache con TTL de 24 horas
        Cache::put(
            "password_reset_code:{$event->user->id}",
            $resetCode,
            now()->addHours(24)
        );

        // Disparar job asíncrono para enviar email con token y código
        SendPasswordResetEmailJob::dispatch(
            $event->user,
            $event->resetToken,
            $resetCode
        );
    }
}
