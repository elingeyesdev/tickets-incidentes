<?php

declare(strict_types=1);

namespace App\Features\AuditLog\Console;

use App\Features\AuditLog\Services\ActivityLogService;
use Illuminate\Console\Command;

/**
 * FlushActivityLogBuffer
 *
 * Comando para hacer flush del buffer de Redis a la base de datos.
 * Debe ejecutarse periódicamente (cada minuto) via scheduler.
 */
class FlushActivityLogBuffer extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'activity-log:flush';

    /**
     * The console command description.
     */
    protected $description = 'Flush activity log buffer from Redis to database';

    /**
     * Execute the console command.
     */
    public function handle(ActivityLogService $activityLogService): int
    {
        $flushed = $activityLogService->flushBuffer();

        if ($flushed > 0) {
            $this->info("Flushed {$flushed} activity log entries to database.");
        }

        return Command::SUCCESS;
    }
}
