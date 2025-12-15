<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Maintenance;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

final class UnscheduleMaintenanceTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function company_admin_can_unschedule_maintenance(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create and schedule a maintenance announcement
        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Scheduled Maintenance',
        ], 'schedule', now()->addDays(2)->toIso8601String());

        // Verify it's scheduled
        $this->assertEquals(PublicationStatus::SCHEDULED, $announcement->status);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        // Verify status changed to DRAFT
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
    }

    #[Test]
    public function unscheduling_removes_scheduled_for_from_metadata(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $scheduledFor = now()->addDays(3)->toIso8601String();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Scheduled Maintenance',
        ], 'schedule', $scheduledFor);

        // Verify scheduled_for is in metadata
        $this->assertArrayHasKey('scheduled_for', $announcement->metadata);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert
        $response->assertStatus(200);

        // Verify scheduled_for removed from metadata
        $announcement->refresh();
        $this->assertArrayNotHasKey('scheduled_for', $announcement->metadata);
    }

    #[Test]
    public function unscheduling_cancels_job_in_redis(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $scheduledFor = now()->addDays(1)->toIso8601String();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Scheduled Maintenance',
        ], 'schedule', $scheduledFor);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert
        $response->assertStatus(200);
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
    }

    #[Test]
    public function cannot_unschedule_non_scheduled_maintenance(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Draft Maintenance',
        ], 'draft');

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Announcement is not scheduled',
            ]);
    }

    #[Test]
    public function cannot_unschedule_published_maintenance(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Published Maintenance',
        ], 'publish');

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Cannot unschedule published announcement',
            ]);
    }

    #[Test]
    public function end_user_cannot_unschedule(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $endUser = User::factory()->withRole('USER')->create();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Scheduled Maintenance',
        ], 'schedule', now()->addDays(2)->toIso8601String());

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Insufficient permissions',
            ]);
    }
}
