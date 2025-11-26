<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Models;

use App\Features\TicketManagement\Models\Ticket;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Area Model - Áreas/Departamentos de la empresa
 *
 * Las áreas son catálogos parametrizados por empresa que representan
 * la estructura organizacional (departamentos, áreas funcionales, etc.).
 * Pueden usarse opcionalmente en tickets y otros módulos.
 *
 * Tabla: business.areas
 *
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<Ticket> $tickets
 */
class Area extends Model
{
    use HasFactory, HasUuid;

    /**
     * Tabla en PostgreSQL (schema business)
     */
    protected $table = 'business.areas';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
    ];

    /**
     * Conversión de tipos (casts)
     */
    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a una empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relación: Tiene muchos tickets (relación cross-schema)
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'area_id');
    }

    /**
     * Scope: Áreas activas
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filtrar por empresa
     */
    public function scopeByCompany(Builder $query, string $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
