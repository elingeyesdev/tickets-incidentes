<?php

namespace App\Features\CompanyManagement\Listeners;

use App\Features\CompanyManagement\Events\CompanyRequestSubmitted;
use App\Features\CompanyManagement\Jobs\SendCompanyRequestEmailJob;

class SendCompanyRequestConfirmationEmail
{
    /**
     * Handle the event.
     */
    public function handle(CompanyRequestSubmitted $event): void
    {
        // Despachar job para enviar email de confirmación al solicitante
        SendCompanyRequestEmailJob::dispatch($event->request);
    }
}
