<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyMinimalResource
 *
 * Contexto: MINIMAL
 * Propósito: Selectores, referencias rápidas
 * Campos: 4 campos básicos (id, companyCode, name, logoUrl)
 * V8.0: Minimal context remains at 4 fields (no industry data)
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
        ];
    }
}
