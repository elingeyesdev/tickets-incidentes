<?php

namespace App\Features\CompanyManagement\Events;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se aprueba una solicitud de empresa.
 * 
 * NOTA: El primer parámetro ahora es la misma Company (antes era CompanyRequest).
 * Dado que ahora la solicitud ES la empresa con status='pending',
 * los datos están todos en Company.
 */
class CompanyRequestApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Company $company,           // La empresa aprobada (antes era $request)
        public Company $approvedCompany,   // Alias para compatibilidad (es la misma)
        public User $adminUser,
        public ?string $temporaryPassword = null
    ) {}
}
