<?php

namespace App\Features\CompanyManagement\Events;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyRequestSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CompanyRequest $request
    ) {}
}
