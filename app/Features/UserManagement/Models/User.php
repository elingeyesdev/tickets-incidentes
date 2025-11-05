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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
 * @property bool $onboarding_completed
 * @property \DateTime|null $onboarding_completed_at
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
        'onboarding_completed_at',
        'has_temporary_password',
        'temporary_password_expires_at',
    ];

    /**
     * Campos ocultos (no exponer en JSON)
     */
    protected $hidden = [
        'password',
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
        'onboarding_completed_at' => 'datetime',
        'has_temporary_password' => 'boolean',
        'temporary_password_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Accessors to append to JSON (para GraphQL UserAuthInfo)
     */
    protected $appends = [
        'displayName',
        'avatarUrl',
        'theme',
        'language',
        'hasTemporaryPassword',
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

    /**
     * Obtener empresas seguidas por el usuario (relación many-to-many)
     */
    public function followedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Features\CompanyManagement\Models\Company::class,
            'business.user_company_followers',
            'user_id',
            'company_id'
        )->withTimestamps('followed_at', 'followed_at');
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
     * Obtener el nombre de la columna del password
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
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
     *
     * IMPORTANTE: Email verification NO es requerido para acceder.
     * Solo requiere: usuario activo, no eliminado, y términos aceptados.
     */
    public function canAccess(): bool
    {
        return $this->isActive()
            && !$this->isDeleted()
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

    /**
     * Obtener TODOS los roles del usuario con company_id para el JWT.
     *
     * Retorna array de roles incluyendo company_id (null para PLATFORM_ADMIN y USER).
     * Usado para incluir todos los roles en el token JWT.
     *
     * @return array Array de roles: [["code" => "COMPANY_ADMIN", "company_id" => "uuid"], ...]
     */
    public function getAllRolesForJWT(): array
    {
        $roles = $this->activeRoles()
            ->get()
            ->map(fn($userRole) => [
                'code' => $userRole->role_code,
                'company_id' => $userRole->company_id,
            ])
            ->values()
            ->toArray();

        // Si no tiene roles, retornar USER por defecto
        if (empty($roles)) {
            return [
                [
                    'code' => 'USER',
                    'company_id' => null,
                ],
            ];
        }

        return $roles;
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

    // ==================== MÉTODOS DE ONBOARDING ====================

    /**
     * Verificar si completó el onboarding
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed;
    }

    /**
     * Marcar onboarding como completado
     *
     * IMPORTANTE: Email verification NO es prerequisito ni parte del onboarding.
     * Este método debe ser llamado después de que el usuario complete:
     * 1. Completar perfil (first_name, last_name) - PASO 1
     * 2. Configurar preferencias (theme, language) - PASO 2
     *
     * Email verification es OPCIONAL y puede hacerse en cualquier momento.
     */
    public function markOnboardingAsCompleted(): void
    {
        $this->update([
            'onboarding_completed_at' => now(),
        ]);
    }

    /**
     * Verificar si el usuario puede acceder a la zona authenticated
     *
     * Requiere:
     * - Usuario activo
     * - Términos aceptados
     * - Onboarding completado
     *
     * IMPORTANTE: Email verification NO es requerido.
     */
    public function canAccessAuthenticatedZone(): bool
    {
        return $this->canAccess() && $this->hasCompletedOnboarding();
    }

    /**
     * Asignar un rol al usuario (V10.1)
     *
     * IMPORTANTE: Normaliza automáticamente roleCode a UPPERCASE_SNAKE_CASE
     * para consistencia con la BD.
     *
     * @param string $roleCode Código del rol (USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
     * @param string|null $companyId ID de la empresa (requerido para AGENT y COMPANY_ADMIN)
     * @return \App\Features\UserManagement\Models\UserRole
     */
    public function assignRole(string $roleCode, ?string $companyId = null): UserRole
    {
        $roleService = app(\App\Features\UserManagement\Services\RoleService::class);

        // Normalizar roleCode a UPPERCASE_SNAKE_CASE para consistencia
        // Acepta: 'agent', 'Agent', 'AGENT', 'platform_admin', etc.
        $normalizedRoleCode = strtoupper($roleCode);

        $result = $roleService->assignRoleToUser(
            userId: $this->id,
            roleCode: $normalizedRoleCode,
            companyId: $companyId,
            assignedBy: null
        );

        // Retornar solo el UserRole (extraer del array result)
        return $result['role'];
    }

    // ==================== ACCESSORS FOR GRAPHQL ====================

    /**
     * Accessor: displayName para UserAuthInfo GraphQL type
     * Computed from profile->first_name + profile->last_name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->profile && $this->profile->first_name) {
            return trim("{$this->profile->first_name} {$this->profile->last_name}");
        }
        return $this->email;
    }

    /**
     * Accessor: avatarUrl para UserAuthInfo GraphQL type
     * Direct access to profile->avatar_url
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile?->avatar_url ?? null;
    }

    /**
     * Accessor: theme para UserAuthInfo GraphQL type
     * Direct access to profile->theme
     */
    public function getThemeAttribute(): string
    {
        return $this->profile?->theme ?? 'light';
    }

    /**
     * Accessor: language para UserAuthInfo GraphQL type
     * Direct access to profile->language
     */
    public function getLanguageAttribute(): string
    {
        return $this->profile?->language ?? 'es';
    }

    /**
     * Accessor: hasTemporaryPassword para GraphQL
     * Retorna si el usuario tiene password temporal activo
     */
    public function getHasTemporaryPasswordAttribute(): bool
    {
        return $this->attributes['has_temporary_password'] ?? false;
    }

    /**
     * Override password attribute to use password_hash instead
     * This prevents Laravel's AuthenticatableTrait from trying to set 'password' column
     */
    public function getPasswordAttribute(): ?string
    {
        return $this->password_hash;
    }

    /**
     * Override password mutator to use password_hash instead
     */
    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password_hash'] = $value;
    }

    /**
     * Accessor booleano: onboardingComplete
     *
     * Se calcula dinámicamente desde onboarding_completed_at para compatibilidad con tests.
     * La base de datos usa onboarding_completed_at (timestamp) que es profesional y auditable.
     * Este accessor permite usar $user->onboarding_complete (booleano) en código y tests.
     *
     * Lógica: onboarding_complete = (onboarding_completed_at !== null)
     */
    protected function onboardingComplete(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->onboarding_completed_at !== null,
        );
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

    /**
     * Scope: Solo usuarios que completaron onboarding
     */
    public function scopeOnboardingCompleted($query)
    {
        return $query->whereNotNull('onboarding_completed_at');
    }

    /**
     * Scope: Solo usuarios que NO completaron onboarding
     */
    public function scopeOnboardingPending($query)
    {
        return $query->whereNull('onboarding_completed_at');
    }
}
