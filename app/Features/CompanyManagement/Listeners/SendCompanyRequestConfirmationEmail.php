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
        // Dispatch job to send confirmation email to requester
        SendCompanyRequestEmailJob::dispatch($event->request);
    }
}
