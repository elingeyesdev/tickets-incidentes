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
        \Illuminate\Support\Facades\Log::debug('SendApprovalEmail listener: Handling CompanyRequestApproved event');

        // Despachar job para enviar email de aprobación
        SendCompanyApprovalEmailJob::dispatch(
            $event->request,
            $event->company,
            $event->adminUser,
            $event->temporaryPassword
        );

        \Illuminate\Support\Facades\Log::debug('SendApprovalEmail listener: Job dispatched');
    }
}
