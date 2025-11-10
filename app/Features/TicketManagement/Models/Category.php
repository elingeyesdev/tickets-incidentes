<?php

namespace App\Features\TicketManagement\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Category Model - Categorías de tickets personalizadas por empresa
 *
 * Tabla: ticketing.categories
 *
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property \DateTime $created_at
 *
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<Ticket> $tickets
 */
class Category extends Model
{
    use HasFactory, HasUuid;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/TicketManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\CategoryFactory::new();
    }

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'ticketing.categories';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * No hay updated_at en esta tabla
     */
    const UPDATED_AT = null;

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
    ];

    /**
     * Relación: Pertenece a una empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relación: Tiene muchos tickets
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    /**
     * Scope: Solo categorías activas
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

    /**
     * Obtiene el conteo de tickets activos (no cerrados)
     * Tickets activos = OPEN, PENDING, IN_PROGRESS (excluye CLOSED)
     *
     * Nota: Este método requiere que la tabla de tickets exista.
     * Retorna 0 si hay algún error (para evitar fallos durante FASE 2).
     */
    public function getActiveTicketsCountAttribute(): int
    {
        try {
            return $this->tickets()
                ->whereNotIn('status', ['CLOSED'])
                ->count();
        } catch (\Exception $e) {
            // Durante FASE 2, la tabla de tickets no existe aún
            return 0;
        }
    }
}
