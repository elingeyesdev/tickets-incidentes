<?php

namespace App\Features\Authentication\Listeners;

use App\Features\Authentication\Events\PasswordResetRequested;
use App\Features\Authentication\Jobs\SendPasswordResetEmailJob;
use Illuminate\Support\Facades\Cache;

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
        \Log::debug('SendPasswordResetEmail listener: Handling PasswordResetRequested event', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);

        // Generar código de 6 dígitos
        $resetCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        \Log::debug('SendPasswordResetEmail listener: Generated code', ['code' => $resetCode]);

        // Guardar código en cache con TTL de 24 horas
        // Usamos 2 keys: user_id -> code Y code -> user_id (reverse mapping para búsqueda rápida)
        Cache::put(
            "password_reset_code:{$event->user->id}",
            $resetCode,
            now()->addHours(24)
        );

        // Mapeo inverso: code -> user_id (para búsqueda por código)
        Cache::put(
            "password_reset_code_lookup:{$resetCode}",
            $event->user->id,
            now()->addHours(24)
        );

        // Disparar job asincrónico para enviar email con token y código
        SendPasswordResetEmailJob::dispatch(
            $event->user,
            $event->resetToken,
            $resetCode
        );

        \Log::debug('SendPasswordResetEmail listener: Job dispatched');
    }
}
