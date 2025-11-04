<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Maintenance;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Test suite for POST /api/announcements/maintenance/:id/complete
 *
 * Verifies:
 * - Company admin can mark maintenance complete
 * - metadata.actual_end is set to current timestamp
 * - Complete can be marked before scheduled_end (early completion)
 * - Complete can be marked after scheduled_end (delayed completion)
 * - Requires actual_start to be marked first
 * - actual_end must be after actual_start validation
 * - Idempotency: cannot mark complete twice
 * - Permission checks (END_USER cannot mark complete)
 */
class MarkMaintenanceCompleteTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function company_admin_can_mark_maintenance_complete(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $actualStart = Carbon::now()->subHours(2);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => $actualStart->toIso8601String(),
                'scheduled_end' => Carbon::now()->addHours(2)->toIso8601String(),
                'actual_start' => $actualStart->toIso8601String(),
            ],
        ]);

        $beforeComplete = Carbon::now()->subSecond();

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/complete");

        $afterComplete = Carbon::now()->addSecond();

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Maintenance completed',
                'data' => [
                    'id' => $announcement->id,
                ],
            ]);

        $announcement->refresh();

        // Verify actual_end is in metadata
        $this->assertArrayHasKey('actual_end', $announcement->metadata);
        $actualEnd = Carbon::parse($announcement->metadata['actual_end']);

        // Verify actual_end is approximately now
        $this->assertTrue(
            $actualEnd->between($beforeComplete, $afterComplete),
            'actual_end should be set to current timestamp'
        );

        // Verify existing metadata is preserved
        $this->assertEquals('HIGH', $announcement->metadata['urgency']);
        $this->assertEquals($actualStart->toIso8601String(), $announcement->metadata['actual_start']);
    }

    #[Test]
    public function complete_can_be_marked_before_scheduled_end(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $actualStart = Carbon::parse('2025-11-09 10:00:00');
        $scheduledEnd = Carbon::parse('2025-11-09 14:00:00');

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => Carbon::parse('2025-11-09 10:00:00')->toIso8601String(),
                'scheduled_end' => $scheduledEnd->toIso8601String(),
                'actual_start' => $actualStart->toIso8601String(),
            ],
        ]);

        // Current time simulated as 13:30 (before scheduled_end of 14:00)
        $earlyCompletionTime = Carbon::parse('2025-11-09 13:30:00');
        Carbon::setTestNow($earlyCompletionTime);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/complete");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Maintenance completed',
            ]);

        $announcement->refresh();

        // Verify actual_end = 13:30 (early completion)
        $this->assertArrayHasKey('actual_end', $announcement->metadata);
        $actualEnd = Carbon::parse($announcement->metadata['actual_end']);
        $this->assertEquals($earlyCompletionTime->toIso8601String(), $actualEnd->toIso8601String());

        // Verify actual_end is before scheduled_end
        $this->assertTrue(
            $actualEnd->lessThan($scheduledEnd),
            'Maintenance can be completed before scheduled_end (early completion)'
        );

        Carbon::setTestNow(); // Reset time
    }

    #[Test]
    public function complete_can_be_marked_after_scheduled_end(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $actualStart = Carbon::parse('2025-11-09 10:00:00');
        $scheduledEnd = Carbon::parse('2025-11-09 14:00:00');

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => Carbon::parse('2025-11-09 10:00:00')->toIso8601String(),
                'scheduled_end' => $scheduledEnd->toIso8601String(),
                'actual_start' => $actualStart->toIso8601String(),
            ],
        ]);

        // Current time simulated as 14:15 (after scheduled_end of 14:00)
        $delayedCompletionTime = Carbon::parse('2025-11-09 14:15:00');
        Carbon::setTestNow($delayedCompletionTime);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/complete");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Maintenance completed',
            ]);

        $announcement->refresh();

        // Verify actual_end = 14:15 (delayed completion)
        $this->assertArrayHasKey('actual_end', $announcement->metadata);
        $actualEnd = Carbon::parse($announcement->metadata['actual_end']);
        $this->assertEquals($delayedCompletionTime->toIso8601String(), $actualEnd->toIso8601String());

        // Verify actual_end is after scheduled_end
        $this->assertTrue(
            $actualEnd->greaterThan($scheduledEnd),
            'Maintenance can be completed after scheduled_end (delayed completion)'
        );

        Carbon::setTestNow(); // Reset time
    }

    #[Test]
    public function marking_complete_requires_start_first(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => Carbon::parse('2025-11-09 10:00:00')->toIso8601String(),
                'scheduled_end' => Carbon::parse('2025-11-09 14:00:00')->toIso8601String(),
                // actual_start is missing
            ],
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/complete");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Mark start first',
            ]);

        $announcement->refresh();

        // Verify actual_end was NOT added
        $this->assertArrayNotHasKey('actual_end', $announcement->metadata ?? []);
    }

    #[Test]
    public function actual_end_must_be_after_actual_start(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $actualStart = Carbon::parse('2025-11-09 10:00:00');

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => Carbon::parse('2025-11-09 10:00:00')->toIso8601String(),
                'scheduled_end' => Carbon::parse('2025-11-09 14:00:00')->toIso8601String(),
                'actual_start' => $actualStart->toIso8601String(),
            ],
        ]);

        // Try to mark complete at 09:00 (before actual_start of 10:00)
        $invalidEndTime = Carbon::parse('2025-11-09 09:00:00');
        Carbon::setTestNow($invalidEndTime);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/complete");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'The end date must be after the start date.',
            ]);

        $announcement->refresh();

        // Verify actual_end was NOT added
        $this->assertArrayNotHasKey('actual_end', $announcement->metadata ?? []);

        Carbon::setTestNow(); // Reset time
    }

    #[Test]
    public function cannot_mark_complete_twice(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $actualStart = Carbon::parse('2025-11-09 10:00:00');
        $actualEnd = Carbon::parse('2025-11-09 13:00:00');

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => Carbon::parse('2025-11-09 10:00:00')->toIso8601String(),
                'scheduled_end' => Carbon::parse('2025-11-09 14:00:00')->toIso8601String(),
                'actual_start' => $actualStart->toIso8601String(),
                'actual_end' => $actualEnd->toIso8601String(), // Already marked complete
            ],
        ]);

        // Act - Try to mark complete again
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/complete");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Maintenance already completed',
            ]);

        $announcement->refresh();

        // Verify actual_end hasn't changed
        $this->assertEquals($actualEnd->toIso8601String(), $announcement->metadata['actual_end']);
    }

    #[Test]
    public function end_user_cannot_mark_complete(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $endUser = User::factory()->create();
        // User without any role in this company will have default USER role in JWT

        $actualStart = Carbon::parse('2025-11-09 10:00:00');

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => Carbon::parse('2025-11-09 10:00:00')->toIso8601String(),
                'scheduled_end' => Carbon::parse('2025-11-09 14:00:00')->toIso8601String(),
                'actual_start' => $actualStart->toIso8601String(),
            ],
        ]);

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson("/api/announcements/maintenance/{$announcement->id}/complete");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Insufficient permissions',
            ]);

        $announcement->refresh();

        // Verify actual_end was NOT added
        $this->assertArrayNotHasKey('actual_end', $announcement->metadata ?? []);
    }
}
