<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Maintenance;

use App\Features\Authentication\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\Announcement;
use App\Shared\Enums\AnnouncementStatus;
use App\Shared\Enums\AnnouncementType;
use App\Shared\Enums\Role as RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UnscheduleMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $companyAdmin;
    private User $endUser;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create company
        $this->company = Company::factory()->create([
            'status' => 'active',
        ]);

        // Create company admin
        $this->companyAdmin = User::factory()->create([
            'status' => 'active',
        ]);
        $this->companyAdmin->userRoles()->create([
            'role_code' => RoleEnum::COMPANY_ADMIN->value,
            'company_id' => $this->company->id,
        ]);
        $this->company->update(['admin_user_id' => $this->companyAdmin->id]);

        // Create end user
        $this->endUser = User::factory()->create([
            'status' => 'active',
        ]);
        $this->endUser->userRoles()->create([
            'role_code' => RoleEnum::END_USER->value,
            'company_id' => $this->company->id,
        ]);
    }

    #[Test]
    public function company_admin_can_unschedule_maintenance(): void
    {
        // Arrange: Create a scheduled maintenance announcement
        $scheduledFor = now()->addDays(2);
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'author_id' => $this->companyAdmin->id,
            'type' => AnnouncementType::MAINTENANCE->value,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'title' => 'Scheduled Maintenance',
            'content' => 'This is scheduled maintenance',
            'metadata' => [
                'scheduled_for' => $scheduledFor->toISOString(),
                'maintenance' => [
                    'start_date' => now()->addDays(2)->toISOString(),
                    'end_date' => now()->addDays(2)->addHours(4)->toISOString(),
                    'affected_services' => ['API', 'Web Portal'],
                    'impact_level' => 'high',
                ],
            ],
        ]);

        // Act: Unschedule the announcement
        $response = $this->actingAs($this->companyAdmin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert: Response is successful
        $response->assertOk()
            ->assertJson([
                'message' => 'Announcement unscheduled successfully',
                'data' => [
                    'id' => $announcement->id,
                    'status' => AnnouncementStatus::DRAFT->value,
                ],
            ]);

        // Assert: Database status changed to DRAFT
        $this->assertDatabaseHas('business.announcements', [
            'id' => $announcement->id,
            'status' => AnnouncementStatus::DRAFT->value,
        ]);

        // Assert: Status actually changed from SCHEDULED to DRAFT
        $announcement->refresh();
        $this->assertEquals(AnnouncementStatus::DRAFT, $announcement->status);
    }

    #[Test]
    public function unscheduling_removes_scheduled_for_from_metadata(): void
    {
        // Arrange: Create scheduled announcement with scheduled_for in metadata
        $scheduledFor = now()->addDays(3);
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'author_id' => $this->companyAdmin->id,
            'type' => AnnouncementType::MAINTENANCE->value,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'title' => 'Scheduled Maintenance',
            'content' => 'Maintenance content',
            'metadata' => [
                'scheduled_for' => $scheduledFor->toISOString(),
                'maintenance' => [
                    'start_date' => now()->addDays(3)->toISOString(),
                    'end_date' => now()->addDays(3)->addHours(2)->toISOString(),
                    'affected_services' => ['Database'],
                    'impact_level' => 'medium',
                ],
            ],
        ]);

        // Act: Unschedule the announcement
        $response = $this->actingAs($this->companyAdmin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert: Response successful
        $response->assertOk();

        // Assert: scheduled_for removed from metadata
        $announcement->refresh();
        $this->assertArrayNotHasKey('scheduled_for', $announcement->metadata);

        // Assert: Other metadata (maintenance details) preserved
        $this->assertArrayHasKey('maintenance', $announcement->metadata);
        $this->assertEquals('Database', $announcement->metadata['maintenance']['affected_services'][0]);
        $this->assertEquals('medium', $announcement->metadata['maintenance']['impact_level']);
    }

    #[Test]
    public function unscheduling_cancels_job_in_redis(): void
    {
        // Arrange: Create scheduled announcement
        $scheduledFor = now()->addDays(1);
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'author_id' => $this->companyAdmin->id,
            'type' => AnnouncementType::MAINTENANCE->value,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'title' => 'Scheduled Maintenance',
            'content' => 'Maintenance with scheduled job',
            'metadata' => [
                'scheduled_for' => $scheduledFor->toISOString(),
                'job_id' => 'test-job-id-12345', // Simulated job ID
                'maintenance' => [
                    'start_date' => now()->addDays(1)->toISOString(),
                    'end_date' => now()->addDays(1)->addHours(3)->toISOString(),
                    'affected_services' => ['All Services'],
                    'impact_level' => 'critical',
                ],
            ],
        ]);

        // NOTE: In a real implementation, the AnnouncementService would:
        // 1. Retrieve the job_id from metadata
        // 2. Call Queue::deleteScheduled($jobId) or similar
        // 3. Remove the PublishAnnouncementJob from Redis delayed queue
        // This test verifies the endpoint works; actual Redis job cancellation
        // would be tested in Unit tests for AnnouncementService

        // Act: Unschedule the announcement
        $response = $this->actingAs($this->companyAdmin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert: Response successful
        $response->assertOk();

        // Assert: job_id removed from metadata (indicating job was cancelled)
        $announcement->refresh();
        $this->assertArrayNotHasKey('job_id', $announcement->metadata);
        $this->assertArrayNotHasKey('scheduled_for', $announcement->metadata);

        // Assert: Status changed to DRAFT
        $this->assertEquals(AnnouncementStatus::DRAFT, $announcement->status);

        // NOTE: Actual verification that PublishAnnouncementJob was removed from
        // Redis queue would require mocking Queue facade or checking Redis directly.
        // This is better suited for Unit tests of AnnouncementService->unschedule()
    }

    #[Test]
    public function cannot_unschedule_non_scheduled_maintenance(): void
    {
        // Arrange: Create DRAFT announcement (not scheduled)
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'author_id' => $this->companyAdmin->id,
            'type' => AnnouncementType::MAINTENANCE->value,
            'status' => AnnouncementStatus::DRAFT->value,
            'title' => 'Draft Maintenance',
            'content' => 'This is a draft, not scheduled',
            'metadata' => [
                'maintenance' => [
                    'start_date' => now()->addDays(5)->toISOString(),
                    'end_date' => now()->addDays(5)->addHours(2)->toISOString(),
                    'affected_services' => ['API'],
                    'impact_level' => 'low',
                ],
            ],
        ]);

        // Act: Try to unschedule a DRAFT announcement
        $response = $this->actingAs($this->companyAdmin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert: Bad Request with appropriate message
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Announcement is not scheduled',
            ]);

        // Assert: Status unchanged in database
        $announcement->refresh();
        $this->assertEquals(AnnouncementStatus::DRAFT, $announcement->status);
    }

    #[Test]
    public function cannot_unschedule_published_maintenance(): void
    {
        // Arrange: Create PUBLISHED announcement (already published, cannot unschedule)
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'author_id' => $this->companyAdmin->id,
            'type' => AnnouncementType::MAINTENANCE->value,
            'status' => AnnouncementStatus::PUBLISHED->value,
            'title' => 'Published Maintenance',
            'content' => 'This maintenance is already published',
            'published_at' => now()->subHour(),
            'metadata' => [
                'maintenance' => [
                    'start_date' => now()->addHours(1)->toISOString(),
                    'end_date' => now()->addHours(5)->toISOString(),
                    'affected_services' => ['Web Portal', 'Mobile App'],
                    'impact_level' => 'high',
                ],
            ],
        ]);

        // Act: Try to unschedule a PUBLISHED announcement
        $response = $this->actingAs($this->companyAdmin)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert: Bad Request with appropriate message
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cannot unschedule published announcement',
            ]);

        // Assert: Status unchanged in database
        $announcement->refresh();
        $this->assertEquals(AnnouncementStatus::PUBLISHED, $announcement->status);
        $this->assertNotNull($announcement->published_at);
    }

    #[Test]
    public function end_user_cannot_unschedule(): void
    {
        // Arrange: Create scheduled announcement
        $scheduledFor = now()->addDays(7);
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'author_id' => $this->companyAdmin->id,
            'type' => AnnouncementType::MAINTENANCE->value,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'title' => 'Scheduled Maintenance',
            'content' => 'End user should not be able to unschedule this',
            'metadata' => [
                'scheduled_for' => $scheduledFor->toISOString(),
                'maintenance' => [
                    'start_date' => now()->addDays(7)->toISOString(),
                    'end_date' => now()->addDays(7)->addHours(6)->toISOString(),
                    'affected_services' => ['All Services'],
                    'impact_level' => 'critical',
                ],
            ],
        ]);

        // Act: Try to unschedule as END_USER
        $response = $this->actingAs($this->endUser)
            ->postJson("/api/announcements/{$announcement->id}/unschedule");

        // Assert: Forbidden
        $response->assertForbidden();

        // Assert: Status unchanged in database
        $announcement->refresh();
        $this->assertEquals(AnnouncementStatus::SCHEDULED, $announcement->status);
        $this->assertArrayHasKey('scheduled_for', $announcement->metadata);
    }
}
