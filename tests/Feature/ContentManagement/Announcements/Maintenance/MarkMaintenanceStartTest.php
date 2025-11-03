<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Maintenance;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Comprehensive Feature Tests for Marking Maintenance Start
 *
 * Tests POST /api/announcements/maintenance/:id/start endpoint
 *
 * Coverage:
 * - Company admin can mark maintenance start
 * - Start can be marked before scheduled_start (early start)
 * - Start can be marked after scheduled_start (late start)
 * - Marking start does not change announcement status
 * - Cannot mark start twice (idempotency check)
 * - End user cannot mark maintenance start (authorization)
 */
class MarkMaintenanceStartTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function company_admin_can_mark_maintenance_start(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = $this->getCompanyForUser($admin);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => now()->addHours(2)->toIso8601String(),
                'scheduled_end' => now()->addHours(6)->toIso8601String(),
                'is_emergency' => false,
                'affected_services' => ['api', 'database'],
            ],
        ]);

        // Record the time before the request
        $beforeRequest = Carbon::now();

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/start");

        // Record the time after the request
        $afterRequest = Carbon::now();

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $announcement->id)
            ->assertJsonPath('data.type', 'MAINTENANCE')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'type',
                    'status',
                    'metadata',
                ],
            ]);

        // Verify database has actual_start in metadata
        $announcement->refresh();

        $this->assertArrayHasKey('actual_start', $announcement->metadata);
        $this->assertNotNull($announcement->metadata['actual_start']);

        // Verify actual_start is a valid timestamp within the request window
        $actualStart = Carbon::parse($announcement->metadata['actual_start']);

        $this->assertTrue(
            $actualStart->between($beforeRequest->subSecond(), $afterRequest->addSecond()),
            "actual_start should be set to current timestamp"
        );
    }

    #[Test]
    public function start_can_be_marked_before_scheduled_start(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = $this->getCompanyForUser($admin);

        // Maintenance scheduled for 10:00 AM
        $scheduledStart = Carbon::now()->setTime(10, 0, 0);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => $scheduledStart->toIso8601String(),
                'scheduled_end' => $scheduledStart->copy()->addHours(4)->toIso8601String(),
                'is_emergency' => false,
                'affected_services' => ['api'],
            ],
        ]);

        // Mark start at 09:55 AM (5 minutes early)
        $earlyStartTime = $scheduledStart->copy()->subMinutes(5);
        Carbon::setTestNow($earlyStartTime);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/start");

        // Assert
        $response->assertStatus(200);

        $announcement->refresh();

        $this->assertArrayHasKey('actual_start', $announcement->metadata);

        $actualStart = Carbon::parse($announcement->metadata['actual_start']);
        $scheduledStartCarbon = Carbon::parse($announcement->metadata['scheduled_start']);

        // Verify actual_start (09:55) is BEFORE scheduled_start (10:00)
        $this->assertTrue(
            $actualStart->lessThan($scheduledStartCarbon),
            "actual_start (09:55) should be before scheduled_start (10:00)"
        );

        // Verify exact time (within 1 second tolerance)
        $this->assertEquals(
            $earlyStartTime->format('Y-m-d H:i:s'),
            $actualStart->format('Y-m-d H:i:s'),
            "actual_start should be set to 09:55 (early start)"
        );

        // Cleanup
        Carbon::setTestNow();
    }

    #[Test]
    public function start_can_be_marked_after_scheduled_start(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = $this->getCompanyForUser($admin);

        // Maintenance scheduled for 10:00 AM
        $scheduledStart = Carbon::now()->setTime(10, 0, 0);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => $scheduledStart->toIso8601String(),
                'scheduled_end' => $scheduledStart->copy()->addHours(3)->toIso8601String(),
                'is_emergency' => false,
                'affected_services' => ['reports'],
            ],
        ]);

        // Mark start at 10:05 AM (5 minutes late)
        $lateStartTime = $scheduledStart->copy()->addMinutes(5);
        Carbon::setTestNow($lateStartTime);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/start");

        // Assert
        $response->assertStatus(200);

        $announcement->refresh();

        $this->assertArrayHasKey('actual_start', $announcement->metadata);

        $actualStart = Carbon::parse($announcement->metadata['actual_start']);
        $scheduledStartCarbon = Carbon::parse($announcement->metadata['scheduled_start']);

        // Verify actual_start (10:05) is AFTER scheduled_start (10:00)
        $this->assertTrue(
            $actualStart->greaterThan($scheduledStartCarbon),
            "actual_start (10:05) should be after scheduled_start (10:00)"
        );

        // Verify exact time (within 1 second tolerance)
        $this->assertEquals(
            $lateStartTime->format('Y-m-d H:i:s'),
            $actualStart->format('Y-m-d H:i:s'),
            "actual_start should be set to 10:05 (late start)"
        );

        // Cleanup
        Carbon::setTestNow();
    }

    #[Test]
    public function marking_start_does_not_change_status(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = $this->getCompanyForUser($admin);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'published_at' => now()->subHour(),
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => now()->addHours(1)->toIso8601String(),
                'scheduled_end' => now()->addHours(5)->toIso8601String(),
                'is_emergency' => false,
                'affected_services' => ['api'],
            ],
        ]);

        // Verify initial status
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/start");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'PUBLISHED');

        $announcement->refresh();

        // Verify status remains PUBLISHED after marking start
        $this->assertEquals(
            PublicationStatus::PUBLISHED,
            $announcement->status,
            "Status should remain PUBLISHED after marking start"
        );

        // Verify actual_start was added without changing status
        $this->assertArrayHasKey('actual_start', $announcement->metadata);
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
    }

    #[Test]
    public function cannot_mark_start_twice(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = $this->getCompanyForUser($admin);

        $firstStartTime = Carbon::now()->subMinutes(10);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => now()->addHours(2)->toIso8601String(),
                'scheduled_end' => now()->addHours(6)->toIso8601String(),
                'is_emergency' => false,
                'affected_services' => ['api'],
                'actual_start' => $firstStartTime->toIso8601String(), // Already marked
            ],
        ]);

        // Act - Try to mark start again
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/start");

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Maintenance start already marked',
            ]);

        $announcement->refresh();

        // Verify actual_start was NOT changed (still the first time)
        $this->assertEquals(
            $firstStartTime->format('Y-m-d H:i:s'),
            Carbon::parse($announcement->metadata['actual_start'])->format('Y-m-d H:i:s'),
            "actual_start should not be modified when already set"
        );
    }

    #[Test]
    public function end_user_cannot_mark_start(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = $this->getCompanyForUser($admin);

        // Create end user (USER role) without company context
        // USER role (Cliente) cannot have company context
        $endUser = User::factory()->withRole('USER')->create();

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => now()->addHours(2)->toIso8601String(),
                'scheduled_end' => now()->addHours(6)->toIso8601String(),
                'is_emergency' => false,
                'affected_services' => ['api'],
            ],
        ]);

        // Act - End user tries to mark start
        $response = $this->authenticateWithJWT($endUser)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/start");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'This action is unauthorized']);

        $announcement->refresh();

        // Verify actual_start was NOT added
        $this->assertArrayNotHasKey('actual_start', $announcement->metadata);
    }

    // ==================== Helper Methods ====================

    /**
     * Get the company associated with a user.
     */
    private function getCompanyForUser(User $user): Company
    {
        return Company::whereHas('userRoles', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->first();
    }
}
