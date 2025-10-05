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
     * The table associated with the model.
     */
    protected $table = 'business.companies';

    /**
     * The attributes that are mass assignable.
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
     * The attributes that should be cast.
     */
    protected $casts = [
        'id' => 'string',
        'business_hours' => 'array',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin user of this company.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Get the company request that created this company.
     */
    public function createdFromRequest(): BelongsTo
    {
        return $this->belongsTo(CompanyRequest::class, 'created_from_request_id');
    }

    /**
     * Get all user roles associated with this company (agents, company_admins).
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'company_id');
    }

    /**
     * Get all followers of this company.
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
     * Get follower records (with full pivot data).
     */
    public function followerRecords(): HasMany
    {
        return $this->hasMany(CompanyFollower::class, 'company_id');
    }

    /**
     * Scope: Active companies only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Suspended companies only.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Check if company is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if company is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Get active agents count (calculated).
     */
    public function getActiveAgentsCountAttribute(): int
    {
        return $this->userRoles()
            ->where('role_code', 'agent')
            ->where('is_active', true)
            ->count();
    }

    /**
     * Get followers count (calculated).
     */
    public function getFollowersCountAttribute(): int
    {
        return $this->followers()->count();
    }
}
