<?php

namespace App\Features\CompanyManagement\Jobs;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCompanyApprovalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CompanyRequest $request,
        public Company $company,
        public User $adminUser
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: Send approval email with company credentials
        // Por ahora, solo registrarlo
        Log::info('Company request approval email would be sent', [
            'request_code' => $this->request->request_code,
            'company_code' => $this->company->company_code,
            'company_name' => $this->company->name,
            'admin_email' => $this->adminUser->email,
        ]);

        // Implementación futura:
        // Mail::to($this->adminUser->email)
        //     ->send(new CompanyApprovalMail($this->request, $this->company, $this->adminUser));
    }
}
