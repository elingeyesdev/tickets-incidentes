<?php

declare(strict_types=1);

namespace App\Features\AuditLog\Models;

use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ActivityLog Model
 *
 * Modelo para registro de actividad del sistema.
 * Tabla: audit.activity_logs
 *
 * @property string $id
 * @property string|null $user_id
 * @property string $action
 * @property string|null $entity_type
 * @property string|null $entity_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property array|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \DateTime $created_at
 *
 * @property-read User|null $user
 */
class ActivityLog extends Model
{
    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'audit.activity_logs';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Solo tiene created_at, no updated_at
     */
    public const UPDATED_AT = null;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * Conversión de tipos
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ==================== SCOPES ====================

    /**
     * Filtrar por usuario
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filtrar por acción
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Filtrar por tipo de entidad
     */
    public function scopeForEntity($query, string $entityType, ?string $entityId = null)
    {
        $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }

    /**
     * Filtrar por rango de fechas
     */
    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Filtrar acciones de autenticación
     */
    public function scopeAuthActions($query)
    {
        return $query->whereIn('action', [
            'login',
            'login_failed',
            'logout',
            'register',
            'email_verified',
            'password_reset_requested',
            'password_changed',
        ]);
    }

    /**
     * Filtrar acciones de tickets
     */
    public function scopeTicketActions($query)
    {
        return $query->whereIn('action', [
            'ticket_created',
            'ticket_updated',
            'ticket_deleted',
            'ticket_resolved',
            'ticket_closed',
            'ticket_reopened',
            'ticket_assigned',
            'ticket_response_added',
            'ticket_attachment_added',
        ]);
    }

    /**
     * Filtrar acciones de usuarios
     */
    public function scopeUserActions($query)
    {
        return $query->whereIn('action', [
            'user_status_changed',
            'role_assigned',
            'role_removed',
            'profile_updated',
        ]);
    }

    /**
     * Filtrar acciones de empresas
     */
    public function scopeCompanyActions($query)
    {
        return $query->whereIn('action', [
            'company_created',
            'company_request_approved',
            'company_request_rejected',
        ]);
    }

    // ==================== HELPERS ====================

    /**
     * Obtener descripción legible de la acción
     */
    public function getActionDescriptionAttribute(): string
    {
        $descriptions = [
            // Autenticación
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
            // Usuarios
            'user_status_changed' => 'Estado de usuario cambiado',
            'role_assigned' => 'Rol asignado',
            'role_removed' => 'Rol removido',
            'profile_updated' => 'Perfil actualizado',
            // Empresas
            'company_created' => 'Empresa creada',
            'company_request_approved' => 'Solicitud de empresa aprobada',
            'company_request_rejected' => 'Solicitud de empresa rechazada',
        ];

        return $descriptions[$this->action] ?? $this->action;
    }

    /**
     * Obtener categoría de la acción
     */
    public function getActionCategoryAttribute(): string
    {
        $authActions = ['login', 'login_failed', 'logout', 'register', 'email_verified', 'password_reset_requested', 'password_changed'];
        $ticketActions = ['ticket_created', 'ticket_updated', 'ticket_deleted', 'ticket_resolved', 'ticket_closed', 'ticket_reopened', 'ticket_assigned', 'ticket_response_added', 'ticket_attachment_added'];
        $userActions = ['user_status_changed', 'role_assigned', 'role_removed', 'profile_updated'];
        $companyActions = ['company_created', 'company_request_approved', 'company_request_rejected'];

        if (in_array($this->action, $authActions)) {
            return 'authentication';
        }
        if (in_array($this->action, $ticketActions)) {
            return 'tickets';
        }
        if (in_array($this->action, $userActions)) {
            return 'users';
        }
        if (in_array($this->action, $companyActions)) {
            return 'companies';
        }

        return 'other';
    }
}
