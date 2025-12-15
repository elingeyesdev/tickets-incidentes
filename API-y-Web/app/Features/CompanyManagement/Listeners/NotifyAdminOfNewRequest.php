<?php

namespace App\Features\CompanyManagement\Listeners;

use App\Features\CompanyManagement\Events\CompanyRequestSubmitted;
use Illuminate\Support\Facades\Log;

class NotifyAdminOfNewRequest
{
    /**
     * Handle the event.
     */
    public function handle(CompanyRequestSubmitted $event): void
    {
        // Obtener datos de la empresa pendiente y sus detalles de onboarding
        $company = $event->company;
        $onboardingDetails = $company->onboardingDetails;

        // TODO: Send notification to PLATFORM_ADMIN users
        // Por ahora, solo registrarlo
        Log::info('New company request submitted', [
            'company_id' => $company->id,
            'request_code' => $onboardingDetails?->request_code,
            'company_name' => $company->name,
            'submitter_email' => $onboardingDetails?->submitter_email ?? $company->support_email,
        ]);

        // Futuro: Despachar job de notificación
        // NotifyPlatformAdminsJob::dispatch($company);
    }
}
