<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyMinimalResource
 *
 * Contexto: MINIMAL
 * Propósito: Selectores, referencias rápidas, campos anidados
 * Campos: 5 campos básicos (id, company_code, name, logo_url, industry_code)
 * Eager loading: 'industry' opcional (for industryCode field)
 * V8.0 Changes: Added industryCode for filtering UI
 */
class CompanyMinimalResource extends JsonResource
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
            'companyCode' => $this->company_code,
            'name' => $this->name,
            'logoUrl' => $this->logo_url,
            'industryCode' => $this->industry?->code ?? null,
        ];
    }
}
