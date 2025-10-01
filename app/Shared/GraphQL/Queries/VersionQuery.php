<?php

namespace App\Shared\GraphQL\Queries;

use Illuminate\Foundation\Application;

class   VersionQuery
{
    public function __invoke(): array
    {
        return [
            'version' => 'v1.1.1',
            'laravel' => Application::VERSION,
            'environment' => config('app.env'),
            'timestamp' => now(),
            'lighthouse' => '^6.0',  // From composer.json
        ];
    }
}
