<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Features\UserManagement\Http\Resources\UserMinimalResource;

/**
 * CompanyRequestResource
 *
 * Propósito: Información completa de solicitudes de empresas
 * Modelo base: CompanyRequest
 * Campos: Todos los campos del modelo + reviewer (nested) + createdCompany (nested) + industry (nested)
 * Eager loading: Requiere 'reviewer', 'createdCompany', 'industry' relations
 * V8.0 Changes: Removed businessDescription/industryType, added companyDescription/requestMessage/industry relation
 */
class CompanyRequestResource extends JsonResource
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
            // Identificación
            'id' => $this->id,
            'requestCode' => $this->request_code,

            // Información de la empresa solicitada
            'companyName' => $this->company_name,
            'legalName' => $this->legal_name ?? null,
            'adminEmail' => $this->admin_email,
            'companyDescription' => $this->company_description ?? null,
            'requestMessage' => $this->request_message ?? null,
            'website' => $this->website ?? null,
            'industryId' => $this->industry_id ?? null,
            'industry' => [
                'id' => $this->industry?->id,
                'code' => $this->industry?->code,
                'name' => $this->industry?->name,
            ],
            'estimatedUsers' => $this->estimated_users ?? null,

            // Dirección
            'contactAddress' => $this->contact_address ?? null,
            'contactCity' => $this->contact_city ?? null,
            'contactCountry' => $this->contact_country ?? null,
            'contactPostalCode' => $this->contact_postal_code ?? null,

            // Información legal
            'taxId' => $this->tax_id ?? null,

            // Estado y revisión
            'status' => $this->status ? strtoupper($this->status) : null,
            'reviewedAt' => $this->reviewed_at?->toIso8601String(),
            'rejectionReason' => $this->rejection_reason ?? null,

            // Relaciones anidadas (usar whenLoaded para evitar N+1)
            'reviewer' => new UserMinimalResource($this->whenLoaded('reviewer')),
            'createdCompany' => new CompanyMinimalResource($this->whenLoaded('createdCompany')),

            // Timestamps
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
