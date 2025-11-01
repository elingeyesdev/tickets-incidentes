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
     * La tabla asociada con el modelo.
     */
    protected $table = 'business.company_requests';

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'request_code',
        'company_name',
        'legal_name',
        'admin_email',
        'company_description',
        'request_message',
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
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'id' => 'string',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener el usuario admin que revisó esta solicitud.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Obtener la empresa que fue creada desde esta solicitud (si fue aprobada).
     */
    public function createdCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'created_company_id');
    }

    /**
     * Scope: Solo solicitudes pendientes.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Solo solicitudes aprobadas.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Solo solicitudes rechazadas.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Verificar si la solicitud está pendiente.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verificar si la solicitud está aprobada.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Verificar si la solicitud está rechazada.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get reviewed_at as reviewedAt (camelCase accessor)
     */
    public function getReviewedAtAttribute()
    {
        $value = $this->attributes['reviewed_at'] ?? null;

        if ($value && $this->hasCast('reviewed_at', ['datetime', 'immutable_datetime'])) {
            return $this->asDateTime($value);
        }

        return $value;
    }

    /**
     * Marcar solicitud como aprobada.
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
     * Marcar solicitud como rechazada.
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
