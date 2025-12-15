<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Jobs;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * AutoCloseResolvedTicketsJob
 *
 * Automatically closes tickets that have been in 'resolved' status
 * for more than 7 days.
 *
 * Business Rules:
 * - Tickets with status='resolved' and resolved_at > 7 days ago are closed
 * - Tickets with status='resolved' and resolved_at <= 7 days ago remain unchanged
 * - Logs the count of closed tickets for monitoring
 *
 * Production Behavior:
 * - Job runs at scheduled time (e.g., daily at 2:00 AM)
 * - Closes all eligible tickets at that exact moment
 * - All closed tickets have identical closed_at timestamp
 *
 * Testing:
 * - Accepts optional $now parameter for deterministic testing
 * - Allows test to control timestamp without affecting real time
 */
class AutoCloseResolvedTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @param Carbon|null $now Optional timestamp for testing purposes.
     *                         If null, uses current time (production behavior).
     */
    public function __construct(
        private ?Carbon $now = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Use injected timestamp (testing) or current time (production)
        // This ensures all tickets closed in this job run have the same timestamp
        $now = $this->now ?? Carbon::now();
        $sevenDaysAgo = $now->clone()->subDays(7);

        // Find all tickets that need to be closed
        $tickets = Ticket::where('status', TicketStatus::RESOLVED->value)
            ->where('resolved_at', '<=', $sevenDaysAgo)
            ->whereNull('closed_at')
            ->get();

        // Update each ticket with the same captured timestamp
        foreach ($tickets as $ticket) {
            $ticket->status = TicketStatus::CLOSED;
            $ticket->closed_at = $now;
            $ticket->save();
        }

        // Log the result
        Log::info('Auto-closed resolved tickets', ['count' => $tickets->count()]);
    }
}