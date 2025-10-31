<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyRejectionResource
 *
 * Propósito: Response de rechazo de solicitud de empresa
 * Entrada: Array con datos de rechazo (no un modelo Eloquent)
 * Campos: success, message, reason, notification_sent_to, request_code
 * Uso: Transformar el resultado del Service después de rechazar una solicitud
 */
class CompanyRejectionResource extends JsonResource
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
            'data' => [
                'success' => $this->resource['success'] ?? true,
                'message' => $this->resource['message'] ?? 'Solicitud rechazada exitosamente',
                'reason' => $this->resource['reason'] ?? null,
                'notification_sent_to' => $this->resource['notification_sent_to'] ?? null,
                'request_code' => $this->resource['request_code'] ?? null,
            ],
        ];
    }
}
