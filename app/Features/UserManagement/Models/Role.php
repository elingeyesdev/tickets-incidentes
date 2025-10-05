<?php

namespace App\Features\UserManagement\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role Model
 *
 * Catálogo de roles del sistema.
 * Tabla: auth.roles
 *
 * Roles disponibles:
 * - USER: Usuario final
 * - AGENT: Agente de soporte (requiere empresa)
 * - COMPANY_ADMIN: Administrador de empresa (requiere empresa)
 * - PLATFORM_ADMIN: Administrador de plataforma
 *
 * @property string $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property array $permissions
 * @property bool $requires_company
 * @property string $default_dashboard
 * @property int $priority
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<UserRole> $userRoles
 */
class Role extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'auth.roles';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'requires_company',
        'default_dashboard',
        'priority',
    ];

    /**
     * Casting de tipos
     */
    protected $casts = [
        'permissions' => 'array',
        'requires_company' => 'boolean',
        'priority' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Constantes de roles
     */
    public const USER = 'USER';
    public const AGENT = 'AGENT';
    public const COMPANY_ADMIN = 'COMPANY_ADMIN';
    public const PLATFORM_ADMIN = 'PLATFORM_ADMIN';

    /**
     * Relación 1:N con UserRole
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'role_id', 'id');
    }

    // ==================== MÉTODOS DE VERIFICACIÓN ====================

    /**
     * Verificar si el rol requiere contexto de empresa
     */
    public function requiresCompany(): bool
    {
        return $this->requires_company;
    }

    /**
     * Verificar si es rol de usuario
     */
    public function isUser(): bool
    {
        return $this->name === self::USER;
    }

    /**
     * Verificar si es rol de agente
     */
    public function isAgent(): bool
    {
        return $this->name === self::AGENT;
    }

    /**
     * Verificar si es rol de administrador de empresa
     */
    public function isCompanyAdmin(): bool
    {
        return $this->name === self::COMPANY_ADMIN;
    }

    /**
     * Verificar si es rol de administrador de plataforma
     */
    public function isPlatformAdmin(): bool
    {
        return $this->name === self::PLATFORM_ADMIN;
    }

    // ==================== MÉTODOS DE PERMISOS ====================

    /**
     * Verificar si tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        // Si tiene permiso total (*)
        if (in_array('*', $this->permissions)) {
            return true;
        }

        // Verificar permiso exacto
        if (in_array($permission, $this->permissions)) {
            return true;
        }

        // Verificar wildcards (ej: 'tickets.*' incluye 'tickets.create')
        foreach ($this->permissions as $rolePermission) {
            if (str_ends_with($rolePermission, '.*')) {
                $prefix = substr($rolePermission, 0, -2);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verificar si tiene todos los permisos especificados
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar si tiene al menos uno de los permisos especificados
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener dashboard por defecto
     */
    public function getDefaultDashboard(): string
    {
        return $this->default_dashboard;
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Roles que requieren empresa
     */
    public function scopeRequiresCompany($query)
    {
        return $query->where('requires_company', true);
    }

    /**
     * Scope: Roles globales (no requieren empresa)
     */
    public function scopeGlobal($query)
    {
        return $query->where('requires_company', false);
    }

    /**
     * Scope: Ordenar por prioridad (mayor a menor)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope: Buscar por nombre
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Obtener rol USER
     */
    public static function user(): ?self
    {
        return static::where('name', self::USER)->first();
    }

    /**
     * Obtener rol AGENT
     */
    public static function agent(): ?self
    {
        return static::where('name', self::AGENT)->first();
    }

    /**
     * Obtener rol COMPANY_ADMIN
     */
    public static function companyAdmin(): ?self
    {
        return static::where('name', self::COMPANY_ADMIN)->first();
    }

    /**
     * Obtener rol PLATFORM_ADMIN
     */
    public static function platformAdmin(): ?self
    {
        return static::where('name', self::PLATFORM_ADMIN)->first();
    }
}