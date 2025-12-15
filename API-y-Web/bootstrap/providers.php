<?php

return [
    App\Providers\AppServiceProvider::class,

    // Feature Service Providers
    App\Features\Authentication\AuthenticationServiceProvider::class,
    App\Features\UserManagement\UserManagementServiceProvider::class,
    App\Features\CompanyManagement\CompanyManagementServiceProvider::class,
    App\Features\TicketManagement\TicketManagementServiceProvider::class,
    App\Features\ContentManagement\ContentManagementServiceProvider::class,
    App\Features\AuditLog\AuditLogServiceProvider::class,
];
