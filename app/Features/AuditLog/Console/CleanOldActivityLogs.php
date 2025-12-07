<?php

declare(strict_types=1);

namespace App\Features\AuditLog\Console;

use App\Features\AuditLog\Services\ActivityLogService;
use Illuminate\Console\Command;

/**
 * CleanOldActivityLogs
 *
 * Comando para limpiar logs antiguos según política de retención.
 * Debe ejecutarse diariamente via scheduler.
 */
class CleanOldActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'activity-log:clean {--days=90 : Number of days to retain logs}';

    /**
     * The console command description.
     */
    protected $description = 'Clean old activity logs based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(ActivityLogService $activityLogService): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning activity logs older than {$days} days...");

        $deleted = $activityLogService->cleanOldRecords($days);

        $this->info("Deleted {$deleted} old activity log entries.");

        return Command::SUCCESS;
    }
}
