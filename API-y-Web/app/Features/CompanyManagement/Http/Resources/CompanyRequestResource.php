<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Features\UserManagement\Http\Resources\UserMinimalResource;

/**
 * CompanyRequestResource
 *
 * Propósito: Información completa de solicitudes de empresas (ahora basado en Company)
 * Modelo base: Company (con status='pending'/'approved'/'rejected')
 * 
 * ARQUITECTURA NORMALIZADA:
 * - Los datos principales vienen de Company
 * - Los datos de proceso vienen de Company->onboardingDetails
 * 
 * Campos: Todos los campos del modelo + reviewer (nested desde onboardingDetails) + industry (nested)
 * Eager loading: Requiere 'onboardingDetails.reviewer', 'industry' relations
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
        // Obtener detalles de onboarding directamente si la relación está cargada
        $onboardingDetails = $this->relationLoaded('onboardingDetails') 
            ? $this->onboardingDetails 
            : null;

        return [
            // Identificación
            'id' => $this->id,
            'requestCode' => $onboardingDetails?->request_code ?? $this->company_code,

            // Información de la empresa
            'companyName' => $this->name,
            'legalName' => $this->legal_name ?? null,
            'adminEmail' => $onboardingDetails?->submitter_email ?? $this->support_email,
            'businessDescription' => $this->description ?? null,
            'requestMessage' => $onboardingDetails?->request_message ?? null,
            'website' => $this->website ?? null,
            'industryId' => $this->industry_id ?? null,
            'industry' => [
                'id' => $this->industry?->id,
                'code' => $this->industry?->code,
                'name' => $this->industry?->name,
            ],
            'estimatedUsers' => $onboardingDetails?->estimated_users ?? null,

            // Dirección
            'contactAddress' => $this->contact_address ?? null,
            'contactCity' => $this->contact_city ?? null,
            'contactCountry' => $this->contact_country ?? null,
            'contactPostalCode' => $this->contact_postal_code ?? null,

            // Información legal
            'taxId' => $this->tax_id ?? null,

            // Estado y revisión (datos de proceso desde onboardingDetails)
            'status' => $this->status ? strtoupper($this->status) : null,
            'reviewedAt' => $onboardingDetails?->reviewed_at?->toIso8601String(),
            'rejectionReason' => $onboardingDetails?->rejection_reason ?? null,

            // Reviewer anidado (viene de onboardingDetails->reviewer)
            'reviewer' => $this->when(
                $onboardingDetails !== null && $onboardingDetails->reviewer !== null,
                fn() => new UserMinimalResource($onboardingDetails->reviewer)
            ),

            // Para empresas aprobadas, la "createdCompany" es la misma (self-reference)
            // Mantenemos por compatibilidad con frontends existentes
            'createdCompany' => $this->when(
                $this->status === 'active',
                fn() => new CompanyMinimalResource($this->resource)
            ),

            // Timestamps
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
