<?php

namespace App\Features\CompanyManagement\Jobs;

use App\Features\CompanyManagement\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Company Request Confirmation Email Job
 * 
 * Job asíncrono para enviar email de confirmación cuando se envía una solicitud.
 * Se ejecuta en la cola 'emails'.
 */
class SendCompanyRequestEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     * 
     * NOTA: Ahora recibe Company (con status='pending') en lugar de CompanyRequest.
     */
    public function __construct(
        public Company $company
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $onboardingDetails = $this->company->onboardingDetails;

        // TODO: Send confirmation email to requester
        // Por ahora, solo registrarlo
        Log::info('Company request confirmation email would be sent', [
            'request_code' => $onboardingDetails?->request_code ?? 'N/A',
            'company_name' => $this->company->name,
            'submitter_email' => $onboardingDetails?->submitter_email ?? $this->company->support_email,
        ]);

        // Implementación futura:
        // $recipientEmail = $onboardingDetails?->submitter_email ?? $this->company->support_email;
        // Mail::to($recipientEmail)
        //     ->send(new CompanyRequestConfirmationMail($this->company));
    }
}
