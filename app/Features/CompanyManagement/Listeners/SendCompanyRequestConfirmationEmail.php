<?php

namespace App\Features\CompanyManagement\Listeners;

use App\Features\CompanyManagement\Events\CompanyRequestSubmitted;
use App\Features\CompanyManagement\Jobs\SendCompanyRequestEmailJob;

/**
 * Listener que envía email de confirmación cuando se recibe una solicitud de empresa.
 * 
 * NOTA: Ahora el evento contiene un Company (con status='pending') en lugar de CompanyRequest.
 */
class SendCompanyRequestConfirmationEmail
{
    /**
     * Handle the event.
     */
    public function handle(CompanyRequestSubmitted $event): void
    {
        // Despachar job para enviar email de confirmación al solicitante
        // El evento ahora contiene Company en lugar de CompanyRequest
        SendCompanyRequestEmailJob::dispatch($event->company);
    }
}
