<?php

namespace App\Features\CompanyManagement\Events;

use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se envía una nueva solicitud de empresa.
 * 
 * NOTA: Ahora recibe Company (con status='pending') en lugar de CompanyRequest
 */
class CompanyRequestSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Company $company
    ) {
    }

    /**
     * Alias para compatibilidad con código existente que espera 'request'.
     * @deprecated Use $company en su lugar
     */
    public function getRequestAttribute(): Company
    {
        return $this->company;
    }
}
