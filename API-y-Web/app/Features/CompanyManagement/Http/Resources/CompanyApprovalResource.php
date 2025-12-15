<?php

namespace App\Features\CompanyManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CompanyApprovalResource
 *
 * Propósito: Response de aprobación de solicitud de empresa
 * Entrada: Array con datos de aprobación (no un modelo Eloquent)
 * Campos: success, message, company (datos limitados + industry), new_user_created, notification_sent_to
 * Uso: Transformar el resultado del Service después de aprobar una solicitud
 * V8.0 Changes: Added description and industry fields to company data
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
        $company = $this->resource['company'] ?? [];
        $adminUser = $company['admin'] ?? null;
        $adminProfile = $adminUser?->profile ?? null;
        $industry = $company['industry'] ?? null;

        return [
            'data' => [
                'success' => $this->resource['success'] ?? true,
                'message' => $this->resource['message'] ?? 'Request approved successfully',
                'company' => [
                    'id' => $company['id'] ?? null,
                    'companyCode' => $company['company_code'] ?? null,
                    'name' => $company['name'] ?? null,
                    'legalName' => $company['legal_name'] ?? null,
                    'description' => $company['description'] ?? null,
                    'status' => isset($company['status']) ? strtoupper($company['status']) : 'ACTIVE',
                    'industryId' => $company['industry_id'] ?? null,
                    'industry' => $industry ? [
                        'id' => $industry->id,
                        'code' => $industry->code,
                        'name' => $industry->name,
                    ] : null,
                    'adminId' => $company['admin_user_id'] ?? null,
                    'adminName' => $adminProfile ? trim($adminProfile->first_name . ' ' . $adminProfile->last_name) : 'Unknown',
                    'adminEmail' => $adminUser?->email ?? null,
                    'createdAt' => isset($company['created_at']) ? $company['created_at']->toIso8601String() : null,
                ],
                'newUserCreated' => $this->resource['new_user_created'] ?? false,
                'notificationSentTo' => $this->resource['notification_sent_to'] ?? null,
            ],
        ];
    }
}
