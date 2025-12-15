<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyIndustryResource
 *
 * Propósito: Transform CompanyIndustry model for API responses
 * Modelo base: CompanyIndustry
 * Campos: id, code, name, description, timestamps, optional company counts
 * Use cases: Industry listings, industry details, nested in company responses
 * V8.0: New resource for industry catalog feature
 */
class CompanyIndustryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description ?? null,
            'createdAt' => $this->created_at?->toIso8601String(),

            // Optional: Include company counts if loaded via withCount()
            'activeCompaniesCount' => $this->when(
                isset($this->active_companies_count),
                $this->active_companies_count ?? 0
            ),
            'totalCompaniesCount' => $this->when(
                isset($this->total_companies_count),
                $this->total_companies_count ?? 0
            ),
        ];
    }
}
