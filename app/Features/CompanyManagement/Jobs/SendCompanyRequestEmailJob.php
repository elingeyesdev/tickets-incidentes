<?php

namespace App\Features\CompanyManagement\Jobs;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCompanyRequestEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CompanyRequest $request
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: Send confirmation email to requester
        // Por ahora, solo registrarlo
        Log::info('Company request confirmation email would be sent', [
            'request_code' => $this->request->request_code,
            'company_name' => $this->request->company_name,
            'admin_email' => $this->request->admin_email,
        ]);

        // Implementación futura:
        // Mail::to($this->request->admin_email)
        //     ->send(new CompanyRequestConfirmationMail($this->request));
    }
}
