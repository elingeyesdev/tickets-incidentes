<?php

namespace App\Features\CompanyManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFollower extends Model
{
    use HasFactory, HasUuid;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/CompanyManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\CompanyManagement\Database\Factories\CompanyFollowerFactory::new();
    }

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'business.user_company_followers';

    /**
     * Indica si el modelo debe ser timestamped.
     */
    public $timestamps = false;

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'followed_at',
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'id' => 'string',
        'followed_at' => 'datetime',
    ];

    /**
     * Obtener el usuario que sigue la empresa.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtener la empresa que está siendo seguida.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Inicializar el modelo.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-establecer timestamp followed_at en la creación
        static::creating(function ($model) {
            if (!$model->followed_at) {
                $model->followed_at = now();
            }
        });
    }

    /**
     * Obtener conteo de mis tickets para esta empresa (calculado).
     * TODO: Implementar cuando la funcionalidad de tickets esté lista
     */
    public function getMyTicketsCountAttribute(): int
    {
        return 0;
    }

    /**
     * Obtener último ticket creado para esta empresa (calculado).
     * TODO: Implementar cuando la funcionalidad de tickets esté lista
     */
    public function getLastTicketCreatedAtAttribute(): ?string
    {
        return null;
    }

    /**
     * Verificar si tiene anuncios no leídos (calculado).
     * TODO: Implementar cuando la funcionalidad de anuncios esté lista
     */
    public function getHasUnreadAnnouncementsAttribute(): bool
    {
        return false;
    }
}
