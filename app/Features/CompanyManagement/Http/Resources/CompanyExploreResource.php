<?php

namespace App\Features\CompanyManagement\Http\Resources;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyExploreResource
 *
 * Contexto: EXPLORE
 * Propósito: Explorar empresas públicas, cards de empresas
 * Campos: 11 campos (info básica + descripción + branding + contadores)
 * Campos calculados: followers_count, is_followed_by_me
 * Eager loading: NINGUNO requerido (contadores se calculan con atributos del modelo)
 */
class CompanyExploreResource extends JsonResource
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
            'description' => $this->business_description ?? null,
            'industry' => $this->industry_type ?? null,
            'city' => $this->contact_city ?? null,
            'country' => $this->contact_country ?? null,
            'primaryColor' => $this->primary_color ?? null,
            'status' => $this->status ? strtoupper($this->status) : null,
            'followersCount' => $this->followers_count ?? 0,
            'isFollowedByMe' => $this->is_followed_by_me ?? false,
        ];
    }
}
