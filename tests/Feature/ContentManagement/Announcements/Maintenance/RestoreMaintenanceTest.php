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
 * Test suite for POST /api/announcements/{id}/restore
 *
 * Verifies:
 * - State transitions: ARCHIVED → DRAFT
 * - Cannot restore non-archived announcements (DRAFT, PUBLISHED)
 * - Clears published_at timestamp on restore
 * - Preserves original content and metadata (urgency, scheduled_start, scheduled_end)
 * - Restored announcements can be edited again
 * - Permission checks (company admin ownership, END_USER cannot restore)
 */
class RestoreMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function company_admin_can_restore_archived_maintenance(): void
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
            'title' => 'Archived Maintenance',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/restore");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $announcement->id,
                    'status' => 'DRAFT',
                ],
            ]);

        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'status' => PublicationStatus::DRAFT->value,
            'published_at' => null,
        ]);

        $announcement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
        $this->assertNull($announcement->published_at, 'published_at should be cleared on restore');
    }

    #[Test]
    public function cannot_restore_non_archived_maintenance(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        // Test DRAFT status
        $draftAnnouncement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::DRAFT,
            'published_at' => null,
            'title' => 'Draft Maintenance',
        ]);

        // Test PUBLISHED status
        $publishedAnnouncement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'published_at' => Carbon::now()->subHour(),
            'title' => 'Published Maintenance',
        ]);

        // Act & Assert - DRAFT
        $responseDraft = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$draftAnnouncement->id}/restore");

        $responseDraft->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Only archived announcements can be restored',
            ]);

        // Verify DRAFT status hasn't changed
        $draftAnnouncement->refresh();
        $this->assertEquals(PublicationStatus::DRAFT, $draftAnnouncement->status);

        // Act & Assert - PUBLISHED
        $responsePublished = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$publishedAnnouncement->id}/restore");

        $responsePublished->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Only archived announcements can be restored',
            ]);

        // Verify PUBLISHED status hasn't changed
        $publishedAnnouncement->refresh();
        $this->assertEquals(PublicationStatus::PUBLISHED, $publishedAnnouncement->status);
    }

    #[Test]
    public function restored_maintenance_keeps_original_content_and_metadata(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $scheduledStart = Carbon::now()->addDay()->startOfHour();
        $scheduledEnd = Carbon::now()->addDays(2)->startOfHour();

        $originalTitle = 'Emergency Database Maintenance';
        $originalContent = 'Our database will be undergoing critical maintenance. Please save your work.';
        $originalMetadata = [
            'urgency' => 'HIGH',
            'scheduled_start' => $scheduledStart->toIso8601String(),
            'scheduled_end' => $scheduledEnd->toIso8601String(),
            'affected_services' => ['database', 'api', 'web-app'],
            'contact_email' => 'ops@example.com',
        ];

        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::ARCHIVED,
            'published_at' => Carbon::now()->subWeek(),
            'title' => $originalTitle,
            'content' => $originalContent,
            'metadata' => $originalMetadata,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/restore");

        // Assert
        $response->assertStatus(200);

        $announcement->refresh();

        // Verify status changed to DRAFT
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
        $this->assertNull($announcement->published_at);

        // Verify content preserved
        $this->assertEquals($originalTitle, $announcement->title);
        $this->assertEquals($originalContent, $announcement->content);

        // Verify metadata preserved
        $this->assertNotNull($announcement->metadata);
        $this->assertEquals('HIGH', $announcement->metadata['urgency']);
        $this->assertEquals($scheduledStart->toIso8601String(), $announcement->metadata['scheduled_start']);
        $this->assertEquals($scheduledEnd->toIso8601String(), $announcement->metadata['scheduled_end']);
        $this->assertEquals(['database', 'api', 'web-app'], $announcement->metadata['affected_services']);
        $this->assertEquals('ops@example.com', $announcement->metadata['contact_email']);
    }

    #[Test]
    public function restored_maintenance_can_be_edited_again(): void
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
            'title' => 'Original Title',
            'content' => 'Original content',
            'metadata' => [
                'urgency' => 'MEDIUM',
            ],
        ]);

        // Act - Restore
        $restoreResponse = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$announcement->id}/restore");

        $restoreResponse->assertStatus(200);

        // Act - Edit after restore
        $updatedTitle = 'Updated Title After Restore';
        $updatedContent = 'Updated content after restoration';

        $updateResponse = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$announcement->id}", [
                'title' => $updatedTitle,
                'content' => $updatedContent,
                'metadata' => [
                    'urgency' => 'HIGH',
                ],
            ]);

        // Assert - Update successful
        $updateResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $announcement->id,
                    'title' => $updatedTitle,
                    'content' => $updatedContent,
                    'status' => 'DRAFT',
                ],
            ]);

        // Verify database updated
        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'title' => $updatedTitle,
            'content' => $updatedContent,
            'status' => PublicationStatus::DRAFT->value,
        ]);

        // Verify metadata updated
        $announcement->refresh();
        $this->assertEquals('HIGH', $announcement->metadata['urgency']);
    }

    #[Test]
    public function end_user_cannot_restore(): void
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
            'status' => PublicationStatus::ARCHIVED,
            'published_at' => Carbon::now()->subWeek(),
        ]);

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson("/api/announcements/{$announcement->id}/restore");

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Insufficient permissions',
            ]);

        // Verify status hasn't changed
        $announcement->refresh();
        $this->assertEquals(PublicationStatus::ARCHIVED, $announcement->status);
        $this->assertNotNull($announcement->published_at);
    }
}
