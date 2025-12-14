<?php

namespace App\Features\CompanyManagement\Jobs;

use App\Features\CompanyManagement\Mail\CompanyApprovalMailForExistingUser;
use App\Features\CompanyManagement\Mail\CompanyApprovalMailForNewUser;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Company Approval Email Job
 *
 * Job asíncrono para enviar email de aprobación de empresa.
 * Envía diferentes emails según si el usuario es nuevo o existente.
 * Se ejecuta en la cola 'emails'.
 */
class SendCompanyApprovalEmailJob implements ShouldQueue
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
     * NOTA: Ahora recibe Company dos veces por compatibilidad con la arquitectura normalizada.
     * El primer parámetro antes era CompanyRequest.
     */
    public function __construct(
        public Company $company,           // Antes era CompanyRequest $request
        public Company $approvedCompany,   // La misma empresa (para compatibilidad)
        public User $adminUser,
        public ?string $temporaryPassword = null
    ) {
        // Asignar a cola específica
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Determinar qué email enviar basado en si hay password temporal
        if ($this->temporaryPassword) {
            // Nuevo usuario - enviar email con password temporal
            Mail::to($this->adminUser->email)->send(
                new CompanyApprovalMailForNewUser(
                    $this->company,
                    $this->adminUser,
                    $this->temporaryPassword
                )
            );
        } else {
            // Usuario existente - enviar email sin password
            Mail::to($this->adminUser->email)->send(
                new CompanyApprovalMailForExistingUser(
                    $this->company,
                    $this->adminUser
                )
            );
        }
    }

    /**
     * Manejar fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        // Obtener request_code desde onboarding details
        $requestCode = $this->company->onboardingDetails?->request_code ?? 'N/A';

        // Log del error
        Log::error('Failed to send company approval email', [
            'request_code' => $requestCode,
            'company_code' => $this->company->company_code,
            'admin_email' => $this->adminUser->email,
            'has_temp_password' => !is_null($this->temporaryPassword),
            'error' => $exception->getMessage(),
        ]);
    }
}
