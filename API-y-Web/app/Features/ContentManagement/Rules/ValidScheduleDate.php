<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class ValidScheduleDate implements Rule
{
    private string $failureReason = 'unknown';

    public function passes($attribute, $value): bool
    {
        try {
            $scheduledDate = Carbon::parse($value);
            $now = Carbon::now();
            $minDate = $now->copy()->addMinutes(5)->startOfSecond();
            $maxDate = $now->copy()->addDays(365)->endOfSecond();

            // Must be at least 5 minutes in the future (inclusive)
            if ($scheduledDate->lt($minDate)) {
                $this->failureReason = 'too_soon';
                return false;
            }

            // Must not be more than 1 year (365 days) in the future (inclusive)
            if ($scheduledDate->gt($maxDate)) {
                $this->failureReason = 'too_late';
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->failureReason = 'invalid_date';
            return false;
        }
    }

    public function message(): string
    {
        return match($this->failureReason) {
            'too_soon' => 'The scheduled for field must be at least 5 minutes in the future.',
            'too_late' => 'The scheduled for field must not be more than 1 year in the future.',
            'invalid_date' => 'The scheduled for field must be a valid date.',
            default => 'The scheduled date must be between 5 minutes and 1 year in the future.',
        };
    }
}
