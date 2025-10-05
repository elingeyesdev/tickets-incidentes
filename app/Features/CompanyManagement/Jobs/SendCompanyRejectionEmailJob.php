<?php

namespace App\Features\CompanyManagement\Jobs;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCompanyRejectionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CompanyRequest $request,
        public string $reason
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: Send rejection email with reason
        // For now, just log it
        Log::info('Company request rejection email would be sent', [
            'request_code' => $this->request->request_code,
            'company_name' => $this->request->company_name,
            'admin_email' => $this->request->admin_email,
            'reason' => $this->reason,
        ]);

        // Future implementation:
        // Mail::to($this->request->admin_email)
        //     ->send(new CompanyRejectionMail($this->request, $this->reason));
    }
}
