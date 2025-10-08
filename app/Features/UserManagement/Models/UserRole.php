<?php

namespace App\Features\UserManagement\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserRole Model
 *
 * Tabla pivot entre users y roles con contexto de empresa.
 * Permite asignación multi-tenant de roles.
 * Tabla: auth.user_roles
 *
 * IMPORTANTE:
 * - FK a role_code VARCHAR (NO role_id UUID)
 * - CHECK constraint: company_admin y agent REQUIEREN company_id
 *
 * Referencia: Modelado V7.0 líneas 137-157
 *
 * @property string $id
 * @property string $user_id
 * @property string $role_code (FK a roles)
 * @property string|null $company_id
 * @property bool $is_active
 * @property \DateTime $assigned_at
 * @property string|null $assigned_by
 * @property \DateTime|null $revoked_at
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read User $user
 * @property-read Role $role
 * @property-read User|null $assignedByUser
 */
class UserRole extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'auth.user_roles';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Deshabilitar timestamps automáticos (no tiene created_at/updated_at)
     * Solo tiene assigned_at que se maneja manualmente
     */
    public $timestamps = false;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'user_id',
        'role_code',  // FK a VARCHAR, no UUID
        'company_id',
        'is_active',
        'assigned_at',
        'assigned_by',
        'revoked_at',
    ];

    /**
     * Casting de tipos
     */
    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación con Role (FK a role_code VARCHAR)
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_code', 'role_code');
    }

    /**
     * Relación con el usuario que asignó el rol
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id');
    }

    /**
     * Relación con Company (solo para roles AGENT y COMPANY_ADMIN)
     * Puede ser null para roles USER y PLATFORM_ADMIN
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Features\CompanyManagement\Models\Company::class, 'company_id', 'id');
    }

    // ==================== OBSERVERS / HOOKS ====================

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-asignar fecha de asignación
        static::creating(function (UserRole $userRole) {
            if (empty($userRole->assigned_at)) {
                $userRole->assigned_at = now();
            }
        });
    }

    // ==================== MÉTODOS DE VERIFICACIÓN ====================

    /**
     * Verificar si el rol está activo
     */
    public function isActive(): bool
    {
        return $this->is_active && is_null($this->revoked_at);
    }

    /**
     * Verificar si el rol está revocado
     */
    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }

    /**
     * Verificar si es un rol global (sin empresa)
     */
    public function isGlobal(): bool
    {
        return is_null($this->company_id);
    }

    /**
     * Verificar si tiene contexto de empresa
     */
    public function hasCompanyContext(): bool
    {
        return !is_null($this->company_id);
    }

    // ==================== MÉTODOS DE ACCIÓN ====================

    /**
     * Activar el rol
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'revoked_at' => null,
        ]);
    }

    /**
     * Desactivar el rol
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
        ]);
    }

    /**
     * Revocar el rol permanentemente
     */
    public function revoke(): void
    {
        $this->update([
            'is_active' => false,
            'revoked_at' => now(),
        ]);
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Solo roles activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->whereNull('revoked_at');
    }

    /**
     * Scope: Solo roles revocados
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope: Por empresa
     */
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Roles globales (sin empresa)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('company_id');
    }

    /**
     * Scope: Por código de rol
     */
    public function scopeByRoleCode($query, string $roleCode)
    {
        return $query->where('role_code', $roleCode);
    }

    /**
     * Scope: Asignados recientemente
     */
    public function scopeRecentlyAssigned($query, int $days = 7)
    {
        return $query->where('assigned_at', '>=', now()->subDays($days));
    }
}