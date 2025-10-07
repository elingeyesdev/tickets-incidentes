<?php

namespace App\Features\UserManagement\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role Model
 *
 * Catálogo de roles FIJOS del sistema.
 * Tabla: auth.roles
 *
 * Roles disponibles (no se pueden modificar):
 * - platform_admin: Administrador de Plataforma
 * - company_admin: Administrador de Empresa
 * - agent: Agente de Soporte
 * - user: Cliente
 *
 * IMPORTANTE:
 * - Permisos se manejan en Laravel Policies, NO en BD
 * - role_code es la clave principal para consultas
 * - Solo created_at (roles no se modifican, no hay updated_at)
 *
 * Referencia: Modelado V7.0 líneas 117-135
 *
 * @property string $id
 * @property string $role_code
 * @property string $role_name
 * @property string|null $description
 * @property bool $is_system
 * @property \DateTime $created_at
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
     * Disable updated_at (roles no se modifican)
     */
    const UPDATED_AT = null;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'role_code',
        'role_name',
        'description',
        'is_system',
    ];

    /**
     * Casting de tipos
     */
    protected $casts = [
        'is_system' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Constantes de códigos de roles
     */
    public const PLATFORM_ADMIN = 'platform_admin';
    public const COMPANY_ADMIN = 'company_admin';
    public const AGENT = 'agent';
    public const USER = 'user';

    /**
     * Relación 1:N con UserRole
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'role_code', 'role_code');
    }

    // ==================== MÉTODOS DE VERIFICACIÓN ====================

    /**
     * Verificar si es rol del sistema (no se puede eliminar)
     */
    public function isSystemRole(): bool
    {
        return $this->is_system;
    }

    /**
     * Verificar si es rol de administrador de plataforma
     */
    public function isPlatformAdmin(): bool
    {
        return $this->role_code === self::PLATFORM_ADMIN;
    }

    /**
     * Verificar si es rol de administrador de empresa
     */
    public function isCompanyAdmin(): bool
    {
        return $this->role_code === self::COMPANY_ADMIN;
    }

    /**
     * Verificar si es rol de agente
     */
    public function isAgent(): bool
    {
        return $this->role_code === self::AGENT;
    }

    /**
     * Verificar si es rol de usuario
     */
    public function isUser(): bool
    {
        return $this->role_code === self::USER;
    }

    /**
     * Verificar si requiere contexto de empresa
     */
    public function requiresCompany(): bool
    {
        return in_array($this->role_code, [self::COMPANY_ADMIN, self::AGENT]);
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Roles del sistema
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: Roles que requieren empresa
     */
    public function scopeRequiresCompany($query)
    {
        return $query->whereIn('role_code', [self::COMPANY_ADMIN, self::AGENT]);
    }

    /**
     * Scope: Roles globales (no requieren empresa)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNotIn('role_code', [self::COMPANY_ADMIN, self::AGENT]);
    }

    /**
     * Scope: Buscar por código
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('role_code', $code);
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Obtener rol por código
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('role_code', $code)->first();
    }

    /**
     * Obtener rol platform_admin
     */
    public static function platformAdmin(): ?self
    {
        return static::findByCode(self::PLATFORM_ADMIN);
    }

    /**
     * Obtener rol company_admin
     */
    public static function companyAdmin(): ?self
    {
        return static::findByCode(self::COMPANY_ADMIN);
    }

    /**
     * Obtener rol agent
     */
    public static function agent(): ?self
    {
        return static::findByCode(self::AGENT);
    }

    /**
     * Obtener rol user
     */
    public static function user(): ?self
    {
        return static::findByCode(self::USER);
    }

    /**
     * Obtener todos los códigos de roles
     */
    public static function allCodes(): array
    {
        return [
            self::PLATFORM_ADMIN,
            self::COMPANY_ADMIN,
            self::AGENT,
            self::USER,
        ];
    }
}