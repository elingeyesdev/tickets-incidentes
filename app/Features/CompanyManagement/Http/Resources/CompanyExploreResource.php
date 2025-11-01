<?php

namespace App\Features\CompanyManagement\Http\Resources;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * CompanyExploreResource
 *
 * Contexto: EXPLORE
 * Propósito: Explorar empresas públicas, cards de empresas
 * Campos: 12 campos (info básica + descripción truncada + industry + branding + contadores)
 * Campos calculados: followers_count, is_followed_by_me
 * Eager loading: 'industry' recomendado para evitar N+1
 * V8.0 Changes: Fixed field names (description, industry relation), added industryCode, truncated description to 120 chars
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
            'description' => Str::limit($this->description ?? '', 120),
            'industry' => $this->industry?->name ?? null,
            'industryCode' => $this->industry?->code ?? null,
            'city' => $this->contact_city ?? null,
            'country' => $this->contact_country ?? null,
            'primaryColor' => $this->primary_color ?? null,
            'status' => $this->status ? strtoupper($this->status) : null,
            'followersCount' => $this->followers_count ?? 0,
            'isFollowedByMe' => $this->is_followed_by_me ?? false,
        ];
    }
}
