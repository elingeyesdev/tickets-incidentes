<?php

declare(strict_types=1);

namespace App\Features\AuditLog\Listeners;

use App\Features\AuditLog\Services\ActivityLogService;
use App\Features\Authentication\Events\UserLoggedIn;
use App\Features\Authentication\Events\UserLoggedOut;
use App\Features\Authentication\Events\UserRegistered;
use App\Features\Authentication\Events\EmailVerified;
use App\Features\Authentication\Events\PasswordResetRequested;

/**
 * LogAuthActivity
 *
 * Listener para registrar actividad de autenticación.
 */
class LogAuthActivity
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    /**
     * Registrar login exitoso
     */
    public function handleLogin(UserLoggedIn $event): void
    {
        $this->activityLogService->logLogin(
            userId: $event->user->id,
            deviceInfo: $event->deviceInfo
        );
    }

    /**
     * Registrar logout
     */
    public function handleLogout(UserLoggedOut $event): void
    {
        $this->activityLogService->logLogout($event->user->id);
    }

    /**
     * Registrar registro de usuario
     */
    public function handleRegistered(UserRegistered $event): void
    {
        $this->activityLogService->log(
            action: 'register',
            userId: $event->user->id,
            entityType: 'user',
            entityId: $event->user->id,
            newValues: [
                'email' => $event->user->email,
            ]
        );
    }

    /**
     * Registrar verificación de email
     */
    public function handleEmailVerified(EmailVerified $event): void
    {
        $this->activityLogService->log(
            action: 'email_verified',
            userId: $event->user->id,
            entityType: 'user',
            entityId: $event->user->id
        );
    }

    /**
     * Registrar solicitud de reset de password
     */
    public function handlePasswordResetRequested(PasswordResetRequested $event): void
    {
        $this->activityLogService->log(
            action: 'password_reset_requested',
            userId: $event->user->id,
            entityType: 'user',
            entityId: $event->user->id
        );
    }
}
