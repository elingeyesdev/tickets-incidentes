<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Incidents;

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
 * Test suite for PUT /api/announcements/{id} - Update Incident Announcements
 *
 * Verifies:
 * - COMPANY_ADMIN can update DRAFT and SCHEDULED incident announcements
 * - Cannot update PUBLISHED or ARCHIVED announcements
 * - Validation rules are enforced (ended_at > started_at, resolution logic)
 * - Cross-company protection (admin from company B cannot update company A incident)
 * - Partial updates preserve unchanged fields (metadata merging)
 * - Immutable fields (type, company_id) cannot be changed
 * - Once is_resolved=true, cannot change to is_resolved=false (irreversible)
 * - Can update resolution_content and urgency even after resolved
 * - PLATFORM_ADMIN has read-only access (cannot update)
 */
class UpdateIncidentTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function can_update_unresolved_incident_details(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $incident = $this->createIncidentAnnouncementViaHttp($admin, [
            'title' => 'Original Incident Title',
            'content' => 'Original incident content.',
            'urgency' => 'LOW',
            'is_resolved' => false,
            'started_at' => now()->subHours(2)->toIso8601String(),
            'affected_services' => ['dashboard'],
        ], 'draft');

        $updateData = [
            'title' => 'Updated Incident Title',
            'content' => 'Updated incident description with more details.',
            'urgency' => 'HIGH',
            'affected_services' => ['api', 'dashboard', 'reports'],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $incident->id,
                    'title' => 'Updated Incident Title',
                    'type' => 'INCIDENT',
                    'status' => 'DRAFT',
                ],
            ]);

        $this->assertDatabaseHas('company_announcements', [
            'id' => $incident->id,
            'title' => 'Updated Incident Title',
            'content' => 'Updated incident description with more details.',
            'status' => PublicationStatus::DRAFT->value,
        ]);

        $incident->refresh();
        $this->assertEquals('HIGH', $incident->metadata['urgency']);
        $this->assertEquals(['api', 'dashboard', 'reports'], $incident->metadata['affected_services']);
        $this->assertFalse($incident->metadata['is_resolved']);
    }

    #[Test]
    public function can_update_resolved_incident_to_add_more_info(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create a resolved incident
        $incident = $this->createIncidentAnnouncementViaHttp($admin, [
            'title' => 'Database Outage',
            'content' => 'Database is currently unavailable.',
            'urgency' => 'CRITICAL',
            'is_resolved' => true,
            'started_at' => now()->subHours(5)->toIso8601String(),
            'ended_at' => now()->subHours(1)->toIso8601String(),
            'resolved_at' => now()->subHours(1)->toIso8601String(),
            'resolution_content' => 'Initial fix applied.',
        ], 'draft');

        $this->assertTrue($incident->metadata['is_resolved']);

        $updateData = [
            'resolution_content' => 'Initial fix applied. Root cause analysis completed: disk space issue resolved and monitoring alerts configured.',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(200);

        $incident->refresh();

        // Verify resolution_content was updated
        $this->assertStringContainsString('Root cause analysis completed', $incident->metadata['resolution_content']);
        $this->assertStringContainsString('monitoring alerts configured', $incident->metadata['resolution_content']);

        // Verify is_resolved is still true
        $this->assertTrue($incident->metadata['is_resolved']);
    }

    #[Test]
    public function cannot_change_is_resolved_from_true_to_false(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create a resolved incident
        $incident = $this->createIncidentAnnouncementViaHttp($admin, [
            'title' => 'API Rate Limiting Issue',
            'content' => 'Rate limits were incorrectly configured.',
            'urgency' => 'HIGH',
            'is_resolved' => true,
            'started_at' => now()->subHours(3)->toIso8601String(),
            'ended_at' => now()->subHours(1)->toIso8601String(),
            'resolved_at' => now()->subHours(1)->toIso8601String(),
            'resolution_content' => 'Rate limits have been corrected.',
        ], 'draft');

        $this->assertTrue($incident->metadata['is_resolved']);

        // Try to change is_resolved back to false
        $updateData = [
            'is_resolved' => false,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('is_resolved')
            ->assertJsonFragment([
                'is_resolved' => ['Cannot change resolved status back to unresolved'],
            ]);

        // Verify is_resolved is still true
        $incident->refresh();
        $this->assertTrue($incident->metadata['is_resolved']);
    }

    #[Test]
    public function updating_urgency_from_critical_to_low_after_resolution(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create a resolved incident with CRITICAL urgency
        $incident = $this->createIncidentAnnouncementViaHttp($admin, [
            'title' => 'Authentication Service Down',
            'content' => 'Users unable to log in.',
            'urgency' => 'CRITICAL',
            'is_resolved' => true,
            'started_at' => now()->subHours(4)->toIso8601String(),
            'ended_at' => now()->subHours(2)->toIso8601String(),
            'resolved_at' => now()->subHours(2)->toIso8601String(),
            'resolution_content' => 'Authentication service restored.',
        ], 'draft');

        $this->assertEquals('CRITICAL', $incident->metadata['urgency']);
        $this->assertTrue($incident->metadata['is_resolved']);

        // Update urgency to LOW after resolution (business decision to downgrade severity in retrospect)
        $updateData = [
            'urgency' => 'LOW',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(200);

        $incident->refresh();

        // Verify urgency was updated
        $this->assertEquals('LOW', $incident->metadata['urgency']);

        // Verify is_resolved is still true
        $this->assertTrue($incident->metadata['is_resolved']);
    }

    #[Test]
    public function validates_ended_at_not_before_started_at_on_update(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $startedAt = Carbon::now()->subHours(5);
        $incident = $this->createIncidentAnnouncementViaHttp($admin, [
            'title' => 'File Storage Unavailable',
            'content' => 'File storage service is down.',
            'urgency' => 'HIGH',
            'is_resolved' => false,
            'started_at' => $startedAt->toIso8601String(),
        ], 'draft');

        // Try to update with ended_at before started_at
        $invalidUpdateData = [
            'ended_at' => $startedAt->subHour()->toIso8601String(), // 1 hour before start
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $invalidUpdateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ended_at']);

        $errorMessage = $response->json('errors.ended_at.0');
        $this->assertStringContainsString('after', strtolower($errorMessage));
    }

    #[Test]
    public function partial_update_of_incident_metadata(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $incident = $this->createIncidentAnnouncementViaHttp($admin, [
            'title' => 'Email Service Degraded',
            'content' => 'Email delivery is delayed.',
            'urgency' => 'MEDIUM',
            'is_resolved' => false,
            'started_at' => now()->subHours(3)->toIso8601String(),
            'affected_services' => ['email', 'notifications'],
        ], 'draft');

        $originalMetadata = $incident->metadata;

        // Update only affected_services (partial update)
        $updateData = [
            'affected_services' => ['email', 'notifications', 'alerts'],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(200);

        $incident->refresh();

        // affected_services changed
        $this->assertEquals(['email', 'notifications', 'alerts'], $incident->metadata['affected_services']);

        // Other metadata fields unchanged
        $this->assertEquals($originalMetadata['urgency'], $incident->metadata['urgency']);
        $this->assertEquals($originalMetadata['is_resolved'], $incident->metadata['is_resolved']);
        $this->assertEquals($originalMetadata['started_at'], $incident->metadata['started_at']);
    }

    /**
     * OPTIONAL TEST - Future Feature
     *
     * This test represents a future business requirement where PUBLISHED incidents
     * cannot be updated (title, content) after 24 hours to prevent historical data
     * manipulation. This is not currently implemented in the controller.
     *
     * When this feature is implemented:
     * 1. Check if incident is PUBLISHED
     * 2. Check if published_at is more than 24 hours ago
     * 3. If both true, only allow updating resolution_content, urgency, affected_services
     * 4. Block updates to title, content
     */
    #[Test]
    public function cannot_update_published_incident_basic_info_after_24_hours(): void
    {
        $this->markTestSkipped('Future feature: Block updates to published incidents after 24 hours');

        // FUTURE IMPLEMENTATION:
        // $admin = $this->createCompanyAdmin();
        //
        // $incident = Announcement::factory()
        //     ->incident()
        //     ->published()
        //     ->create([
        //         'company_id' => $admin->companies()->first()->id,
        //         'author_id' => $admin->id,
        //         'published_at' => now()->subHours(25), // More than 24 hours ago
        //     ]);
        //
        // $updateData = [
        //     'title' => 'Trying to change title after 24 hours',
        // ];
        //
        // $response = $this->authenticateWithJWT($admin)
        //     ->putJson("/api/announcements/{$incident->id}", $updateData);
        //
        // $response->assertStatus(403)
        //     ->assertJson([
        //         'message' => 'Cannot update published incident after 24 hours',
        //     ]);
    }

    #[Test]
    public function company_admin_from_different_company_cannot_update_incident(): void
    {
        // Arrange
        $adminA = $this->createCompanyAdmin();
        $adminB = $this->createCompanyAdmin();

        // Incident belongs to company A
        $incident = $this->createIncidentAnnouncementViaHttp($adminA, [
            'title' => 'Company A Incident',
            'content' => 'This is company A\'s incident.',
            'urgency' => 'MEDIUM',
        ], 'draft');

        $updateData = [
            'title' => 'Unauthorized Update Attempt by Company B Admin',
        ];

        // Act - Admin B tries to update Company A's incident
        $response = $this->authenticateWithJWT($adminB)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions',
            ]);

        // Verify no changes were made
        $incident->refresh();
        $this->assertEquals('Company A Incident', $incident->title);
        $this->assertNotEquals('Unauthorized Update Attempt by Company B Admin', $incident->title);
    }

    #[Test]
    public function cannot_update_incident_in_published_status(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $incident = Announcement::factory()
            ->incident()
            ->published()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
            ]);

        $updateData = [
            'title' => 'Trying to Update Published Incident',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Cannot edit published announcement',
            ]);

        // Verify no changes were made
        $incident->refresh();
        $this->assertNotEquals('Trying to Update Published Incident', $incident->title);
    }

    #[Test]
    public function cannot_update_incident_in_archived_status(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $incident = Announcement::factory()
            ->incident()
            ->archived()
            ->create([
                'company_id' => $company->id,
                'author_id' => $admin->id,
            ]);

        $updateData = [
            'title' => 'Trying to Update Archived Incident',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Cannot edit archived announcement',
            ]);

        // Verify no changes were made
        $incident->refresh();
        $this->assertNotEquals('Trying to Update Archived Incident', $incident->title);
    }

    #[Test]
    public function company_admin_can_update_incident_in_scheduled_status(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Create draft incident and then schedule it
        $incident = $this->createIncidentAnnouncementViaHttp($admin, [
            'title' => 'Scheduled Maintenance Window',
            'urgency' => 'MEDIUM',
        ], 'draft');

        $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/{$incident->id}/schedule", [
                'scheduled_for' => now()->addDay()->toIso8601String(),
            ]);

        $incident->refresh();

        $updateData = [
            'title' => 'Updated Scheduled Incident Title',
            'urgency' => 'HIGH',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated Scheduled Incident Title',
                    'status' => 'SCHEDULED',
                ],
            ]);

        $incident->refresh();
        $this->assertEquals(PublicationStatus::SCHEDULED, $incident->status);
        $this->assertEquals('HIGH', $incident->metadata['urgency']);
    }

    #[Test]
    public function update_does_not_change_type_or_company_id(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $otherCompany = Company::factory()->create();

        $incident = $this->createIncidentAnnouncementViaHttp($admin, [
            'title' => 'Original Incident',
        ], 'draft');

        $originalCompanyId = $incident->company_id;
        $originalType = $incident->type;

        // Try to change immutable fields
        $maliciousUpdateData = [
            'title' => 'Legitimate Update',
            'type' => 'MAINTENANCE', // Should be ignored
            'company_id' => $otherCompany->id, // Should be ignored
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$incident->id}", $maliciousUpdateData);

        // Assert
        $response->assertStatus(200);

        $incident->refresh();

        // Title updated
        $this->assertEquals('Legitimate Update', $incident->title);

        // Immutable fields unchanged
        $this->assertEquals($originalCompanyId, $incident->company_id);
        $this->assertEquals($originalType, $incident->type);
        $this->assertEquals(AnnouncementType::INCIDENT, $incident->type);
    }

    #[Test]
    public function platform_admin_cannot_update_incidents(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $companyAdmin = $this->createCompanyAdmin();

        $incident = $this->createIncidentAnnouncementViaHttp($companyAdmin, [
            'title' => 'Company Incident',
        ], 'draft');

        $updateData = [
            'title' => 'Platform Admin Trying to Update',
        ];

        // Act - Platform admin tries to update (read-only role)
        $response = $this->authenticateWithJWT($platformAdmin)
            ->putJson("/api/announcements/{$incident->id}", $updateData);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions',
            ]);

        // Verify no changes were made
        $incident->refresh();
        $this->assertEquals('Company Incident', $incident->title);
        $this->assertNotEquals('Platform Admin Trying to Update', $incident->title);
    }

    // ==================== Helper Methods ====================

    /**
     * Create an incident announcement via HTTP POST endpoint
     *
     * Uses HTTP POST to create announcements, which ensures proper transaction
     * handling with RefreshDatabaseWithoutTransactions trait. This is the correct
     * approach for testing because:
     * 1. It tests the actual HTTP flow (like production)
     * 2. It avoids RefreshDatabase transaction isolation issues
     * 3. All subsequent route model binding works correctly
     *
     * @param User $user The authenticated user creating the announcement
     * @param array $overrides Override default payload values
     * @param string $action 'draft', 'publish', or 'schedule'
     * @param string|null $scheduledFor ISO8601 datetime for scheduled publication
     * @return Announcement The created announcement
     */
    protected function createIncidentAnnouncementViaHttp(
        User $user,
        array $overrides = [],
        string $action = 'draft',
        ?string $scheduledFor = null
    ): Announcement {
        // Build payload with defaults
        $payload = array_merge([
            'title' => 'Test Incident',
            'content' => 'Test incident content',
            'urgency' => 'MEDIUM',
            'is_resolved' => false,
            'started_at' => now()->subHours(1)->toIso8601String(),
            'affected_services' => [],
        ], $overrides);

        // Add action if not draft
        if ($action !== 'draft') {
            $payload['action'] = $action;
        }

        // Add scheduled_for if provided and action is schedule
        if ($scheduledFor && $action === 'schedule') {
            $payload['scheduled_for'] = $scheduledFor;
        }

        // Make HTTP POST request
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert the request was successful
        if (!in_array($response->status(), [201])) {
            throw new \Exception(
                "Failed to create incident via HTTP. Status: {$response->status()}\n" .
                "Response: {$response->content()}"
            );
        }

        // Extract the ID from response
        $announcementId = $response->json('data.id');

        if (!$announcementId) {
            throw new \Exception(
                "No announcement ID in response.\n" .
                "Response: {$response->content()}"
            );
        }

        // Fetch the created announcement from database
        $announcement = Announcement::findOrFail($announcementId);

        return $announcement;
    }
}
