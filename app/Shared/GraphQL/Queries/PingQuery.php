<?php

namespace App\Shared\GraphQL\Queries;

class PingQuery
{
    public function __invoke(): string
    {
        return 'pong :)';
    }
}
