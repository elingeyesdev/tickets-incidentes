<?php

namespace App\Features\Authentication\Listeners;

use App\Features\Authentication\Events\UserRegistered;
use App\Features\Authentication\Jobs\SendEmailVerificationJob;
use Illuminate\Support\Facades\Cache;

/**
 * Send Verification Email Listener
 *
 * Escucha el evento UserRegistered y:
 * 1. Genera un código de 6 dígitos
 * 2. Guarda el código en cache con mapeo inverso
 * 3. Dispara el job para enviar email con token Y código
 *
 * NOTA: Se ejecuta sincrónicamente (rápido, solo genera código y dispara job)
 * El job en sí es quien se encola y envía el email
 */
class SendVerificationEmail
{
    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        \Log::debug('SendVerificationEmail listener: Handling UserRegistered event', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);

        // Generar código de 6 dígitos
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        \Log::debug('SendVerificationEmail listener: Generated code', ['code' => $verificationCode]);

        // Guardar código en cache con TTL de 24 horas
        // Usamos 2 keys: user_id -> code Y code -> user_id (reverse mapping para búsqueda rápida)
        Cache::put(
            "email_verification_code:{$event->user->id}",
            $verificationCode,
            now()->addHours(24)
        );

        // Mapeo inverso: code -> user_id (para búsqueda por código)
        Cache::put(
            "email_verification_code_lookup:{$verificationCode}",
            $event->user->id,
            now()->addHours(24)
        );

        // Disparar job asíncrono para enviar email con token y código
        SendEmailVerificationJob::dispatch(
            $event->user,
            $event->verificationToken,
            $verificationCode
        );

        \Log::debug('SendVerificationEmail listener: Job dispatched');
    }
}