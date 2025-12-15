<?php

namespace App\Features\TicketManagement\Models;

use App\Features\TicketManagement\Enums\AuthorType;
use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * TicketResponse Model - Conversación pública del ticket
 *
 * Tabla: ticketing.ticket_responses
 *
 * Visible para el cliente y los agentes. Cuando un agente responde
 * por primera vez, se activa el trigger de auto-assignment.
 *
 * @property string $id
 * @property string $ticket_id
 * @property string $author_id
 * @property string $content
 * @property AuthorType $author_type
 * @property \DateTime $created_at
 *
 * @property-read Ticket $ticket
 * @property-read User $author
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketAttachment> $attachments
 */
class TicketResponse extends Model
{
    use HasFactory, HasUuid;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/TicketManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\TicketResponseFactory::new();
    }

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'ticketing.ticket_responses';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'ticket_id',
        'author_id',
        'content',
        'author_type',
        'created_at',
    ];

    /**
     * Conversión de tipos (casts)
     */
    protected $casts = [
        'id' => 'string',
        'ticket_id' => 'string',
        'author_id' => 'string',
        'author_type' => AuthorType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a un ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Relación: Pertenece a un usuario (autor)
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Relación: Tiene muchos archivos adjuntos
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'response_id');
    }

    /**
     * Scope: Solo respuestas de agentes
     */
    public function scopeByAgents(Builder $query): Builder
    {
        return $query->where('author_type', AuthorType::AGENT);
    }

    /**
     * Scope: Solo respuestas de usuarios
     */
    public function scopeByUsers(Builder $query): Builder
    {
        return $query->where('author_type', AuthorType::USER);
    }

    /**
     * Scope: Filtrar por ticket
     */
    public function scopeByTicket(Builder $query, string $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Verifica si la respuesta puede ser editada (dentro de 30 minutos)
     */
    public function canBeEdited(): bool
    {
        $thirtyMinutesAgo = now()->subMinutes(30);
        return $this->created_at->isAfter($thirtyMinutesAgo)
            && $this->ticket->status !== \App\Features\TicketManagement\Enums\TicketStatus::CLOSED;
    }

    /**
     * Verifica si es una respuesta de agente
     */
    public function isFromAgent(): bool
    {
        return $this->author_type === AuthorType::AGENT;
    }

    /**
     * Verifica si es una respuesta de usuario
     */
    public function isFromUser(): bool
    {
        return $this->author_type === AuthorType::USER;
    }
}
