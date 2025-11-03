<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Maintenance;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test suite for PUT/PATCH /api/v1/announcements/{id} - Update Maintenance Announcements
 *
 * Verifies:
 * - COMPANY_ADMIN can update DRAFT and SCHEDULED maintenance announcements
 * - Cannot update PUBLISHED or ARCHIVED announcements
 * - Validation rules are enforced (scheduled_end > scheduled_start)
 * - Cross-company protection (admin from company B cannot update company A announcement)
 * - Partial updates preserve unchanged fields
 * - Immutable fields (type, company_id) cannot be changed
 * - PLATFORM_ADMIN has read-only access (cannot update)
 * - Scheduled jobs are not rescheduled when updating non-scheduled fields
 */
class UpdateMaintenanceAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function company_admin_can_update_maintenance_in_draft_status(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()
            ->maintenance()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
                'status' => PublicationStatus::DRAFT,
                'title' => 'Original Title',
                'metadata' => [
                    'urgency' => 'LOW',
                    'scheduled_start' => now()->addDays(2)->toIso8601String(),
                    'scheduled_end' => now()->addDays(2)->addHours(4)->toIso8601String(),
                    'is_emergency' => false,
                    'affected_services' => ['dashboard'],
                ],
            ]);

        $updateData = [
            'title' => 'Updated Maintenance Title',
            'urgency' => 'HIGH',
            'affected_services' => ['api', 'reports'],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/announcements/{$announcement->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $announcement->id,
                    'title' => 'Updated Maintenance Title',
                    'type' => 'MAINTENANCE',
                    'status' => 'DRAFT',
                ],
            ]);

        $this->assertDatabaseHas('company_announcements', [
            'id' => $announcement->id,
            'title' => 'Updated Maintenance Title',
            'status' => PublicationStatus::DRAFT->value,
        ]);

        $announcement->refresh();
        $this->assertEquals('HIGH', $announcement->metadata['urgency']);
        $this->assertEquals(['api', 'reports'], $announcement->metadata['affected_services']);
    }

    #[Test]
    public function company_admin_can_update_maintenance_in_scheduled_status(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()
            ->maintenance()
            ->scheduled()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
                'title' => 'Scheduled Maintenance',
            ]);

        $updateData = [
            'title' => 'Updated Scheduled Maintenance',
            'urgency' => 'CRITICAL',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/announcements/{$announcement->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated Scheduled Maintenance',
                    'status' => 'SCHEDULED',
                ],
            ]);

        $announcement->refresh();
        $this->assertEquals(PublicationStatus::SCHEDULED, $announcement->status);
        $this->assertEquals('CRITICAL', $announcement->metadata['urgency']);
    }

    #[Test]
    public function cannot_update_maintenance_in_published_status(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()
            ->maintenance()
            ->published()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
            ]);

        $updateData = [
            'title' => 'Trying to Update Published',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/announcements/{$announcement->id}", $updateData);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Cannot edit published announcement',
            ]);

        // Verify no changes were made
        $announcement->refresh();
        $this->assertNotEquals('Trying to Update Published', $announcement->title);
    }

    #[Test]
    public function cannot_update_maintenance_in_archived_status(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()
            ->maintenance()
            ->archived()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
            ]);

        $updateData = [
            'title' => 'Trying to Update Archived',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/announcements/{$announcement->id}", $updateData);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Cannot edit archived announcement',
            ]);

        // Verify no changes were made
        $announcement->refresh();
        $this->assertNotEquals('Trying to Update Archived', $announcement->title);
    }

    #[Test]
    public function validates_updated_scheduled_end_is_after_scheduled_start(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $announcement = Announcement::factory()
            ->maintenance()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
                'status' => PublicationStatus::DRAFT,
            ]);

        $invalidUpdateData = [
            'scheduled_start' => now()->addDays(5)->toIso8601String(),
            'scheduled_end' => now()->addDays(3)->toIso8601String(), // Earlier than start
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/announcements/{$announcement->id}", $invalidUpdateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_end']);

        $errorMessage = $response->json('errors.scheduled_end.0');
        $this->assertStringContainsString('after', strtolower($errorMessage));
    }

    #[Test]
    public function company_admin_from_different_company_cannot_update(): void
    {
        // Arrange
        $adminA = User::factory()->create();
        $companyA = Company::factory()->create(['admin_user_id' => $adminA->id]);
        $adminA->assignRole('COMPANY_ADMIN', $companyA->id);

        $adminB = User::factory()->create();
        $companyB = Company::factory()->create(['admin_user_id' => $adminB->id]);
        $adminB->assignRole('COMPANY_ADMIN', $companyB->id);

        // Announcement belongs to company A
        $announcement = Announcement::factory()
            ->maintenance()
            ->create([
                'company_id' => $companyA->id,
                'author_id' => $adminA->id,
                'status' => PublicationStatus::DRAFT,
            ]);

        $updateData = [
            'title' => 'Unauthorized Update Attempt',
        ];

        // Act - Admin B tries to update Company A's announcement
        $response = $this->authenticateWithJWT($adminB)
            ->putJson("/api/v1/announcements/{$announcement->id}", $updateData);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This action is unauthorized',
            ]);

        // Verify no changes were made
        $announcement->refresh();
        $this->assertNotEquals('Unauthorized Update Attempt', $announcement->title);
    }

    #[Test]
    public function partial_update_preserves_unchanged_fields(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $originalMetadata = [
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(2)->toIso8601String(),
            'scheduled_end' => now()->addDays(2)->addHours(4)->toIso8601String(),
            'is_emergency' => false,
            'affected_services' => ['dashboard', 'reports'],
        ];

        $announcement = Announcement::factory()
            ->maintenance()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
                'status' => PublicationStatus::DRAFT,
                'title' => 'Original Title',
                'content' => 'Original Content',
                'metadata' => $originalMetadata,
            ]);

        // Update only title (partial update)
        $updateData = [
            'title' => 'Only Title Changed',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/announcements/{$announcement->id}", $updateData);

        // Assert
        $response->assertStatus(200);

        $announcement->refresh();

        // Title changed
        $this->assertEquals('Only Title Changed', $announcement->title);

        // Other fields unchanged
        $this->assertEquals('Original Content', $announcement->content);
        $this->assertEquals($originalMetadata['urgency'], $announcement->metadata['urgency']);
        $this->assertEquals($originalMetadata['affected_services'], $announcement->metadata['affected_services']);
        $this->assertEquals($originalMetadata['is_emergency'], $announcement->metadata['is_emergency']);
    }

    #[Test]
    public function update_does_not_change_type_or_company_id(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $otherCompany = Company::factory()->create();

        $announcement = Announcement::factory()
            ->maintenance()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
                'status' => PublicationStatus::DRAFT,
                'type' => AnnouncementType::MAINTENANCE,
            ]);

        $originalCompanyId = $announcement->company_id;
        $originalType = $announcement->type;

        // Try to change immutable fields
        $maliciousUpdateData = [
            'title' => 'Legitimate Update',
            'type' => 'INCIDENT', // Should be ignored
            'company_id' => $otherCompany->id, // Should be ignored
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/announcements/{$announcement->id}", $maliciousUpdateData);

        // Assert
        $response->assertStatus(200);

        $announcement->refresh();

        // Title updated
        $this->assertEquals('Legitimate Update', $announcement->title);

        // Immutable fields unchanged
        $this->assertEquals($originalCompanyId, $announcement->company_id);
        $this->assertEquals($originalType, $announcement->type);
        $this->assertEquals(AnnouncementType::MAINTENANCE, $announcement->type);
    }

    #[Test]
    public function updating_scheduled_maintenance_does_not_reschedule_job(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $scheduledFor = now()->addDays(3);

        $announcement = Announcement::factory()
            ->maintenance()
            ->scheduled()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
                'status' => PublicationStatus::SCHEDULED,
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => now()->addDays(2)->toIso8601String(),
                    'scheduled_end' => now()->addDays(2)->addHours(4)->toIso8601String(),
                    'is_emergency' => false,
                    'affected_services' => ['api'],
                    'scheduled_for' => $scheduledFor->toIso8601String(),
                ],
            ]);

        $originalScheduledFor = $announcement->metadata['scheduled_for'];

        // Update title only (not scheduled_for)
        $updateData = [
            'title' => 'Updated Title Without Rescheduling',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/v1/announcements/{$announcement->id}", $updateData);

        // Assert
        $response->assertStatus(200);

        $announcement->refresh();

        // Title updated
        $this->assertEquals('Updated Title Without Rescheduling', $announcement->title);

        // Scheduled_for unchanged (job not rescheduled)
        $this->assertEquals($originalScheduledFor, $announcement->metadata['scheduled_for']);

        // Status still SCHEDULED
        $this->assertEquals(PublicationStatus::SCHEDULED, $announcement->status);
    }

    #[Test]
    public function platform_admin_cannot_update_announcements(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $companyAdmin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $companyAdmin->id]);

        $announcement = Announcement::factory()
            ->maintenance()
            ->create([
                'company_id' => $company->id,
                'author_id' => $companyAdmin->id,
                'status' => PublicationStatus::DRAFT,
            ]);

        $updateData = [
            'title' => 'Platform Admin Trying to Update',
        ];

        // Act - Platform admin tries to update (read-only role)
        $response = $this->authenticateWithJWT($platformAdmin)
            ->putJson("/api/v1/announcements/{$announcement->id}", $updateData);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Platform administrators have read-only access to announcements',
            ]);

        // Verify no changes were made
        $announcement->refresh();
        $this->assertNotEquals('Platform Admin Trying to Update', $announcement->title);
    }
}
