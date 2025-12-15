<?php

namespace App\Features\CompanyManagement\Listeners;

use App\Features\CompanyManagement\Events\CompanyRequestApproved;
use Illuminate\Support\Facades\Log;

/**
 * CreateCompanyFromRequest Listener
 * 
 * NOTA: Con la arquitectura normalizada, la empresa YA EXISTE cuando se aprueba
 * (solo se cambia el status de 'pending' a 'active').
 * Este listener ahora sirve para procesamiento post-aprobación.
 */
class CreateCompanyFromRequest
{
    /**
     * Handle the event.
     *
     * NOTE: The actual company creation happens in CompanyRequestService::submit()
     * The approval only changes status from 'pending' to 'active'
     * This listener is for additional post-processing if needed.
     */
    public function handle(CompanyRequestApproved $event): void
    {
        // Obtener datos de onboarding para logging
        $onboardingDetails = $event->company->onboardingDetails;

        // Registrar la aprobación exitosa
        Log::info('Company approved from pending request', [
            'company_id' => $event->company->id,
            'company_code' => $event->company->company_code,
            'request_code' => $onboardingDetails?->request_code ?? 'N/A',
            'admin_user_id' => $event->adminUser->id,
            'status' => $event->company->status,
        ]);

        // Procesamiento posterior adicional se puede agregar aquí:
        // - Send welcome email
        // - Create default categories
        // - Set up initial configurations
        // - etc.
    }
}
