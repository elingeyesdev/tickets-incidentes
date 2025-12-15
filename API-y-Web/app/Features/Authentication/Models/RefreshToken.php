<?php

namespace App\Features\Authentication\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RefreshToken Model
 *
 * Modelo para refresh tokens JWT.
 * Los refresh tokens permiten renovar access tokens sin requerir re-login.
 *
 * Tabla: auth.refresh_tokens
 *
 * @property string $id
 * @property string $user_id
 * @property string $token_hash
 * @property string|null $device_name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $location
 * @property \DateTime $expires_at
 * @property \DateTime|null $last_used_at
 * @property bool $is_revoked
 * @property \DateTime|null $revoked_at
 * @property string|null $revoke_reason
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read User $user
 */
class RefreshToken extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'auth.refresh_tokens';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'user_id',
        'token_hash',
        'device_name',
        'ip_address',
        'user_agent',
        'location',
        'expires_at',
        'last_used_at',
        'is_revoked',
        'revoked_at',
        'revoke_reason',
    ];

    /**
     * Campos ocultos (no exponer en JSON)
     */
    protected $hidden = [
        'token_hash', // NUNCA exponer el hash
    ];

    /**
     * Casting de tipos
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_revoked' => 'boolean',
        'location' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con User (dueño del token)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    // ==================== MÉTODOS DE VALIDACIÓN ====================

    /**
     * Verificar si el token es válido
     * (no expirado, no revocado)
     */
    public function isValid(): bool
    {
        return !$this->isExpired()
            && !$this->isRevoked()
            && $this->user->isActive();
    }

    /**
     * Verificar si el token está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verificar si el token está revocado
     */
    public function isRevoked(): bool
    {
        return $this->is_revoked;
    }

    /**
     * Verificar si el token está activo
     */
    public function isActive(): bool
    {
        return $this->isValid();
    }

    // ==================== MÉTODOS DE ACCIÓN ====================

    /**
     * Revocar el token con razón
     *
     * @param string|null $reason Razón: 'manual_logout', 'security_breach', 'expired'
     */
    public function revoke(?string $reason = null): void
    {
        $this->update([
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoke_reason' => $reason ?? 'manual_logout',
        ]);
    }

    /**
     * Actualizar timestamp de último uso
     */
    public function updateLastUsed(): void
    {
        $this->update([
            'last_used_at' => now(),
        ]);
    }

    /**
     * Extender expiración del token
     *
     * @param int $days Días adicionales
     */
    public function extend(int $days = 30): void
    {
        $this->update([
            'expires_at' => now()->addDays($days),
        ]);
    }

    // ==================== MÉTODOS HELPER ====================

    /**
     * Obtener información del dispositivo formateada
     */
    public function getDeviceInfo(): array
    {
        return [
            'name' => $this->device_name ?? 'Dispositivo desconocido',
            'ip' => $this->ip_address,
            'user_agent' => $this->user_agent,
        ];
    }

    /**
     * Verificar si es el dispositivo actual
     *
     * @param string $currentTokenHash Hash del token actual
     */
    public function isCurrent(string $currentTokenHash): bool
    {
        return $this->token_hash === $currentTokenHash;
    }

    /**
     * Obtener días hasta expiración
     */
    public function getDaysUntilExpiration(): int
    {
        return max(0, now()->diffInDays($this->expires_at, false));
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Solo tokens activos (válidos)
     */
    public function scopeActive($query)
    {
        return $query->where('is_revoked', false)
                     ->where('expires_at', '>', now());
    }

    /**
     * Scope: Solo tokens expirados
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: Solo tokens revocados
     */
    public function scopeRevoked($query)
    {
        return $query->where('is_revoked', true);
    }

    /**
     * Scope: Tokens de un usuario específico
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Tokens por dispositivo
     */
    public function scopeByDevice($query, string $deviceName)
    {
        return $query->where('device_name', 'LIKE', "%{$deviceName}%");
    }

    /**
     * Scope: Tokens usados recientemente
     */
    public function scopeRecentlyUsed($query, int $hours = 24)
    {
        return $query->where('last_used_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: Ordenar por uso reciente
     */
    public function scopeOrderByLastUsed($query, string $direction = 'desc')
    {
        return $query->orderBy('last_used_at', $direction);
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Limpiar tokens expirados (garbage collection)
     *
     * @return int Número de tokens eliminados
     */
    public static function cleanExpired(): int
    {
        return static::expired()->delete();
    }

    /**
     * Revocar todos los tokens de un usuario
     *
     * @param string $userId
     * @param string|null $reason
     * @return int Número de tokens revocados
     */
    public static function revokeAllForUser(string $userId, ?string $reason = null): int
    {
        return static::forUser($userId)
            ->active()
            ->update([
                'is_revoked' => true,
                'revoked_at' => now(),
                'revoke_reason' => $reason ?? 'manual_logout',
            ]);
    }
}