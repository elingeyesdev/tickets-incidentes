<?php

namespace App\Shared\GraphQL\Scalars;

use Illuminate\Support\Carbon;
use Nuwave\Lighthouse\Schema\Types\Scalars\DateScalar;

class DateTimeScalar extends DateScalar
{
    protected function format(Carbon $carbon): string
    {
        return $carbon->toIso8601ZuluString();
    }

    protected function parse(string $value): Carbon
    {
        return Carbon::parse($value);
    }
}