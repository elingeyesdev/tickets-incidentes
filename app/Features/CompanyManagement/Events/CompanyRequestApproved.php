<?php

namespace App\Features\CompanyManagement\Events;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyRequestApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CompanyRequest $request,
        public Company $company,
        public User $adminUser
    ) {}
}
