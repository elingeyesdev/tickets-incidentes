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
        // Dispatch job to send rejection email
        SendCompanyRejectionEmailJob::dispatch($event->request, $event->reason);
    }
}
