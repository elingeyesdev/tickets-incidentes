<?php

namespace App\Features\TicketManagement\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Ticket Model - Centro del sistema de soporte
 *
 * Tabla: ticketing.tickets
 *
 * Ciclo de vida: open -> pending -> resolved -> closed
 *
 * @property string $id
 * @property string $ticket_code
 * @property string $created_by_user_id
 * @property string $company_id
 * @property string|null $category_id
 * @property string $title
 * @property string $description
 * @property TicketStatus $status
 * @property string|null $owner_agent_id
 * @property string $last_response_author_type
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime|null $first_response_at
 * @property \DateTime|null $resolved_at
 * @property \DateTime|null $closed_at
 *
 * @property-read User $creator
 * @property-read User|null $ownerAgent
 * @property-read Company $company
 * @property-read Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketResponse> $responses
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketInternalNote> $internalNotes
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketAttachment> $attachments
 * @property-read TicketRating|null $rating
 */
class Ticket extends Model
{
    use HasFactory, HasUuid;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/TicketManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\TicketFactory::new();
    }

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'ticketing.tickets';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Use ticket_code for route model binding instead of id
     * This allows API routes to accept /api/tickets/TKT-2025-00001 instead of UUID
     */
    public function getRouteKeyName(): string
    {
        return 'ticket_code';
    }

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'ticket_code',
        'created_by_user_id',
        'company_id',
        'category_id',
        'title',
        'description',
        'status',
        'owner_agent_id',
        'last_response_author_type',
        'first_response_at',
        'resolved_at',
        'closed_at',
    ];

    /**
     * Conversión de tipos (casts)
     */
    protected $casts = [
        'id' => 'string',
        'created_by_user_id' => 'string',
        'company_id' => 'string',
        'category_id' => 'string',
        'owner_agent_id' => 'string',
        'last_response_author_type' => 'string',
        'status' => TicketStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece al usuario que lo creó
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Relación: Pertenece a una empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relación: Pertenece a una categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Relación: Pertenece a un agente propietario (opcional)
     */
    public function ownerAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_agent_id');
    }

    /**
     * Relación: Tiene muchas respuestas
     */
    public function responses(): HasMany
    {
        return $this->hasMany(TicketResponse::class, 'ticket_id')->orderBy('created_at');
    }

    /**
     * Relación: Tiene muchas notas internas
     */
    public function internalNotes(): HasMany
    {
        return $this->hasMany(TicketInternalNote::class, 'ticket_id')->orderBy('created_at');
    }

    /**
     * Relación: Tiene muchos archivos adjuntos
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id')->orderBy('created_at');
    }

    /**
     * Relación: Tiene una calificación (opcional)
     */
    public function rating(): HasOne
    {
        return $this->hasOne(TicketRating::class, 'ticket_id');
    }

    /**
     * Scope: Tickets abiertos
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::OPEN);
    }

    /**
     * Scope: Tickets pendientes (con respuesta de agente)
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::PENDING);
    }

    /**
     * Scope: Tickets resueltos
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::RESOLVED);
    }

    /**
     * Scope: Tickets cerrados
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::CLOSED);
    }

    /**
     * Scope: Filtrar por empresa
     */
    public function scopeByCompany(Builder $query, string $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Filtrar por creador
     */
    public function scopeCreatedBy(Builder $query, string $userId): Builder
    {
        return $query->where('created_by_user_id', $userId);
    }

    /**
     * Scope: Filtrar por agente asignado
     */
    public function scopeOwnedBy(Builder $query, string $agentId): Builder
    {
        return $query->where('owner_agent_id', $agentId);
    }

    /**
     * Scope: Tickets activos (no cerrados)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING, TicketStatus::RESOLVED]);
    }

    /**
     * Accessor: Código formateado del ticket
     *
     * @return string Ejemplo: "TKT-2025-00001"
     */
    protected function formattedCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ticket_code,
        );
    }

    /**
     * Verifica si el ticket puede ser editado por el usuario creador
     */
    public function canBeEditedByCreator(): bool
    {
        return $this->status === TicketStatus::OPEN;
    }

    /**
     * Verifica si el ticket puede recibir respuestas
     */
    public function canReceiveResponses(): bool
    {
        return $this->status->canReceiveResponses();
    }

    /**
     * Verifica si el ticket puede ser calificado
     */
    public function canBeRated(): bool
    {
        return $this->status->canBeRated() && $this->rating === null;
    }

    /**
     * Verifica si el ticket puede ser reabierto
     */
    public function canBeReopened(): bool
    {
        return $this->status->canBeReopened();
    }

    /**
     * Verifica si el ticket puede ser eliminado
     */
    public function canBeDeleted(): bool
    {
        return $this->status === TicketStatus::CLOSED;
    }
}
