<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyFollowResource
 *
 * Propósito: Response de seguir/dejar de seguir una empresa
 * Entrada: Array con datos de la acción (no un modelo Eloquent)
 * Campos: success, message, company (CompanyMinimalResource), followed_at
 * Uso: Transformar el resultado del Service después de seguir/dejar de seguir una empresa
 */
class CompanyFollowResource extends JsonResource
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
            'success' => $this->resource['success'] ?? true,
            'message' => $this->resource['message'] ?? 'Operación completada exitosamente',
            'company' => isset($this->resource['company'])
                ? new CompanyMinimalResource($this->resource['company'])
                : null,
            'followedAt' => $this->resource['followed_at'] ?? null,
        ];
    }
}
