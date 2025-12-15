<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Log Enabled
    |--------------------------------------------------------------------------
    |
    | This option enables or disables the activity log feature.
    |
    */
    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Redis Buffer
    |--------------------------------------------------------------------------
    |
    | Enable Redis buffering for better performance under high load.
    | Logs will be batched and written to database periodically.
    |
    */
    'use_redis_buffer' => env('AUDIT_USE_REDIS_BUFFER', true),

    /*
    |--------------------------------------------------------------------------
    | Buffer Size
    |--------------------------------------------------------------------------
    |
    | Number of log entries to buffer before flushing to database.
    |
    */
    'buffer_size' => env('AUDIT_BUFFER_SIZE', 50),

    /*
    |--------------------------------------------------------------------------
    | Retention Days
    |--------------------------------------------------------------------------
    |
    | Number of days to retain activity logs before automatic cleanup.
    |
    */
    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Excluded Actions
    |--------------------------------------------------------------------------
    |
    | Actions that should not be logged (e.g., read operations).
    |
    */
    'excluded_actions' => [
        // Add any actions you want to exclude from logging
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions to Log
    |--------------------------------------------------------------------------
    |
    | Complete list of actions that will be logged.
    |
    */
    'actions' => [
        // Authentication
        'login' => 'Inicio de sesión',
        'login_failed' => 'Intento de inicio de sesión fallido',
        'logout' => 'Cierre de sesión',
        'register' => 'Registro de cuenta',
        'email_verified' => 'Email verificado',
        'password_reset_requested' => 'Solicitud de recuperación de contraseña',
        'password_changed' => 'Contraseña cambiada',

        // Tickets
        'ticket_created' => 'Ticket creado',
        'ticket_updated' => 'Ticket actualizado',
        'ticket_deleted' => 'Ticket eliminado',
        'ticket_resolved' => 'Ticket resuelto',
        'ticket_closed' => 'Ticket cerrado',
        'ticket_reopened' => 'Ticket reabierto',
        'ticket_assigned' => 'Ticket asignado',
        'ticket_response_added' => 'Respuesta agregada al ticket',
        'ticket_attachment_added' => 'Adjunto agregado al ticket',

        // Users
        'user_status_changed' => 'Estado de usuario cambiado',
        'role_assigned' => 'Rol asignado',
        'role_removed' => 'Rol removido',
        'profile_updated' => 'Perfil actualizado',

        // Companies
        'company_created' => 'Empresa creada',
        'company_request_approved' => 'Solicitud de empresa aprobada',
        'company_request_rejected' => 'Solicitud de empresa rechazada',
    ],
];
