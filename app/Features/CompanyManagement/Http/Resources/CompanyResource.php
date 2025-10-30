<?php

namespace App\Features\CompanyManagement\Http\Resources;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyResource
 *
 * Contexto: MANAGEMENT
 * Propósito: Administración completa de empresas
 * Campos: TODOS los campos del modelo + campos calculados + admin como campos planos
 * Campos calculados: active_agents_count, total_users_count, total_tickets_count, open_tickets_count, is_followed_by_me
 * Admin: Desestructurado en campos planos (admin_id, admin_name, admin_email, admin_avatar)
 * Eager loading: Requiere admin.profile para evitar N+1
 */
class CompanyResource extends JsonResource
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
            // Campos básicos
            'id' => $this->id,
            'companyCode' => $this->company_code,
            'name' => $this->name,
            'legalName' => $this->legal_name ?? null,

            // Contacto y comunicación
            'supportEmail' => $this->support_email ?? null,
            'phone' => $this->phone ?? null,
            'website' => $this->website ?? null,

            // Dirección
            'contactAddress' => $this->contact_address ?? null,
            'contactCity' => $this->contact_city ?? null,
            'contactState' => $this->contact_state ?? null,
            'contactCountry' => $this->contact_country ?? null,
            'contactPostalCode' => $this->contact_postal_code ?? null,

            // Información legal
            'taxId' => $this->tax_id ?? null,
            'legalRepresentative' => $this->legal_representative ?? null,

            // Configuración
            'businessHours' => $this->business_hours ?? null,
            'timezone' => $this->timezone ?? 'UTC',

            // Branding
            'logoUrl' => $this->logo_url ?? null,
            'faviconUrl' => $this->favicon_url ?? null,
            'primaryColor' => $this->primary_color ?? null,
            'secondaryColor' => $this->secondary_color ?? null,

            // Settings y estado
            'settings' => $this->settings ?? null,
            'status' => $this->status ? strtoupper($this->status) : null,

            // Admin como campos planos (previene loops infinitos)
            'adminId' => $this->admin_user_id,
            'adminName' => $this->admin_name ?? 'Unknown',
            'adminEmail' => $this->admin_email ?? 'unknown@example.com',
            'adminAvatar' => $this->when(
                $this->relationLoaded('admin'),
                fn() => $this->admin?->profile?->avatar_url ?? null
            ),

            // Estadísticas (campos calculados)
            'activeAgentsCount' => $this->active_agents_count ?? 0,
            'totalUsersCount' => $this->total_users_count ?? 0,
            'totalTicketsCount' => $this->total_tickets_count ?? 0,
            'openTicketsCount' => $this->open_tickets_count ?? 0,
            'followersCount' => $this->followers_count ?? 0,

            // Campo contextual (solo si usuario autenticado)
            'isFollowedByMe' => $this->is_followed_by_me ?? false,

            // Relaciones opcionales (usar whenLoaded)
            'createdFromRequestId' => $this->created_from_request_id ?? null,

            // Timestamps
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
