<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyFollowInfoResource
 *
 * Propósito: Información de seguimiento de empresas para el usuario autenticado
 * Modelo base: CompanyFollower
 * Campos: id, company (nested CompanyMinimalResource), followed_at, my_tickets_count, last_ticket_created_at, has_unread_announcements
 * Eager loading: Requiere 'company' relation
 */
class CompanyFollowInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company' => new CompanyMinimalResource($this->whenLoaded('company')),
            'followedAt' => $this->followed_at?->toIso8601String(),
            'myTicketsCount' => $this->my_tickets_count ?? 0,
            'lastTicketCreatedAt' => $this->last_ticket_created_at ?? null,
            'hasUnreadAnnouncements' => $this->has_unread_announcements ?? false,
        ];
    }
}
