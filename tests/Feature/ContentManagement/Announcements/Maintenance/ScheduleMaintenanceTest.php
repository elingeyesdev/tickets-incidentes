<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Maintenance;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Jobs\PublishAnnouncementJob;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Test suite for POST /api/announcements/{id}/schedule
 *
 * Verifies:
 * - State transitions: DRAFT → SCHEDULED
 * - Job enqueueing with correct delay calculation
 * - Metadata updates (scheduled_for field)
 * - Validation rules (required, future date, max 1 year)
 * - Cannot schedule PUBLISHED or ARCHIVED announcements
 * - Rescheduling updates existing jobs
 * - Permission checks (company admin ownership)
 * - Role restrictions (END_USER cannot schedule)
 */
class ScheduleMaintenanceTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function company_admin_can_schedule_maintenance_from_draft(): void
    {
        // Arrange
        Queue::fake();

        $admin = $this->createCompanyAdmin();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [], 'draft');

        $scheduledFor = Carbon::now()->addHours(6);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $scheduledFor->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $announcement->id,
                    'status' => 'SCHEDULED',
                ],
            ]);

        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'status' => PublicationStatus::SCHEDULED->value,
        ]);

        $announcement->refresh();
        $this->assertEquals(PublicationStatus::SCHEDULED, $announcement->status);
        $this->assertNull($announcement->published_at, 'published_at should remain null until actual publication');
    }

    #[Test]
    public function scheduling_enqueues_publish_job_in_redis(): void
    {
        // Arrange
        Queue::fake();

        $admin = $this->createCompanyAdmin();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [], 'draft');

        $scheduledFor = Carbon::now()->addHours(3);
        $expectedDelay = $scheduledFor->diffInSeconds(Carbon::now());

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $scheduledFor->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(200);

        Queue::assertPushed(PublishAnnouncementJob::class, function ($job) use ($announcement, $expectedDelay) {
            // Verify job is for the correct announcement
            $this->assertEquals($announcement->id, $job->announcementId);

            // Verify delay is approximately correct (allow 2-second tolerance)
            $actualDelay = $job->delay instanceof \DateTimeInterface
                ? $job->delay->diffInSeconds(Carbon::now())
                : $job->delay;

            $this->assertEqualsWithDelta($expectedDelay, $actualDelay, 2, 'Job delay should match scheduled_for - now()');

            return true;
        });
    }

    #[Test]
    public function scheduling_adds_scheduled_for_to_metadata(): void
    {
        // Arrange
        Queue::fake();

        $admin = $this->createCompanyAdmin();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'urgency' => 'HIGH',
        ], 'draft');

        $scheduledFor = Carbon::now()->addHours(6);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $scheduledFor->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(200);

        $announcement->refresh();

        // Verify scheduled_for is in metadata
        $this->assertArrayHasKey('scheduled_for', $announcement->metadata);
        $this->assertEquals($scheduledFor->toIso8601String(), $announcement->metadata['scheduled_for']);

        // Verify existing metadata is preserved
        $this->assertEquals('HIGH', $announcement->metadata['urgency']);
    }

    #[Test]
    public function validates_scheduled_for_is_required(): void
    {
        // Arrange
        Queue::fake();

        $admin = $this->createCompanyAdmin();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [], 'draft');

        // Act - no scheduled_for provided
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                // Missing scheduled_for
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_for'])
            ->assertJsonFragment([
                'scheduled_for' => ['The scheduled for field is required.'],
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function validates_scheduled_for_is_at_least_5_minutes_future(): void
    {
        // Arrange
        Queue::fake();

        $admin = $this->createCompanyAdmin();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [], 'draft');

        $tooSoon = Carbon::now()->addMinutes(2);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $tooSoon->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_for'])
            ->assertJsonFragment([
                'scheduled_for' => ['The scheduled for field must be at least 5 minutes in the future.'],
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function validates_scheduled_for_is_not_more_than_1_year_future(): void
    {
        // Arrange
        Queue::fake();

        $admin = $this->createCompanyAdmin();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [], 'draft');

        $tooFar = Carbon::now()->addDays(400);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $tooFar->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_for'])
            ->assertJsonFragment([
                'scheduled_for' => ['The scheduled for field must not be more than 1 year in the future.'],
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function cannot_schedule_already_published_maintenance(): void
    {
        // Arrange
        Queue::fake();

        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'published_at' => Carbon::now()->subHour(),
        ]);

        $scheduledFor = Carbon::now()->addHours(6);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $scheduledFor->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Cannot schedule already published announcement',
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function rescheduling_scheduled_maintenance_updates_job_in_redis(): void
    {
        // Arrange
        Queue::fake();

        $admin = $this->createCompanyAdmin();
        $firstScheduledFor = Carbon::now()->addHours(10);

        // Create draft first
        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'urgency' => 'HIGH',
        ], 'draft');

        // Act - First schedule
        $response1 = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $firstScheduledFor->toIso8601String(),
            ]);

        $response1->assertStatus(200);

        Queue::assertPushed(PublishAnnouncementJob::class, 1);

        // Act - Reschedule with new date
        $newScheduledFor = Carbon::now()->addHours(24);
        $expectedNewDelay = $newScheduledFor->diffInSeconds(Carbon::now());

        $response2 = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $newScheduledFor->toIso8601String(),
            ]);

        // Assert
        $response2->assertStatus(200);

        // Verify a new job was enqueued (total should now be 2)
        Queue::assertPushed(PublishAnnouncementJob::class, 2);

        // Verify the most recent job has the new delay
        Queue::assertPushed(PublishAnnouncementJob::class, function ($job) use ($announcement, $expectedNewDelay) {
            $this->assertEquals($announcement->id, $job->announcementId);

            $actualDelay = $job->delay instanceof \DateTimeInterface
                ? $job->delay->diffInSeconds(Carbon::now())
                : $job->delay;

            // Allow 2-second tolerance for execution time
            return abs($expectedNewDelay - $actualDelay) <= 2;
        });

        // Note: In real implementation, the previous job should be cancelled via Redis
        // This would typically use job IDs or tags for tracking
    }

    #[Test]
    public function scheduling_from_scheduled_replaces_previous_schedule(): void
    {
        // Arrange
        Queue::fake();

        $admin = $this->createCompanyAdmin();
        $originalScheduledFor = Carbon::now()->addHours(10);

        // Create and schedule via HTTP
        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'urgency' => 'HIGH',
        ], 'draft');

        // Schedule it
        $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $originalScheduledFor->toIso8601String(),
            ]);

        $announcement->refresh();

        $newScheduledFor = Carbon::now()->addHours(24);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $newScheduledFor->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $announcement->id,
                    'status' => 'SCHEDULED',
                ],
            ]);

        $announcement->refresh();

        // Verify metadata.scheduled_for was updated
        $this->assertArrayHasKey('scheduled_for', $announcement->metadata);
        $this->assertEquals($newScheduledFor->toIso8601String(), $announcement->metadata['scheduled_for']);
        $this->assertNotEquals($originalScheduledFor->toIso8601String(), $announcement->metadata['scheduled_for']);

        // Verify other metadata is preserved
        $this->assertEquals('HIGH', $announcement->metadata['urgency']);

        // Verify status remains SCHEDULED
        $this->assertEquals(PublicationStatus::SCHEDULED, $announcement->status);
    }

    #[Test]
    public function end_user_cannot_schedule_maintenance(): void
    {
        // Arrange
        Queue::fake();

        $admin = $this->createCompanyAdmin();
        $company = Company::whereHas('userRoles', function ($query) use ($admin) {
            $query->where('user_id', $admin->id);
        })->first();

        $endUser = User::factory()->create();
        // User without any role in this company will have default USER role in JWT

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [], 'draft');

        $scheduledFor = Carbon::now()->addHours(6);

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $scheduledFor->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Insufficient permissions',
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
        $this->assertArrayNotHasKey('scheduled_for', $announcement->metadata ?? []);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function cannot_schedule_archived_maintenance(): void
    {
        // Arrange
        Queue::fake();

        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::ARCHIVED,
            'published_at' => Carbon::now()->subWeek(),
        ]);

        $scheduledFor = Carbon::now()->addHours(6);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $scheduledFor->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Cannot schedule archived announcement',
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::ARCHIVED, $announcement->status);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function scheduling_archived_maintenance_requires_restore_first(): void
    {
        // Arrange
        Queue::fake();

        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::ARCHIVED,
            'published_at' => Carbon::now()->subMonth(),
        ]);

        $scheduledFor = Carbon::now()->addDays(2);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/schedule", [
                'scheduled_for' => $scheduledFor->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Cannot schedule archived announcement',
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::ARCHIVED, $announcement->status);

        Queue::assertNothingPushed();
    }
}
