<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AreaResource - Transforma Area model a formato JSON
 *
 * Incluye:
 * - id, company_id, name, description, is_active
 * - created_at en formato ISO8601
 * - active_tickets_count (si está cargado desde withCount)
 */
class AreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            // Incluir active_tickets_count si está presente (desde withCount)
            'active_tickets_count' => $this->when(
                isset($this->active_tickets_count),
                $this->active_tickets_count ?? 0
            ),
        ];
    }
}
