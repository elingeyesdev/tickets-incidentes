<?php

namespace App\Features\CompanyManagement\Listeners;

use App\Features\CompanyManagement\Events\CompanyRequestRejected;
use App\Features\CompanyManagement\Jobs\SendCompanyRejectionEmailJob;

class SendRejectionEmail
{
    /**
     * Handle the event.
     */
    public function handle(CompanyRequestRejected $event): void
    {
        // Despachar job para enviar email de rechazo
        SendCompanyRejectionEmailJob::dispatch($event->request, $event->reason);
    }
}
