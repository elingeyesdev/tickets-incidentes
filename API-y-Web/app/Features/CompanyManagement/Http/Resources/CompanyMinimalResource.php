<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyMinimalResource
 *
 * Contexto: MINIMAL
 * Propósito: Selectores, referencias rápidas, cards de contenido
 * Campos: 5 campos básicos (id, companyCode, name, logoUrl, industryName)
 * V8.1: Added industryName for content cards display
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
            'industryName' => $this->industry?->name,
        ];
    }
}
