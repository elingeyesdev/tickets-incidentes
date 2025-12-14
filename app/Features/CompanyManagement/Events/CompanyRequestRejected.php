<?php

namespace App\Features\CompanyManagement\Events;

use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se rechaza una solicitud de empresa.
 * 
 * NOTA: Ahora recibe Company (con status='rejected') en lugar de CompanyRequest
 */
class CompanyRequestRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Company $company,
        public string $reason
    ) {}
}
