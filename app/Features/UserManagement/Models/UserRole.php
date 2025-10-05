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
 * @property string $id
 * @property string $user_id
 * @property string $role_id
 * @property string|null $company_id
 * @property bool $is_active
 * @property \DateTime $assigned_at
 * @property \DateTime|null $revoked_at
 * @property string|null $assigned_by_id
 * @property string|null $revoked_by_id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read User $user
 * @property-read Role $role
 * @property-read User|null $assignedBy
 * @property-read User|null $revokedBy
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
     * Campos asignables en masa
     */
    protected $fillable = [
        'user_id',
        'role_id',
        'company_id',
        'is_active',
        'assigned_at',
        'revoked_at',
        'assigned_by_id',
        'revoked_by_id',
    ];

    /**
     * Casting de tipos
     */
    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación con Role
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * Relación con el usuario que asignó el rol
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id', 'id');
    }

    /**
     * Relación con el usuario que revocó el rol
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_id', 'id');
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
            'revoked_by_id' => null,
        ]);
    }

    /**
     * Desactivar el rol
     */
    public function deactivate(?string $revokedById = null): void
    {
        $this->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by_id' => $revokedById,
        ]);
    }

    /**
     * Revocar el rol permanentemente
     */
    public function revoke(?string $revokedById = null): void
    {
        $this->deactivate($revokedById);
    }

    // ==================== MÉTODOS DE PERMISOS ====================

    /**
     * Verificar si tiene permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->role->hasPermission($permission);
    }

    /**
     * Obtener todos los permisos del rol
     */
    public function getPermissions(): array
    {
        return $this->role->permissions ?? [];
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
     * Scope: Por rol específico
     */
    public function scopeByRole($query, string $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope: Por nombre de rol
     */
    public function scopeByRoleName($query, string $roleName)
    {
        return $query->whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Scope: Asignados recientemente
     */
    public function scopeRecentlyAssigned($query, int $days = 7)
    {
        return $query->where('assigned_at', '>=', now()->subDays($days));
    }
}