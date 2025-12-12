<?php

declare(strict_types=1);

namespace App\Features\ExternalIntegration\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para API Keys de servicios externos (Widget).
 * 
 * Cada API Key permite a un proyecto externo autenticarse con Helpdesk
 * para usar el widget embebible.
 * 
 * @property string $id
 * @property string $company_id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property string $type (production|development|testing)
 * @property \Carbon\Carbon|null $last_used_at
 * @property int $usage_count
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_active
 * @property string|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ServiceApiKey extends Model
{
    use HasUuids;

    protected $table = 'service_api_keys';

    protected $fillable = [
        'company_id',
        'key',
        'name',
        'description',
        'type',
        'last_used_at',
        'usage_count',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Campos que nunca deben ser expuestos en JSON.
     * La key completa solo se muestra una vez al crearla.
     */
    protected $hidden = ['key'];

    // ========================================================================
    // RELACIONES
    // ========================================================================

    /**
     * Empresa a la que pertenece esta API Key.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Usuario que creó esta API Key.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========================================================================
    // MÉTODOS DE VALIDACIÓN
    // ========================================================================

    /**
     * Verifica si la API Key es válida (activa y no expirada).
     */
    public function isValid(): bool
    {
        // Debe estar activa
        if (!$this->is_active) {
            return false;
        }

        // Si tiene expiración, verificar que no haya pasado
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verifica si la API Key está expirada.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    // ========================================================================
    // MÉTODOS DE USO
    // ========================================================================

    /**
     * Marca la API Key como usada (actualiza timestamp y contador).
     * Se recomienda llamar esto de forma asíncrona para no bloquear requests.
     */
    public function markAsUsed(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Desactiva la API Key (revocación).
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Reactiva una API Key revocada.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    // ========================================================================
    // MÉTODOS ESTÁTICOS
    // ========================================================================

    /**
     * Genera una nueva API Key con formato seguro.
     * 
     * Formato: sk_{prefix}_{48_caracteres_hex}
     * Ejemplo: sk_live_a1b2c3d4e5f6...
     * 
     * @param string $type 'production', 'development' o 'testing'
     * @return string
     */
    public static function generateKey(string $type = 'production'): string
    {
        $prefixes = [
            'production' => 'sk_live_',
            'development' => 'sk_dev_',
            'testing' => 'sk_test_',
        ];
        $prefix = $prefixes[$type] ?? 'sk_live_';
        $randomPart = bin2hex(random_bytes(24)); // 48 caracteres hex
        
        return $prefix . $randomPart;
    }

    /**
     * Busca una API Key por su valor.
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * Busca una API Key válida por su valor.
     */
    public static function findValidByKey(string $key): ?self
    {
        $apiKey = static::findByKey($key);
        
        if ($apiKey && $apiKey->isValid()) {
            return $apiKey;
        }
        
        return null;
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Scope para obtener solo keys activas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para obtener solo keys de producción.
     */
    public function scopeProduction($query)
    {
        return $query->where('type', 'production');
    }

    /**
     * Scope para obtener solo keys de desarrollo.
     */
    public function scopeDevelopment($query)
    {
        return $query->where('type', 'development');
    }

    /**
     * Scope para obtener solo keys de testing.
     */
    public function scopeTesting($query)
    {
        return $query->where('type', 'testing');
    }

    /**
     * Scope para obtener keys no expiradas.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Retorna una versión enmascarada de la key para mostrar en UI.
     * Ejemplo: sk_live_a1b2...f6g7
     */
    public function getMaskedKeyAttribute(): string
    {
        if (strlen($this->key) < 20) {
            return '****';
        }
        
        $prefix = substr($this->key, 0, 12); // sk_live_ + 4 chars
        $suffix = substr($this->key, -4);
        
        return $prefix . '...' . $suffix;
    }
}
