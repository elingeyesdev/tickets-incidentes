<?php

namespace App\Features\CompanyManagement\Listeners;

use App\Features\CompanyManagement\Events\CompanyRequestApproved;
use Illuminate\Support\Facades\Log;

class CreateCompanyFromRequest
{
    /**
     * Handle the event.
     *
     * NOTE: The actual company creation happens in CompanyRequestService::approve()
     * This listener is for additional post-processing if needed.
     */
    public function handle(CompanyRequestApproved $event): void
    {
        // Registrar la creación exitosa
        Log::info('Company created from request', [
            'request_id' => $event->request->id,
            'request_code' => $event->request->request_code,
            'company_id' => $event->company->id,
            'company_code' => $event->company->company_code,
            'admin_user_id' => $event->adminUser->id,
        ]);

        // Procesamiento posterior adicional se puede agregar aquí:
        // - Send welcome email
        // - Create default categories
        // - Set up initial configurations
        // - etc.
    }
}
