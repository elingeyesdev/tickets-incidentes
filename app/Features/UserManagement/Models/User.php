<?php

namespace App\Features\UserManagement\Models;

use App\Shared\Enums\UserStatus;
use App\Shared\Traits\HasUuid;
use App\Shared\Traits\Auditable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * User Model
 *
 * Modelo principal de usuarios del sistema.
 * Tabla: auth.users
 *
 * @property string $id
 * @property string $user_code
 * @property string $email
 * @property string $password_hash
 * @property bool $email_verified
 * @property \DateTime|null $email_verified_at
 * @property UserStatus $status
 * @property string $auth_provider
 * @property \DateTime|null $last_login_at
 * @property string|null $last_login_ip
 * @property \DateTime|null $last_activity_at
 * @property bool $terms_accepted
 * @property \DateTime|null $terms_accepted_at
 * @property string|null $terms_version
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime|null $deleted_at
 *
 * @property-read UserProfile $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<UserRole> $userRoles
 */
class User extends Model implements Authenticatable
{
    use HasFactory;
    use HasUuid;
    use Auditable;
    use SoftDeletes;
    use AuthenticatableTrait;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/UserManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\UserManagement\Database\Factories\UserFactory::new();
    }

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'auth.users';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'user_code',
        'email',
        'password_hash',
        'email_verified',
        'email_verified_at',
        'status',
        'auth_provider',
        'external_auth_id',
        'password_reset_token',
        'password_reset_expires',
        'last_login_at',
        'last_login_ip',
        'last_activity_at',
        'terms_accepted',
        'terms_accepted_at',
        'terms_version',
    ];

    /**
     * Campos ocultos (no exponer en JSON)
     */
    protected $hidden = [
        'password_hash',
        'password_reset_token',
        'external_auth_id',
    ];

    /**
     * Casting de tipos
     */
    protected $casts = [
        'email_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'status' => UserStatus::class,
        'password_reset_expires' => 'datetime',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación 1:1 con UserProfile
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    /**
     * Relación 1:N con UserRole
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id', 'id');
    }

    /**
     * Obtener roles activos del usuario
     */
    public function activeRoles(): HasMany
    {
        return $this->userRoles()->where('is_active', true);
    }

    // ==================== MÉTODOS DE AUTENTICACIÓN ====================

    /**
     * Obtener el nombre de la columna para autenticación
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Obtener el password para autenticación
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // ==================== MÉTODOS DE VERIFICACIÓN ====================

    /**
     * Verificar si el usuario está activo
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Verificar si el usuario está suspendido
     */
    public function isSuspended(): bool
    {
        return $this->status === UserStatus::SUSPENDED;
    }

    /**
     * Verificar si el usuario está eliminado (soft delete)
     */
    public function isDeleted(): bool
    {
        return $this->status === UserStatus::DELETED || $this->trashed();
    }

    /**
     * Verificar si el email está verificado
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified;
    }

    /**
     * Verificar si aceptó los términos
     */
    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted;
    }

    /**
     * Verificar si puede acceder al sistema
     */
    public function canAccess(): bool
    {
        return $this->isActive()
            && !$this->isDeleted()
            && $this->hasVerifiedEmail()
            && $this->hasAcceptedTerms();
    }

    // ==================== MÉTODOS DE ROLES ====================

    /**
     * Verificar si tiene un rol específico (en cualquier empresa)
     *
     * @param string $roleCode Código del rol: 'platform_admin', 'company_admin', 'agent', 'user'
     */
    public function hasRole(string $roleCode): bool
    {
        return $this->activeRoles()
            ->where('role_code', $roleCode)
            ->exists();
    }

    /**
     * Verificar si tiene un rol en una empresa específica
     *
     * @param string $roleCode Código del rol
     * @param string $companyId ID de la empresa
     */
    public function hasRoleInCompany(string $roleCode, string $companyId): bool
    {
        return $this->activeRoles()
            ->where('role_code', $roleCode)
            ->where('company_id', $companyId)
            ->exists();
    }

    /**
     * Obtener todos los role_codes del usuario
     */
    public function getRoleCodes(): array
    {
        return $this->activeRoles()
            ->pluck('role_code')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Obtener todos los nombres legibles de los roles
     */
    public function getRoleNames(): array
    {
        return $this->activeRoles()
            ->with('role')
            ->get()
            ->pluck('role.role_name')
            ->unique()
            ->values()
            ->toArray();
    }

    // ==================== MÉTODOS DE ACTIVIDAD ====================

    /**
     * Registrar último login
     */
    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Registrar actividad
     */
    public function recordActivity(): void
    {
        $this->update([
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Marcar email como verificado
     */
    public function markEmailAsVerified(): void
    {
        $this->update([
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Aceptar términos y condiciones
     */
    public function acceptTerms(string $version): void
    {
        $this->update([
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'terms_version' => $version,
        ]);
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Solo usuarios activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    /**
     * Scope: Solo usuarios con email verificado
     */
    public function scopeVerified($query)
    {
        return $query->where('email_verified', true);
    }

    /**
     * Scope: Buscar por email o código de usuario
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('email', 'ILIKE', "%{$search}%")
              ->orWhere('user_code', 'ILIKE', "%{$search}%");
        });
    }
}
