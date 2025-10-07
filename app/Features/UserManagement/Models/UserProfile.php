<?php

namespace App\Features\UserManagement\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserProfile Model
 *
 * Perfiles de usuarios - Información personal y preferencias.
 * Relación 1:1 con User.
 * Tabla: auth.user_profiles
 *
 * IMPORTANTE:
 * - PK es user_id (NO hay campo 'id')
 * - display_name NO se almacena, se calcula con accessor
 *
 * @property string $user_id (PK)
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone_number
 * @property string|null $avatar_url
 * @property string $theme
 * @property string $language
 * @property string $timezone
 * @property bool $push_web_notifications
 * @property bool $notifications_tickets
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read User $user
 * @property-read string $display_name (computed)
 */
class UserProfile extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'auth.user_profiles';

    /**
     * Primary key es user_id (NO 'id')
     */
    protected $primaryKey = 'user_id';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'avatar_url',
        'theme',
        'language',
        'timezone',
        'push_web_notifications',
        'notifications_tickets',
    ];

    /**
     * Casting de tipos
     */
    protected $casts = [
        'push_web_notifications' => 'boolean',
        'notifications_tickets' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Appends - Atributos calculados que se incluyen en JSON
     */
    protected $appends = ['display_name'];

    /**
     * Relación inversa con User (1:1)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // ==================== ACCESSORS (COMPUTED ATTRIBUTES) ====================

    /**
     * Accessor: display_name calculado (NO almacenado)
     * Según Modelado V7.0 línea 84
     */
    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // ==================== MÉTODOS HELPER ====================

    /**
     * Obtener nombre completo (alias para display_name)
     */
    public function getFullName(): string
    {
        return $this->display_name;
    }

    /**
     * Obtener iniciales (para avatar)
     */
    public function getInitials(): string
    {
        $firstInitial = mb_substr($this->first_name, 0, 1);
        $lastInitial = mb_substr($this->last_name, 0, 1);
        return mb_strtoupper($firstInitial . $lastInitial);
    }

    /**
     * Verificar si el tema es oscuro
     */
    public function isDarkTheme(): bool
    {
        return $this->theme === 'dark';
    }

    /**
     * Verificar si tiene notificaciones habilitadas
     */
    public function hasNotificationsEnabled(): bool
    {
        return $this->push_web_notifications || $this->notifications_tickets;
    }

    /**
     * Actualizar avatar
     */
    public function updateAvatar(string $avatarUrl): void
    {
        $this->update(['avatar_url' => $avatarUrl]);
    }

    /**
     * Actualizar preferencias de UI
     */
    public function updateUIPreferences(string $theme, string $language, string $timezone): void
    {
        $this->update([
            'theme' => $theme,
            'language' => $language,
            'timezone' => $timezone,
        ]);
    }

    /**
     * Actualizar preferencias de notificaciones
     */
    public function updateNotificationPreferences(bool $pushWeb, bool $tickets): void
    {
        $this->update([
            'push_web_notifications' => $pushWeb,
            'notifications_tickets' => $tickets,
        ]);
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Buscar por nombre
     */
    public function scopeSearchByName($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'ILIKE', "%{$search}%")
              ->orWhere('last_name', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Scope: Por idioma
     */
    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Scope: Con notificaciones habilitadas
     */
    public function scopeWithNotifications($query)
    {
        return $query->where(function ($q) {
            $q->where('push_web_notifications', true)
              ->orWhere('notifications_tickets', true);
        });
    }
}