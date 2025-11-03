<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Services;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Jobs\PublishAnnouncementJob;
use App\Features\ContentManagement\Models\Announcement;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service for managing announcement lifecycle
 *
 * Handles creation, publishing, scheduling, archiving, and deletion
 * of company announcements with proper status validation.
 */
class AnnouncementService
{
    /**
     * Create a new announcement
     *
     * @param array $data Announcement data
     * @return Announcement The created announcement
     */
    public function create(array $data): Announcement
    {
        // Set default status to DRAFT if not specified
        if (!isset($data['status'])) {
            $data['status'] = PublicationStatus::DRAFT;
        }

        // Handle action if specified
        if (isset($data['action'])) {
            $action = $data['action'];

            if ($action === 'publish') {
                $data['status'] = PublicationStatus::PUBLISHED;
                $data['published_at'] = now();
            } elseif ($action === 'schedule') {
                $data['status'] = PublicationStatus::SCHEDULED;
                // scheduled_for should be in metadata
            }

            unset($data['action']);
        }

        $announcement = Announcement::create($data);

        // If scheduled, enqueue job
        if ($announcement->status === PublicationStatus::SCHEDULED
            && isset($announcement->metadata['scheduled_for'])) {
            $scheduledFor = Carbon::parse($announcement->metadata['scheduled_for']);
            PublishAnnouncementJob::dispatch($announcement)->delay($scheduledFor);
        }

        return $announcement->fresh();
    }

    /**
     * Update an existing announcement
     *
     * Only DRAFT or SCHEDULED announcements can be updated.
     *
     * @param Announcement $announcement The announcement to update
     * @param array $data Update data
     * @return Announcement The updated announcement
     * @throws RuntimeException If announcement is published or archived
     */
    public function update(Announcement $announcement, array $data): Announcement
    {
        // Only allow updates for DRAFT or SCHEDULED
        if (!in_array($announcement->status, [PublicationStatus::DRAFT, PublicationStatus::SCHEDULED])) {
            throw new RuntimeException('Cannot edit published announcement');
        }

        $announcement->update($data);

        return $announcement->fresh();
    }

    /**
     * Publish an announcement immediately
     *
     * @param Announcement $announcement The announcement to publish
     * @return Announcement The published announcement
     */
    public function publish(Announcement $announcement): Announcement
    {
        $announcement->update([
            'status' => PublicationStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        return $announcement->fresh();
    }

    /**
     * Schedule an announcement for future publication
     *
     * @param Announcement $announcement The announcement to schedule
     * @param Carbon $scheduledFor When to publish (must be in the future)
     * @return Announcement The scheduled announcement
     * @throws InvalidArgumentException If scheduled date is in the past
     */
    public function schedule(Announcement $announcement, Carbon $scheduledFor): Announcement
    {
        // Validate future date
        if ($scheduledFor->isPast()) {
            throw new InvalidArgumentException('Scheduled date must be in the future');
        }

        $announcement->update([
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => array_merge($announcement->metadata ?? [], [
                'scheduled_for' => $scheduledFor->toIso8601String(),
            ]),
        ]);

        // Enqueue job
        PublishAnnouncementJob::dispatch($announcement)->delay($scheduledFor);

        return $announcement->fresh();
    }

    /**
     * Archive a published announcement
     *
     * Only PUBLISHED announcements can be archived.
     *
     * @param Announcement $announcement The announcement to archive
     * @return Announcement The archived announcement
     * @throws RuntimeException If announcement is not published
     */
    public function archive(Announcement $announcement): Announcement
    {
        // Only allow archiving PUBLISHED announcements
        if ($announcement->status !== PublicationStatus::PUBLISHED) {
            throw new RuntimeException('Only published announcements can be archived');
        }

        $announcement->update([
            'status' => PublicationStatus::ARCHIVED,
        ]);

        return $announcement->fresh();
    }

    /**
     * Restore an archived announcement to draft status
     *
     * Only ARCHIVED announcements can be restored.
     *
     * @param Announcement $announcement The announcement to restore
     * @return Announcement The restored announcement
     * @throws RuntimeException If announcement is not archived
     */
    public function restore(Announcement $announcement): Announcement
    {
        // Only allow restoring ARCHIVED announcements
        if ($announcement->status !== PublicationStatus::ARCHIVED) {
            throw new RuntimeException('Only archived announcements can be restored');
        }

        $announcement->update([
            'status' => PublicationStatus::DRAFT,
            'published_at' => null,
        ]);

        return $announcement->fresh();
    }

    /**
     * Delete an announcement
     *
     * Only DRAFT or ARCHIVED announcements can be deleted.
     *
     * @param Announcement $announcement The announcement to delete
     * @return bool True if deletion was successful
     * @throws RuntimeException If announcement is published or scheduled
     */
    public function delete(Announcement $announcement): bool
    {
        // Only allow deleting DRAFT or ARCHIVED
        if (!in_array($announcement->status, [PublicationStatus::DRAFT, PublicationStatus::ARCHIVED])) {
            throw new RuntimeException('Cannot delete published announcement');
        }

        return $announcement->delete();
    }
}
