<?php

namespace App\Features\TicketManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * TicketAttachment Model - Archivos adjuntos del ticket
 *
 * Tabla: ticketing.ticket_attachments
 *
 * Los archivos pueden estar adjuntos directamente al ticket (response_id = NULL)
 * o a una respuesta específica (response_id = UUID).
 *
 * @property string $id
 * @property string $ticket_id
 * @property string|null $response_id
 * @property string $uploaded_by_user_id
 * @property string $file_name
 * @property string $file_path
 * @property string|null $file_type
 * @property int|null $file_size_bytes
 * @property \DateTime $created_at
 *
 * @property-read Ticket $ticket
 * @property-read TicketResponse|null $response
 * @property-read User $uploader
 */
class TicketAttachment extends Model
{
    use HasFactory, HasUuid;

    /**
     * Factory para el modelo
     * Feature-first: Factory está en app/Features/TicketManagement/Database/Factories
     */
    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\TicketAttachmentFactory::new();
    }

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'ticketing.ticket_attachments';

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
        'response_id',
        'uploaded_by_user_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size_bytes',
        'created_at',
    ];

    /**
     * Conversión de tipos (casts)
     */
    protected $casts = [
        'id' => 'string',
        'ticket_id' => 'string',
        'response_id' => 'string',
        'uploaded_by_user_id' => 'string',
        'file_size_bytes' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a un ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Relación: Pertenece a una respuesta (opcional)
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(TicketResponse::class, 'response_id');
    }

    /**
     * Relación: Pertenece al usuario que lo subió
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Scope: Filtrar por ticket
     */
    public function scopeByTicket(Builder $query, string $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope: Solo adjuntos del ticket (no de respuestas)
     */
    public function scopeTicketLevel(Builder $query): Builder
    {
        return $query->whereNull('response_id');
    }

    /**
     * Scope: Solo adjuntos de respuestas
     */
    public function scopeResponseLevel(Builder $query): Builder
    {
        return $query->whereNotNull('response_id');
    }

    /**
     * Verifica si el archivo puede ser eliminado (dentro de 30 minutos)
     */
    public function canBeDeleted(): bool
    {
        $thirtyMinutesAgo = now()->subMinutes(30);
        return $this->created_at->isAfter($thirtyMinutesAgo)
            && $this->ticket->status !== \App\Features\TicketManagement\Enums\TicketStatus::CLOSED;
    }

    /**
     * Verifica si está adjunto a una respuesta
     */
    public function isAttachedToResponse(): bool
    {
        return $this->response_id !== null;
    }

    /**
     * Accessor for file_url (returns file_path for backward compatibility)
     */
    public function getFileUrlAttribute(): string
    {
        return $this->file_path;
    }

    /**
     * Obtiene el tamaño del archivo en formato legible
     */
    public function getFormattedSizeAttribute(): string
    {
        if ($this->file_size_bytes === null) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size_bytes;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
