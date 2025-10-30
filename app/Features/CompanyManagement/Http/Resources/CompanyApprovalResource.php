<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyApprovalResource
 *
 * Propósito: Response de aprobación de solicitud de empresa
 * Entrada: Array con datos de aprobación (no un modelo Eloquent)
 * Campos: success, message, company (datos limitados), new_user_created, notification_sent_to
 * Uso: Transformar el resultado del Service después de aprobar una solicitud
 */
class CompanyApprovalResource extends JsonResource
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
            'message' => $this->resource['message'] ?? 'Solicitud aprobada exitosamente',
            'company' => [
                'id' => $this->resource['company']['id'] ?? null,
                'company_code' => $this->resource['company']['company_code'] ?? null,
                'name' => $this->resource['company']['name'] ?? null,
                'admin_email' => $this->resource['company']['admin_email'] ?? null,
                'status' => isset($this->resource['company']['status']) ? strtoupper($this->resource['company']['status']) : 'ACTIVE',
            ],
            'new_user_created' => $this->resource['new_user_created'] ?? false,
            'notification_sent_to' => $this->resource['notification_sent_to'] ?? null,
        ];
    }
}
