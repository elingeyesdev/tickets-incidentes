<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Jobs;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $announcementId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Announcement $announcement
    ) {
        $this->announcementId = $announcement->id;
    }

    /**
     * Execute the job.
     *
     * Publishes the announcement if it's still in SCHEDULED status.
     * This provides a safety check in case the announcement was manually
     * published, unpublished, or deleted before the scheduled time.
     */
    public function handle(): void
    {
        // Only publish if still in SCHEDULED status
        if ($this->announcement->status === PublicationStatus::SCHEDULED) {
            $this->announcement->update([
                'status' => PublicationStatus::PUBLISHED,
                'published_at' => now(),
            ]);
        }
    }
}
