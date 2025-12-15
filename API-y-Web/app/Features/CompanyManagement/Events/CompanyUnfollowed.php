<?php

namespace App\Features\CompanyManagement\Events;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyUnfollowed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public Company $company
    ) {}
}
