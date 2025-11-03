<?php

declare(strict_types=1);

namespace Tests\Unit\ContentManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Jobs\PublishAnnouncementJob;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Services\AnnouncementService;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Comprehensive unit tests for AnnouncementService
 *
 * Tests all business logic using TDD approach with modern PHP 8 attributes.
 * Total: 8 tests
 */
class AnnouncementServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnnouncementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AnnouncementService::class);
    }

    /**
     * Test 1: Create announcement sets default status to DRAFT
     */
    #[Test]
    public function create_announcement_sets_default_status_to_draft(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $author = User::factory()->create();

        $data = [
            'company_id' => $company->id,
            'author_id' => $author->id,
            'title' => 'Test Announcement',
            'content' => 'Test content for announcement',
            'type' => AnnouncementType::MAINTENANCE,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => now()->addDays(2)->toIso8601String(),
                'scheduled_end' => now()->addDays(2)->addHours(4)->toIso8601String(),
                'is_emergency' => false,
            ],
            // NO action specified - should default to DRAFT
        ];

        // Act
        $announcement = $this->service->create($data);

        // Assert
        $this->assertInstanceOf(Announcement::class, $announcement);
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
        $this->assertNull($announcement->published_at);
        $this->assertEquals('Test Announcement', $announcement->title);
        $this->assertEquals($company->id, $announcement->company_id);
        $this->assertEquals($author->id, $announcement->author_id);

        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'title' => 'Test Announcement',
            'status' => PublicationStatus::DRAFT->value,
            'published_at' => null,
        ]);
    }

    /**
     * Test 2: Publish action sets status to PUBLISHED and published_at timestamp
     */
    #[Test]
    public function publish_action_sets_status_and_published_at(): void
    {
        // Arrange
        $announcement = Announcement::factory()->create([
            'status' => PublicationStatus::DRAFT,
            'published_at' => null,
        ]);

        // Act
        $published = $this->service->publish($announcement);

        // Assert
        $this->assertEquals(PublicationStatus::PUBLISHED, $published->status);
        $this->assertNotNull($published->published_at);
        $this->assertInstanceOf(Carbon::class, $published->published_at);

        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'status' => PublicationStatus::PUBLISHED->value,
        ]);

        // Verify published_at is recent (within last minute)
        $this->assertTrue($published->published_at->greaterThan(now()->subMinute()));
    }

    /**
     * Test 3: Schedule action enqueues Redis job for future publication
     */
    #[Test]
    public function schedule_action_enqueues_redis_job(): void
    {
        // Arrange
        Queue::fake();

        $announcement = Announcement::factory()->create([
            'status' => PublicationStatus::DRAFT,
        ]);

        $scheduledFor = now()->addDays(1);

        // Act
        $scheduled = $this->service->schedule($announcement, $scheduledFor);

        // Assert
        $this->assertEquals(PublicationStatus::SCHEDULED, $scheduled->status);

        // Verify job was dispatched
        Queue::assertPushed(PublishAnnouncementJob::class, function ($job) use ($announcement) {
            return $job->announcementId === $announcement->id;
        });

        // Verify metadata contains scheduled_for
        $this->assertArrayHasKey('scheduled_for', $scheduled->metadata);
        $this->assertEquals(
            $scheduledFor->toIso8601String(),
            $scheduled->metadata['scheduled_for']
        );
    }

    /**
     * Test 4: Schedule validates that scheduled_for is in the future
     */
    #[Test]
    public function schedule_validates_future_date(): void
    {
        // Arrange
        $announcement = Announcement::factory()->create([
            'status' => PublicationStatus::DRAFT,
        ]);

        $pastDate = now()->subHour();

        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Scheduled date must be in the future');

        $this->service->schedule($announcement, $pastDate);
    }

    /**
     * Test 5: Update only allows DRAFT or SCHEDULED announcements
     */
    #[Test]
    public function update_only_allows_draft_or_scheduled(): void
    {
        // Arrange
        $announcement = Announcement::factory()->published()->create([
            'status' => PublicationStatus::PUBLISHED,
        ]);

        $data = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ];

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot edit published announcement');

        $this->service->update($announcement, $data);
    }

    /**
     * Test 6: Archive only allows PUBLISHED announcements
     */
    #[Test]
    public function archive_only_allows_published(): void
    {
        // Arrange
        $announcement = Announcement::factory()->create([
            'status' => PublicationStatus::DRAFT,
        ]);

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only published announcements can be archived');

        $this->service->archive($announcement);
    }

    /**
     * Test 7: Restore only allows ARCHIVED announcements
     */
    #[Test]
    public function restore_only_allows_archived(): void
    {
        // Arrange
        $announcement = Announcement::factory()->published()->create([
            'status' => PublicationStatus::PUBLISHED,
        ]);

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only archived announcements can be restored');

        $this->service->restore($announcement);
    }

    /**
     * Test 8: Delete only allows DRAFT or ARCHIVED announcements
     */
    #[Test]
    public function delete_only_allows_draft_or_archived(): void
    {
        // Arrange
        $announcement = Announcement::factory()->published()->create([
            'status' => PublicationStatus::PUBLISHED,
        ]);

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete published announcement');

        $this->service->delete($announcement);
    }
}