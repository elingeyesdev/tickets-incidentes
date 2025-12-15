<?php

namespace App\Features\CompanyManagement\Events;

use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanySuspended
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Company $company,
        public ?string $reason = null
    ) {}
}
