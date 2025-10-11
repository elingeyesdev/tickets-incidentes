<?php

namespace App\Features\CompanyManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyRequest extends Model
{
    use HasFactory, HasUuid;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/CompanyManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\CompanyManagement\Database\Factories\CompanyRequestFactory::new();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'business.company_requests';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'request_code',
        'company_name',
        'legal_name',
        'admin_email',
        'business_description',
        'website',
        'industry_type',
        'estimated_users',
        'contact_address',
        'contact_city',
        'contact_country',
        'contact_postal_code',
        'tax_id',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'created_company_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'id' => 'string',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin user who reviewed this request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the company that was created from this request (if approved).
     */
    public function createdCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'created_company_id');
    }

    /**
     * Scope: Pending requests only.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Approved requests only.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Rejected requests only.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Mark request as approved.
     */
    public function markAsApproved(User $reviewer, Company $createdCompany): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'created_company_id' => $createdCompany->id,
        ]);
    }

    /**
     * Mark request as rejected.
     */
    public function markAsRejected(User $reviewer, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
