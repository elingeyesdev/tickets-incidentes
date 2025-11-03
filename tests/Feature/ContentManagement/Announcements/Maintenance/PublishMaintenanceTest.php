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
 * Test suite for POST /api/v1/announcements/{id}/publish
 *
 * Verifies:
 * - State transitions: DRAFT → PUBLISHED, SCHEDULED → PUBLISHED
 * - Cannot publish already PUBLISHED or ARCHIVED announcements
 * - Sets published_at timestamp correctly
 * - Permission checks (company admin ownership)
 * - Role restrictions (END_USER, PLATFORM_ADMIN cannot publish)
 */
class PublishMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function company_admin_can_publish_maintenance_from_draft(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::DRAFT,
            'published_at' => null,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/v1/announcements/{$announcement->id}/publish");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $announcement->id,
                    'status' => 'PUBLISHED',
                ],
            ]);

        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'status' => PublicationStatus::PUBLISHED->value,
        ]);

        $announcement->refresh();
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
        $this->assertNotNull($announcement->published_at);
    }

    #[Test]
    public function company_admin_can_publish_maintenance_from_scheduled(): void
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
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/v1/announcements/{$announcement->id}/publish");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $announcement->id,
                    'status' => 'PUBLISHED',
                ],
            ]);

        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'status' => PublicationStatus::PUBLISHED->value,
        ]);

        $announcement->refresh();
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
        $this->assertNotNull($announcement->published_at);

        // Note: Verify scheduled job is cancelled in the controller/service
        // This would typically be done via Queue::fake() assertions
    }

    #[Test]
    public function cannot_publish_already_published_maintenance(): void
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
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/v1/announcements/{$announcement->id}/publish");

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Announcement is already published',
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
    }

    #[Test]
    public function cannot_publish_archived_maintenance(): void
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
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/v1/announcements/{$announcement->id}/publish");

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Cannot publish archived announcement',
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::ARCHIVED, $announcement->status);
    }

    #[Test]
    public function publish_sets_published_at_timestamp(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::DRAFT,
            'published_at' => null,
        ]);

        $beforePublish = Carbon::now()->subSecond();

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/v1/announcements/{$announcement->id}/publish");

        $afterPublish = Carbon::now()->addSecond();

        // Assert
        $response->assertStatus(200);

        $announcement->refresh();
        $this->assertNotNull($announcement->published_at);
        $this->assertTrue(
            $announcement->published_at->between($beforePublish, $afterPublish),
            'published_at should be set to current timestamp'
        );
    }

    #[Test]
    public function company_admin_from_different_company_cannot_publish(): void
    {
        // Arrange
        $adminA = User::factory()->create();
        $companyA = Company::factory()->create(['admin_user_id' => $adminA->id]);
        $adminA->assignRole('COMPANY_ADMIN', $companyA->id);

        $adminB = User::factory()->create();
        $companyB = Company::factory()->create(['admin_user_id' => $adminB->id]);
        $adminB->assignRole('COMPANY_ADMIN', $companyB->id);

        $announcementA = Announcement::factory()->create([
            'company_id' => $companyA->id,
            'author_id' => $adminA->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::DRAFT,
        ]);

        // Act - adminB tries to publish adminA's announcement
        $response = $this->authenticateWithJWT($adminB)
            ->postJson("/api/v1/announcements/{$announcementA->id}/publish");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'This action is unauthorized',
            ]);

        // Verify status hasn't changed
        $announcementA->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcementA->status);
        $this->assertNull($announcementA->published_at);
    }

    #[Test]
    public function end_user_cannot_publish_maintenance(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $endUser = User::factory()->create();
        $endUser->assignRole('END_USER', $company->id);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::DRAFT,
        ]);

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson("/api/v1/announcements/{$announcement->id}/publish");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Insufficient permissions',
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
        $this->assertNull($announcement->published_at);
    }

    #[Test]
    public function platform_admin_cannot_publish_maintenance(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $companyAdmin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $companyAdmin->id]);
        $companyAdmin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $companyAdmin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::DRAFT,
        ]);

        // Act - PLATFORM_ADMIN is read-only for company content
        $response = $this->authenticateWithJWT($platformAdmin)
            ->postJson("/api/v1/announcements/{$announcement->id}/publish");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Platform admins cannot publish company announcements',
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
        $this->assertNull($announcement->published_at);
    }
}
