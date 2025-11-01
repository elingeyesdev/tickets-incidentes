<?php

namespace App\Features\CompanyManagement\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CompanyIndustry Model
 *
 * Represents a company industry classification in the system.
 * Industries are used to categorize companies by their business sector.
 *
 * @property string $id UUID primary key
 * @property string $code Unique industry code (e.g., 'technology', 'healthcare')
 * @property string $name Display name of the industry
 * @property string|null $description Detailed description of the industry
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 */
class CompanyIndustry extends Model
{
    use HasFactory, HasUuid;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/CompanyManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\CompanyManagement\Database\Factories\CompanyIndustryFactory::new();
    }

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'business.company_industries';

    /**
     * Indica que no hay columna updated_at en esta tabla.
     */
    public const UPDATED_AT = null;

    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'id' => 'string',
        'created_at' => 'datetime',
    ];

    /**
     * Obtener todas las empresas que pertenecen a esta industria.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'industry_id');
    }

    /**
     * Obtener conteo de empresas activas en esta industria.
     *
     * @return int
     */
    public function getActiveCompaniesCountAttribute(): int
    {
        return $this->companies()->where('status', 'active')->count();
    }

    /**
     * Obtener conteo total de empresas en esta industria.
     *
     * @return int
     */
    public function getTotalCompaniesCountAttribute(): int
    {
        return $this->companies()->count();
    }

    /**
     * Scope: Ordenar por nombre alfabéticamente.
     */
    public function scopeAlphabetical($query)
    {
        return $query->orderBy('name', 'asc');
    }

    /**
     * Scope: Buscar por código.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
