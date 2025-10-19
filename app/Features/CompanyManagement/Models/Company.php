<?php

namespace App\Features\CompanyManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUuid;

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
        'created_from_request_id',
        'admin_user_id',
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'id' => 'string',
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
     * Obtener la solicitud de empresa que creó esta empresa.
     */
    public function createdFromRequest(): BelongsTo
    {
        return $this->belongsTo(CompanyRequest::class, 'created_from_request_id');
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
        )->withTimestamps('followed_at');
    }

    /**
     * Obtener registros de seguidores (con datos completos del pivot).
     */
    public function followerRecords(): HasMany
    {
        return $this->hasMany(CompanyFollower::class, 'company_id');
    }

    /**
     * Scope: Solo empresas activas.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Solo empresas suspendidas.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
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
     * Obtener conteo de agentes activos (calculado).
     */
    public function getActiveAgentsCountAttribute(): int
    {
        return $this->userRoles()
            ->where('role_code', 'agent')
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
        if (!$this->relationLoaded('admin')) {
            $this->load('admin.profile');
        }

        $admin = $this->admin;
        if (!$admin) {
            return 'Unknown';
        }

        $profile = $admin->profile;
        if (!$profile) {
            return $admin->email;
        }

        return $profile->first_name . ' ' . $profile->last_name;
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
}
