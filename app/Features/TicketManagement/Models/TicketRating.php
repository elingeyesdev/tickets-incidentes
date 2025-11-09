<?php

namespace App\Features\TicketManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * TicketRating Model - Calificaciones de tickets
 *
 * Tabla: ticketing.ticket_ratings
 *
 * Guarda el snapshot histórico del agente al momento de la calificación.
 * Si el ticket se reasigna después, rated_agent_id NO cambia.
 *
 * @property string $id
 * @property string $ticket_id
 * @property string $customer_id
 * @property string $rated_agent_id
 * @property int $rating
 * @property string|null $comment
 * @property \DateTime $created_at
 *
 * @property-read Ticket $ticket
 * @property-read User $customer
 * @property-read User $ratedAgent
 */
class TicketRating extends Model
{
    use HasFactory, HasUuid;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/TicketManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\TicketRatingFactory::new();
    }

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'ticketing.ticket_ratings';

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
        'ticket_id',
        'customer_id',
        'rated_agent_id',
        'rating',
        'comment',
    ];

    /**
     * Conversión de tipos (casts)
     */
    protected $casts = [
        'id' => 'string',
        'ticket_id' => 'string',
        'customer_id' => 'string',
        'rated_agent_id' => 'string',
        'rating' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a un ticket (única)
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Relación: Pertenece al cliente que calificó
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Relación: Pertenece al agente calificado (snapshot histórico)
     */
    public function ratedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_agent_id');
    }

    /**
     * Scope: Filtrar por agente calificado
     */
    public function scopeByAgent(Builder $query, string $agentId): Builder
    {
        return $query->where('rated_agent_id', $agentId);
    }

    /**
     * Scope: Solo calificaciones positivas (4-5 estrellas)
     */
    public function scopePositive(Builder $query): Builder
    {
        return $query->where('rating', '>=', 4);
    }

    /**
     * Scope: Solo calificaciones negativas (1-2 estrellas)
     */
    public function scopeNegative(Builder $query): Builder
    {
        return $query->where('rating', '<=', 2);
    }

    /**
     * Scope: Solo calificaciones neutras (3 estrellas)
     */
    public function scopeNeutral(Builder $query): Builder
    {
        return $query->where('rating', 3);
    }

    /**
     * Verifica si la calificación puede ser actualizada (dentro de 24 horas)
     */
    public function canBeUpdated(): bool
    {
        $twentyFourHoursAgo = now()->subHours(24);
        return $this->created_at->isAfter($twentyFourHoursAgo);
    }

    /**
     * Verifica si es una calificación positiva
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Verifica si es una calificación negativa
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Obtiene las estrellas como string visual
     */
    public function getStarsAttribute(): string
    {
        return str_repeat('⭐', $this->rating);
    }
}
