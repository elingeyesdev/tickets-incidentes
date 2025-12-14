<?php

namespace App\Features\CompanyManagement\Jobs;

use App\Features\CompanyManagement\Mail\CompanyRejectionMail;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Company Rejection Email Job
 *
 * Job asíncrono para enviar email de rechazo de solicitud de empresa.
 * Se ejecuta en la cola 'emails'.
 */
class SendCompanyRejectionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos
     */
    public int $tries = 3;

    /**
     * Timeout en segundos
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     * 
     * NOTA: Ahora recibe Company (con status='rejected') en lugar de CompanyRequest.
     */
    public function __construct(
        public Company $company,
        public string $reason
    ) {
        // Asignar a cola específica
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Obtener email del solicitante desde onboarding details o support_email
        $recipientEmail = $this->company->onboardingDetails?->submitter_email
            ?? $this->company->support_email;

        // Enviar email de rechazo
        Mail::to($recipientEmail)->send(
            new CompanyRejectionMail(
                $this->company,
                $this->reason
            )
        );
    }

    /**
     * Manejar fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        $onboardingDetails = $this->company->onboardingDetails;

        // Log del error
        Log::error('Failed to send company rejection email', [
            'request_code' => $onboardingDetails?->request_code ?? 'N/A',
            'company_name' => $this->company->name,
            'submitter_email' => $onboardingDetails?->submitter_email ?? $this->company->support_email,
            'reason' => $this->reason,
            'error' => $exception->getMessage(),
        ]);
    }
}
