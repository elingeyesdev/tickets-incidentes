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
 * Test suite for DELETE /api/announcements/{id}
 *
 * Verifies:
 * - DRAFT and ARCHIVED announcements can be deleted (204 No Content)
 * - PUBLISHED announcements cannot be deleted (must archive first)
 * - SCHEDULED announcements cannot be deleted (must unschedule first)
 * - Deleted announcements are not retrievable (404)
 * - Redis scheduled jobs are cancelled when deleting scheduled announcements (future)
 * - Permission checks (only COMPANY_ADMIN can delete)
 * - END_USER cannot delete announcements
 */
class DeleteMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function company_admin_can_delete_draft_maintenance(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Draft Maintenance Announcement',
        ], 'draft');

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/announcements/{$announcement->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        // Verify announcement is deleted from database
        $this->assertDatabaseMissing('company_announcements', [
            'id' => $announcement->id,
        ]);
    }

    #[Test]
    public function company_admin_can_delete_archived_maintenance(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::ARCHIVED,
            'published_at' => Carbon::now()->subWeek(),
            'title' => 'Archived Maintenance Announcement',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/announcements/{$announcement->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        // Verify announcement is deleted from database
        $this->assertDatabaseMissing('company_announcements', [
            'id' => $announcement->id,
        ]);
    }

    #[Test]
    public function cannot_delete_published_maintenance(): void
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
            'published_at' => Carbon::now()->subHour(),
            'title' => 'Published Maintenance Announcement',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/announcements/{$announcement->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Cannot delete published announcement. Archive it first.',
            ]);

        // Verify announcement still exists
        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'status' => PublicationStatus::PUBLISHED->value,
        ]);
    }

    #[Test]
    public function cannot_delete_scheduled_maintenance(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $scheduledFor = Carbon::now()->addDay();

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'scheduled_for' => $scheduledFor->toIso8601String(),
                'urgency' => 'HIGH',
            ],
            'published_at' => null,
            'title' => 'Scheduled Maintenance Announcement',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/announcements/{$announcement->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Cannot delete scheduled announcement. Unschedule it first.',
            ]);

        // Verify announcement still exists
        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'status' => PublicationStatus::SCHEDULED->value,
        ]);
    }

    #[Test]
    public function deleting_scheduled_maintenance_cancels_redis_job(): void
    {
        // NOTE: This test is for future implementation when SCHEDULED announcements
        // can be deleted directly (if business rules change).
        //
        // When implemented, this test would verify that:
        // 1. The DELETE endpoint cancels any Redis scheduled jobs
        // 2. The scheduled publication job is removed from the queue
        // 3. The announcement is successfully deleted
        //
        // Implementation approach:
        // - Use Queue::fake() to intercept scheduled jobs
        // - Verify that a "cancel scheduled job" command is dispatched
        // - Check that the specific job ID is removed from Redis
        //
        // Example assertion:
        // Queue::assertPushed(CancelScheduledAnnouncementJob::class, function ($job) use ($announcement) {
        //     return $job->announcementId === $announcement->id;
        // });

        $this->markTestSkipped(
            'Scheduled announcements currently cannot be deleted. ' .
            'This test is a placeholder for future implementation if business rules change.'
        );
    }

    #[Test]
    public function deleted_maintenance_cannot_be_retrieved(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Draft to be Deleted',
        ], 'draft');

        $announcementId = $announcement->id;

        // Act - Delete the announcement
        $deleteResponse = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/announcements/{$announcementId}");

        // Assert - Deletion successful
        $deleteResponse->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        // Act - Try to retrieve the deleted announcement
        $getResponse = $this->authenticateWithJWT($admin)
            ->getJson("/api/announcements/{$announcementId}");

        // Assert - 404 Not Found
        $getResponse->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Announcement not found',
            ]);
    }

    #[Test]
    public function end_user_cannot_delete_maintenance(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $endUser = User::factory()->withRole('USER')->create();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($admin, [
            'title' => 'Draft Maintenance Announcement',
        ], 'draft');

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->deleteJson("/api/announcements/{$announcement->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Insufficient permissions',
            ]);

        // Verify announcement still exists
        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'status' => PublicationStatus::DRAFT->value,
        ]);
    }

    #[Test]
    public function company_admin_from_different_company_cannot_delete(): void
    {
        // Arrange
        $adminA = $this->createCompanyAdmin();
        $adminB = $this->createCompanyAdmin();

        $announcementA = $this->createMaintenanceAnnouncementViaHttp($adminA, [
            'title' => 'Company A Draft',
        ], 'draft');

        // Act - adminB tries to delete adminA's announcement
        $response = $this->authenticateWithJWT($adminB)
            ->deleteJson("/api/announcements/{$announcementA->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'This action is unauthorized',
            ]);

        // Verify announcement still exists
        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcementA->id,
            'status' => PublicationStatus::DRAFT->value,
        ]);
    }

    #[Test]
    public function platform_admin_cannot_delete_company_maintenance(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $companyAdmin = $this->createCompanyAdmin();

        $announcement = $this->createMaintenanceAnnouncementViaHttp($companyAdmin, [
            'title' => 'Company Draft',
        ], 'draft');

        // Act - PLATFORM_ADMIN tries to delete company announcement
        $response = $this->authenticateWithJWT($platformAdmin)
            ->deleteJson("/api/announcements/{$announcement->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Platform admins cannot delete company announcements',
            ]);

        // Verify announcement still exists
        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'status' => PublicationStatus::DRAFT->value,
        ]);
    }

    #[Test]
    public function deleting_nonexistent_announcement_returns_404(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $nonExistentId = 99999;

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/announcements/{$nonExistentId}");

        // Assert
        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Announcement not found',
            ]);
    }
}
