<?php

namespace App\Features\CompanyManagement\Listeners;

use App\Features\CompanyManagement\Events\CompanyRequestApproved;
use App\Features\CompanyManagement\Jobs\SendCompanyApprovalEmailJob;

class SendApprovalEmail
{
    /**
     * Handle the event.
     */
    public function handle(CompanyRequestApproved $event): void
    {
        // Dispatch job to send approval email
        SendCompanyApprovalEmailJob::dispatch(
            $event->request,
            $event->company,
            $event->adminUser
        );
    }
}
