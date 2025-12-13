<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanyInvitation Model
 *
 * Stores invitations sent by Company Admins to users.
 * When accepted, the user is assigned the AGENT role for the company.
 *
 * @property string $id
 * @property string $company_id
 * @property string $user_id
 * @property string $role_code
 * @property string $status (PENDING, ACCEPTED, REJECTED, CANCELLED)
 * @property string $invited_by
 * @property string|null $message
 * @property \DateTime|null $responded_at
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read Company $company
 * @property-read User $user
 * @property-read User $inviter
 */
class CompanyInvitation extends Model
{
    use HasFactory, HasUuid;

    /**
     * Table name in PostgreSQL
     */
    protected $table = 'business.company_invitations';

    /**
     * Primary key is UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'role_code',
        'status',
        'invited_by',
        'message',
        'responded_at',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'user_id' => 'string',
        'invited_by' => 'string',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    // ==================== RELATIONSHIPS ====================

    /**
     * Relation: Company that sent the invitation
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relation: User being invited
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation: User who sent the invitation (Company Admin)
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Only pending invitations
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Only accepted invitations
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope: Only rejected invitations
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope: For a specific company
     */
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: For a specific user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if invitation is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if invitation was accepted
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if invitation was rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if invitation was cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Accept the invitation
     */
    public function accept(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);
    }

    /**
     * Reject the invitation
     */
    public function reject(): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'responded_at' => now(),
        ]);
    }

    /**
     * Cancel the invitation (by Company Admin)
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Get status label in Spanish
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_ACCEPTED => 'Aceptada',
            self::STATUS_REJECTED => 'Rechazada',
            self::STATUS_CANCELLED => 'Cancelada',
            default => $this->status,
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACCEPTED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }
}
