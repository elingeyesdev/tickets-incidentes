<?php

namespace App\Features\TicketManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * TicketInternalNote Model - Notas privadas entre agentes
 *
 * Tabla: ticketing.ticket_internal_notes
 *
 * Invisible para el cliente. Solo visible para agentes de la empresa.
 * Permite la colaboración privada entre agentes.
 *
 * @property string $id
 * @property string $ticket_id
 * @property string $agent_id
 * @property string $note_content
 * @property \DateTime $created_at
 * @property \DateTime|null $updated_at
 *
 * @property-read Ticket $ticket
 * @property-read User $agent
 */
class TicketInternalNote extends Model
{
    use HasFactory, HasUuid;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/TicketManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\TicketInternalNoteFactory::new();
    }

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'ticketing.ticket_internal_notes';

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
        'agent_id',
        'note_content',
    ];

    /**
     * Conversión de tipos (casts)
     */
    protected $casts = [
        'id' => 'string',
        'ticket_id' => 'string',
        'agent_id' => 'string',
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
     * Relación: Pertenece a un agente (autor)
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Scope: Filtrar por ticket
     */
    public function scopeByTicket(Builder $query, string $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope: Filtrar por agente
     */
    public function scopeByAgent(Builder $query, string $agentId): Builder
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Verifica si la nota puede ser editada (solo por el autor)
     */
    public function canBeEditedBy(string $agentId): bool
    {
        return $this->agent_id === $agentId;
    }
}
