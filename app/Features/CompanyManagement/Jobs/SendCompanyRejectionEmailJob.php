<?php

namespace App\Features\CompanyManagement\Jobs;

use App\Features\CompanyManagement\Mail\CompanyRejectionMail;
use App\Features\CompanyManagement\Models\CompanyRequest;
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
     */
    public function __construct(
        public CompanyRequest $request,
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
        // Enviar email de rechazo
        Mail::to($this->request->admin_email)->send(
            new CompanyRejectionMail(
                $this->request,
                $this->reason
            )
        );
    }

    /**
     * Manejar fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        // Log del error
        Log::error('Failed to send company rejection email', [
            'request_code' => $this->request->request_code,
            'company_name' => $this->request->company_name,
            'admin_email' => $this->request->admin_email,
            'reason' => $this->reason,
            'error' => $exception->getMessage(),
        ]);
    }
}
