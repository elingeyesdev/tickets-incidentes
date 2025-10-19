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
        // TODO: Send notification to PLATFORM_ADMIN users
        // Por ahora, solo registrarlo
        Log::info('New company request submitted', [
            'request_id' => $event->request->id,
            'request_code' => $event->request->request_code,
            'company_name' => $event->request->company_name,
            'admin_email' => $event->request->admin_email,
        ]);

        // Futuro: Despachar job de notificación
        // NotifyPlatformAdminsJob::dispatch($event->request);
    }
}
