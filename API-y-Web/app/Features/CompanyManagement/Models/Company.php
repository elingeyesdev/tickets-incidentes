<?php

namespace App\Features\CompanyManagement\Models;

use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\CompanyManagement\Models\CompanyOnboardingDetails;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Company Model
 * 
 * IMPORTANTE: Este modelo tiene un Global Scope que filtra automáticamente
 * las empresas con status 'active'. Para ver empresas pendientes o rechazadas,
 * usa: Company::withoutGlobalScope('activeOnly')->where('status', 'pending')
 * o los scopes shorthand: Company::pending(), Company::rejected()
 */
class Company extends Model
{
    use HasFactory, HasUuid;

    /**
     * Boot del modelo - Agrega Global Scope para filtrar solo activas por defecto
     */
    protected static function booted(): void
    {
        // Global Scope: Por defecto solo muestra empresas activas
        // Esto protege contra mostrar accidentalmente empresas pendientes/rechazadas
        static::addGlobalScope('activeOnly', function (Builder $builder) {
            $builder->where('status', 'active');
        });
    }

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/CompanyManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\CompanyManagement\Database\Factories\CompanyFactory::new();
    }

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'business.companies';

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'company_code',
        'name',
        'legal_name',
        'description',
        'support_email',
        'phone',
        'website',
        'contact_address',
        'contact_city',
        'contact_state',
        'contact_country',
        'contact_postal_code',
        'tax_id',
        'legal_representative',
        'business_hours',
        'timezone',
        'logo_url',
        'favicon_url',
        'primary_color',
        'secondary_color',
        'settings',
        'status',
        'industry_id',
        'admin_user_id',
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'id' => 'string',
        'industry_id' => 'string',
        'business_hours' => 'array',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener el usuario admin de esta empresa.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Obtener la industria a la que pertenece esta empresa.
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(CompanyIndustry::class, 'industry_id');
    }

    /**
     * Obtener los detalles de onboarding de esta empresa.
     * Contiene metadata del proceso de solicitud original.
     */
    public function onboardingDetails(): HasOne
    {
        return $this->hasOne(CompanyOnboardingDetails::class, 'company_id');
    }

    /**
     * Obtener todos los roles de usuario asociados con esta empresa (agentes, company_admins).
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'company_id');
    }

    /**
     * Obtener todos los seguidores de esta empresa.
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'business.user_company_followers',
            'company_id',
            'user_id'
        )->withTimestamps('followed_at', 'followed_at');
    }

    /**
     * Obtener registros de seguidores (con datos completos del pivot).
     */
    public function followerRecords(): HasMany
    {
        return $this->hasMany(CompanyFollower::class, 'company_id');
    }

    /**
     * Obtener todos los tickets de esta empresa.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(\App\Features\TicketManagement\Models\Ticket::class, 'company_id');
    }

    /**
     * Scope: Solo empresas activas.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Retrieve the model for a bound value (Route Model Binding).
     *
     * Throws a custom exception when the company is not found.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     *
     * @throws \App\Shared\Errors\ErrorWithExtensions
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $company = $this->where($field ?? 'id', $value)->first();

        if (!$company) {
            throw \App\Shared\Errors\ErrorWithExtensions::notFound(
                'Company not found',
                'COMPANY_NOT_FOUND',
                ['companyId' => $value]
            );
        }

        return $company;
    }

    /**
     * Scope: Solo empresas suspendidas.
     */
    public function scopeSuspended($query)
    {
        return $query->withoutGlobalScope('activeOnly')->where('status', 'suspended');
    }

    /**
     * Scope: Solo empresas pendientes de aprobación.
     */
    public function scopePending($query)
    {
        return $query->withoutGlobalScope('activeOnly')->where('status', 'pending');
    }

    /**
     * Scope: Solo empresas rechazadas.
     */
    public function scopeRejected($query)
    {
        return $query->withoutGlobalScope('activeOnly')->where('status', 'rejected');
    }

    /**
     * Scope: Todas las empresas sin importar status.
     * Útil para admins que necesitan ver todo.
     */
    public function scopeWithAllStatuses($query)
    {
        return $query->withoutGlobalScope('activeOnly');
    }

    /**
     * Verificar si la empresa está activa.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verificar si la empresa está suspendida.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Verificar si la empresa está pendiente de aprobación.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verificar si la empresa fue rechazada.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Aprobar la empresa pendiente.
     * Cambia estado a 'active' y asigna el admin.
     */
    public function approve(User $admin, User $reviewer): void
    {
        $this->update([
            'status' => 'active',
            'admin_user_id' => $admin->id,
        ]);

        // Actualizar detalles de onboarding con info del reviewer
        $this->onboardingDetails?->markAsReviewed($reviewer);
    }

    /**
     * Rechazar la empresa pendiente.
     */
    public function reject(User $reviewer, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
        ]);

        // Guardar razón de rechazo en detalles de onboarding
        $this->onboardingDetails?->markAsRejectedByReviewer($reviewer, $reason);
    }

    /**
     * Verificar si la empresa tiene áreas habilitadas.
     */
    public function hasAreasEnabled(): bool
    {
        return ($this->settings['areas_enabled'] ?? false) === true;
    }

    /**
     * Obtener conteo de agentes activos (calculado).
     */
    public function getActiveAgentsCountAttribute(): int
    {
        return $this->userRoles()
            ->where('role_code', 'AGENT')
            ->where('is_active', true)
            ->count();
    }

    /**
     * Obtener conteo total de usuarios (calculado).
     */
    public function getTotalUsersCountAttribute(): int
    {
        return $this->userRoles()
            ->where('is_active', true)
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Obtener conteo de seguidores (calculado).
     */
    public function getFollowersCountAttribute(): int
    {
        return $this->followers()->count();
    }

    /**
     * Obtener conteo total de tickets (calculado).
     * TODO: Implementar cuando la funcionalidad de tickets esté lista
     */
    public function getTotalTicketsCountAttribute(): int
    {
        return 0;
    }

    /**
     * Obtener conteo de tickets abiertos (calculado).
     * TODO: Implementar cuando la funcionalidad de tickets esté lista
     */
    public function getOpenTicketsCountAttribute(): int
    {
        return 0;
    }

    /**
     * Obtener nombre del admin (calculado desde la relación).
     */
    public function getAdminNameAttribute(): string
    {
        try {
            if (!$this->relationLoaded('admin')) {
                $this->load('admin.profile');
            }

            $admin = $this->admin;
            if (!$admin) {
                return 'Unknown';
            }

            $profile = $admin->profile;
            if (!$profile) {
                return $admin->email ?? 'Unknown';
            }

            $firstName = $profile->first_name ?? '';
            $lastName = $profile->last_name ?? '';
            return trim("$firstName $lastName") ?: 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Obtener email del admin (calculado desde la relación).
     */
    public function getAdminEmailAttribute(): string
    {
        if (!$this->relationLoaded('admin')) {
            $this->load('admin');
        }

        return $this->admin?->email ?? 'unknown@example.com';
    }

    /**
     * Obtener ID del admin (alias para consistencia con schema GraphQL).
     */
    public function getAdminIdAttribute(): string
    {
        return $this->admin_user_id;
    }

    /**
     * Obtener nombre de la industria (calculado desde la relación).
     */
    public function getIndustryNameAttribute(): ?string
    {
        if (!$this->relationLoaded('industry')) {
            $this->load('industry');
        }

        return $this->industry?->name;
    }

    /**
     * Obtener código de la industria (calculado desde la relación).
     */
    public function getIndustryCodeAttribute(): ?string
    {
        if (!$this->relationLoaded('industry')) {
            $this->load('industry');
        }

        return $this->industry?->code;
    }
}
