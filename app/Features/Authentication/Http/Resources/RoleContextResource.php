<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Role Context Resource
 *
 * Transforma un contexto de rol a su representación JSON.
 * Incluye información de rol y empresa.
 */
class RoleContextResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'roleId' => $this->role_id,
            'roleCode' => $this->role?->role_code,
            'roleName' => $this->role?->name,
            'companyId' => $this->company_id,
            'companyCode' => $this->company?->company_code,
        ];
    }
}
