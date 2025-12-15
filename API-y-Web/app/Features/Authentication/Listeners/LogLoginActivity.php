<?php

namespace App\Features\Authentication\Listeners;

use App\Features\Authentication\Events\UserLoggedIn;
use Illuminate\Support\Facades\Log;

/**
 * Log Login Activity Listener
 *
 * Escucha el evento UserLoggedIn y registra la actividad.
 *
 * NOTA: Por ahora usa Log::info() porque AuditLog viene en Phase 6.
 * Cuando implementemos el feature de Auditoría, cambiaremos a AuditLog::create()
 */
class LogLoginActivity
{
    /**
     * Handle the event.
     */
    public function handle(UserLoggedIn $event): void
    {
        // Por ahora solo loguear en archivo
        // TODO: En Phase 6, reemplazar con AuditLog::create()
        Log::info('User logged in', [
            'user_id' => $event->user->id,
            'user_code' => $event->user->user_code,
            'email' => $event->user->email,
            'device_name' => $event->deviceInfo['name'] ?? 'Unknown',
            'ip_address' => $event->deviceInfo['ip'] ?? null,
            'user_agent' => $event->deviceInfo['user_agent'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Futuro (Phase 6):
        // AuditLog::create([
        //     'user_id' => $event->user->id,
        //     'action' => 'user.login',
        //     'ip_address' => $event->deviceInfo['ip'] ?? null,
        //     'user_agent' => $event->deviceInfo['user_agent'] ?? null,
        //     'metadata' => [
        //         'device_name' => $event->deviceInfo['name'] ?? null,
        //         'user_code' => $event->user->user_code,
        //     ],
        // ]);
    }
}