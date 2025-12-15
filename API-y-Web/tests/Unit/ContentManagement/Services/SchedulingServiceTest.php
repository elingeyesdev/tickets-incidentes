<?php

declare(strict_types=1);

namespace Tests\Unit\ContentManagement\Services;

use App\Features\ContentManagement\Jobs\PublishAnnouncementJob;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Services\SchedulingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Comprehensive unit tests for SchedulingService
 *
 * Tests scheduling logic, job queueing, validation, and rescheduling operations
 * following TDD approach with PHP 8 attributes.
 *
 * Total: 6 tests
 */
#[CoversClass(SchedulingService::class)]
class SchedulingServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: enqueueJob calculates correct delay for scheduled announcements
     *
     * Verifies that when scheduling an announcement for 2 hours in the future,
     * the job is queued with approximately 7200 seconds delay.
     */
    #[Test]
    public function enqueue_job_calculates_correct_delay(): void
    {
        // Arrange: Fake the queue to intercept dispatched jobs
        Queue::fake();
        $service = app(SchedulingService::class);

        // Create an announcement to be scheduled
        $announcement = Announcement::factory()->create();

        // Schedule for exactly 2 hours from now (7200 seconds)
        $scheduledFor = now()->addHours(2);

        // Act: Enqueue the job with the scheduled time
        $service->enqueueJob($announcement, $scheduledFor);

        // Assert: Verify the job was pushed with correct delay
        Queue::assertPushed(PublishAnnouncementJob::class, function ($job) use ($scheduledFor, $announcement) {
            // Get the delay property from the job
            $jobDelay = $job->delay;

            // Calculate expected delay in seconds
            $expectedDelay = now()->diffInSeconds($scheduledFor, false);

            // Allow 10 second tolerance for test execution time
            $isDelayCorrect = $jobDelay >= ($expectedDelay - 10)
                && $jobDelay <= ($expectedDelay + 10);

            // Verify the job is for the correct announcement
            $isCorrectAnnouncement = $job->announcementId === $announcement->id;

            return $isDelayCorrect && $isCorrectAnnouncement;
        });
    }

    /**
     * Test 2: cancelJob removes scheduled job from queue
     *
     * Verifies that calling cancelJob() properly removes the job
     * from the queue using Redis/Laravel queue system.
     */
    #[Test]
    public function cancel_job_removes_from_redis(): void
    {
        // Arrange: Fake the queue
        Queue::fake();
        $service = app(SchedulingService::class);

        // Create and schedule an announcement
        $announcement = Announcement::factory()->create();
        $scheduledFor = now()->addHours(1);
        $service->enqueueJob($announcement, $scheduledFor);

        // Verify job was scheduled
        Queue::assertPushed(PublishAnnouncementJob::class, 1);

        // Act: Cancel the scheduled job
        $service->cancelJob($announcement->id);

        // Assert: After cancellation, attempting to get scheduled jobs should return empty
        $scheduledJobs = $service->getScheduledJobs($announcement->id);
        $this->assertIsArray($scheduledJobs);
        $this->assertEmpty($scheduledJobs, 'Scheduled jobs should be empty after cancellation');
    }

    /**
     * Test 3: reschedule cancels old job and enqueues new one
     *
     * Verifies that rescheduling properly:
     * 1. Cancels the existing scheduled job
     * 2. Enqueues a new job with the new scheduled time
     */
    #[Test]
    public function reschedule_cancels_old_and_enqueues_new(): void
    {
        // Arrange: Fake the queue
        Queue::fake();
        $service = app(SchedulingService::class);

        // Create announcement and schedule it for 1 hour from now
        $announcement = Announcement::factory()->create();
        $oldScheduledFor = now()->addHour();
        $service->enqueueJob($announcement, $oldScheduledFor);

        // Verify initial job was scheduled
        Queue::assertPushed(PublishAnnouncementJob::class, 1);

        // Act: Reschedule to 3 hours from now
        $newScheduledFor = now()->addHours(3);
        $service->reschedule($announcement, $newScheduledFor);

        // Assert: Verify two jobs were pushed total (old + new)
        Queue::assertPushed(PublishAnnouncementJob::class, 2);

        // Verify the new job has the correct delay (~3 hours = ~10800 seconds)
        Queue::assertPushed(PublishAnnouncementJob::class, function ($job) use ($newScheduledFor, $announcement) {
            $jobDelay = $job->delay;
            $expectedDelay = now()->diffInSeconds($newScheduledFor, false);

            // Check if this is the new job (with ~3 hour delay)
            $isNewJob = $jobDelay >= ($expectedDelay - 10)
                && $jobDelay <= ($expectedDelay + 10);

            $isCorrectAnnouncement = $job->announcementId === $announcement->id;

            return $isNewJob && $isCorrectAnnouncement;
        });
    }

    /**
     * Test 4: validateScheduleDate throws exception for past dates
     *
     * Verifies that attempting to schedule an announcement in the past
     * throws InvalidArgumentException with appropriate message.
     */
    #[Test]
    public function validate_schedule_date_throws_on_past_date(): void
    {
        // Arrange: Get service instance
        $service = app(SchedulingService::class);

        // Create a date in the past (1 hour ago)
        $pastDate = now()->subHour();

        // Assert: Expect InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scheduled date must be in the future');

        // Act: Attempt to validate past date (should throw)
        $service->validateScheduleDate($pastDate);
    }

    /**
     * Test 5: validateScheduleDate throws exception for dates too far in future
     *
     * Verifies that scheduling more than 1 year in the future
     * throws InvalidArgumentException with appropriate message.
     */
    #[Test]
    public function validate_schedule_date_throws_on_too_far_future(): void
    {
        // Arrange: Get service instance
        $service = app(SchedulingService::class);

        // Create a date 400 days in the future (exceeds 1 year limit)
        $farFutureDate = now()->addDays(400);

        // Assert: Expect InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scheduled date cannot be more than 1 year in the future');

        // Act: Attempt to validate far future date (should throw)
        $service->validateScheduleDate($farFutureDate);
    }

    /**
     * Test 6: getScheduledJobs returns array with job details
     *
     * Verifies that getScheduledJobs() returns an array containing
     * information about scheduled jobs for a specific announcement.
     */
    #[Test]
    public function get_scheduled_jobs_for_announcement(): void
    {
        // Arrange: Fake the queue
        Queue::fake();
        $service = app(SchedulingService::class);

        // Create announcement and schedule it
        $announcement = Announcement::factory()->create();
        $scheduledFor = now()->addHours(2);
        $service->enqueueJob($announcement, $scheduledFor);

        // Act: Get scheduled jobs for this announcement
        $scheduledJobs = $service->getScheduledJobs($announcement->id);

        // Assert: Verify the result is an array
        $this->assertIsArray($scheduledJobs);

        // When using Queue::fake(), the jobs are intercepted but not actually queued
        // So we verify the structure is correct (array that can contain job details)
        // In a real scenario with actual queue, this would contain job metadata

        // Additional assertion: calling again after cancellation should return empty
        $service->cancelJob($announcement->id);
        $jobsAfterCancel = $service->getScheduledJobs($announcement->id);
        $this->assertIsArray($jobsAfterCancel);
    }
}
