<?php

namespace App\Shared\Enums;

/**
 * Enum para roles del sistema
 *
 * Define los 4 roles principales del sistema Helpdesk multi-tenant.
 * Cada rol tiene permisos y contextos específicos.
 */
enum Role: string
{
    /**
     * Usuario final - Crea tickets y gestiona su perfil
     * NO requiere contexto de empresa
     */
    case USER = 'USER';

    /**
     * Agente de soporte - Responde tickets de su empresa
     * REQUIERE contexto de empresa específica
     */
    case AGENT = 'AGENT';

    /**
     * Administrador de empresa - Gestiona una empresa específica
     * REQUIERE contexto de empresa específica
     */
    case COMPANY_ADMIN = 'COMPANY_ADMIN';

    /**
     * Administrador de plataforma - Gestión completa del sistema
     * NO requiere contexto de empresa (acceso global)
     */
    case PLATFORM_ADMIN = 'PLATFORM_ADMIN';

    /**
     * Obtiene el nombre legible para UI
     */
    public function label(): string
    {
        return match($this) {
            self::USER => 'Cliente',
            self::AGENT => 'Agente de Soporte',
            self::COMPANY_ADMIN => 'Administrador de Empresa',
            self::PLATFORM_ADMIN => 'Administrador de Plataforma',
        };
    }

    /**
     * Descripción detallada del rol
     */
    public function description(): string
    {
        return match($this) {
            self::USER => 'Usuario que crea y gestiona tickets de soporte',
            self::AGENT => 'Agente que responde y resuelve tickets de una empresa',
            self::COMPANY_ADMIN => 'Administrador que gestiona una empresa específica y sus agentes',
            self::PLATFORM_ADMIN => 'Administrador con acceso completo a todo el sistema',
        };
    }

    /**
     * Verifica si el rol requiere contexto de empresa
     */
    public function requiresCompany(): bool
    {
        return match($this) {
            self::AGENT, self::COMPANY_ADMIN => true,
            self::USER, self::PLATFORM_ADMIN => false,
        };
    }

    /**
     * Verifica si es un rol administrativo
     */
    public function isAdmin(): bool
    {
        return match($this) {
            self::COMPANY_ADMIN, self::PLATFORM_ADMIN => true,
            self::USER, self::AGENT => false,
        };
    }

    /**
     * Verifica si puede crear tickets
     */
    public function canCreateTickets(): bool
    {
        return $this !== self::PLATFORM_ADMIN; // Todos excepto platform admin
    }

    /**
     * Verifica si puede responder tickets
     */
    public function canRespondTickets(): bool
    {
        return match($this) {
            self::AGENT, self::COMPANY_ADMIN => true,
            self::USER, self::PLATFORM_ADMIN => false,
        };
    }

    /**
     * Obtiene la ruta del dashboard por defecto
     */
    public function defaultDashboard(): string
    {
        return match($this) {
            self::USER => '/tickets',
            self::AGENT => '/agent/dashboard',
            self::COMPANY_ADMIN => '/company/dashboard',
            self::PLATFORM_ADMIN => '/admin/dashboard',
        };
    }

    /**
     * Obtiene permisos principales del rol
     *
     * @return array<string>
     */
    public function permissions(): array
    {
        return match($this) {
            self::USER => [
                'tickets.create',
                'tickets.view.own',
                'tickets.update.own',
                'profile.update',
            ],
            self::AGENT => [
                'tickets.view.company',
                'tickets.respond',
                'tickets.resolve',
                'tickets.assign',
                'profile.update',
            ],
            self::COMPANY_ADMIN => [
                'company.manage',
                'agents.manage',
                'tickets.view.company',
                'tickets.respond',
                'tickets.resolve',
                'categories.manage',
                'macros.manage',
                'profile.update',
            ],
            self::PLATFORM_ADMIN => [
                '*', // Acceso total
            ],
        };
    }

    /**
     * Verifica si tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions();

        // Si tiene permiso de todo (*)
        if (in_array('*', $permissions)) {
            return true;
        }

        // Verificar permiso exacto
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Verificar wildcards (ej: tickets.* incluye tickets.create)
        foreach ($permissions as $allowed) {
            if (str_ends_with($allowed, '.*')) {
                $prefix = str_replace('.*', '', $allowed);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Obtiene nivel de prioridad del rol (para ordenamiento)
     * Menor número = mayor prioridad
     */
    public function priority(): int
    {
        return match($this) {
            self::PLATFORM_ADMIN => 1,
            self::COMPANY_ADMIN => 2,
            self::AGENT => 3,
            self::USER => 4,
        };
    }

    /**
     * Obtiene color para UI (Tailwind classes)
     */
    public function color(): string
    {
        return match($this) {
            self::USER => 'blue',
            self::AGENT => 'green',
            self::COMPANY_ADMIN => 'purple',
            self::PLATFORM_ADMIN => 'red',
        };
    }

    /**
     * Obtiene todos los roles como array
     *
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return [
            self::USER->value => self::USER->label(),
            self::AGENT->value => self::AGENT->label(),
            self::COMPANY_ADMIN->value => self::COMPANY_ADMIN->label(),
            self::PLATFORM_ADMIN->value => self::PLATFORM_ADMIN->label(),
        ];
    }

    /**
     * Obtiene roles que requieren empresa
     *
     * @return array<Role>
     */
    public static function rolesRequiringCompany(): array
    {
        return array_filter(
            self::cases(),
            fn(Role $role) => $role->requiresCompany()
        );
    }

    /**
     * Obtiene roles que NO requieren empresa
     *
     * @return array<Role>
     */
    public static function rolesWithoutCompany(): array
    {
        return array_filter(
            self::cases(),
            fn(Role $role) => !$role->requiresCompany()
        );
    }
}