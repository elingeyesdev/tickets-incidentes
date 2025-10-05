<?php

namespace App\Features\CompanyManagement\Events;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyRequestRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CompanyRequest $request,
        public string $reason
    ) {}
}
