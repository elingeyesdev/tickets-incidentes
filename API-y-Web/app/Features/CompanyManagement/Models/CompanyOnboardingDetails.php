<?php

namespace App\Features\CompanyManagement\Models;

use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanyOnboardingDetails
 * 
 * Almacena la metadata del proceso de solicitud/onboarding de una empresa.
 * Relación 1:1 con Company.
 * 
 * Contiene datos que solo son relevantes durante el proceso de solicitud:
 * - request_code: Código único del trámite
 * - request_message: Mensaje original del solicitante
 * - estimated_users: Estimación inicial de usuarios
 * - submitter_email: Email del solicitante original
 * - reviewed_by/at: Datos de auditoría de la revisión
 * - rejection_reason: Motivo de rechazo (si aplica)
 */
class CompanyOnboardingDetails extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'business.company_onboarding_details';

    /**
     * La clave primaria de la tabla.
     */
    protected $primaryKey = 'company_id';

    /**
     * Indica que la clave primaria no es auto-incrementable.
     */
    public $incrementing = false;

    /**
     * El tipo de la clave primaria.
     */
    protected $keyType = 'string';

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'company_id',
        'request_code',
        'request_message',
        'estimated_users',
        'submitter_email',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'company_id' => 'string',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener la empresa asociada a estos detalles de onboarding.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Obtener el usuario que revisó esta solicitud.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Marcar como aprobado.
     */
    public function markAsReviewed(User $reviewer): void
    {
        $this->update([
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Marcar con razón de rechazo.
     */
    public function markAsRejectedByReviewer(User $reviewer, string $reason): void
    {
        $this->update([
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
