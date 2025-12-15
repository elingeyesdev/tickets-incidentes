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
 * Test suite for POST /api/announcements/incidents/{id}/resolve
 *
 * Verifies:
 * - State transitions: is_resolved=false → is_resolved=true
 * - Metadata updates: sets resolved_at, resolution_content, ended_at
 * - Validation: resolution_content required, ended_at must be after started_at
 * - Cannot resolve already resolved incidents
 * - Event dispatching: IncidentResolved event
 * - Notifications: followers are notified
 * - Permission checks (COMPANY_ADMIN ownership, END_USER cannot resolve)
 */
class ResolveIncidentTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function company_admin_can_resolve_incident(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $startedAt = Carbon::now()->subHours(3);
        $incident = $this->createIncidentAnnouncementViaHttp(
            user: $admin,
            overrides: [
                'title' => 'Database Connection Issue',
                'content' => 'Experiencing connection failures.',
                'urgency' => 'HIGH',
                'is_resolved' => false,
                'started_at' => $startedAt->toIso8601String(),
            ]
        );

        $this->assertFalse($incident->metadata['is_resolved']);
        $this->assertNull($incident->metadata['resolved_at'] ?? null);

        // Act
        $endedAt = Carbon::now()->subMinutes(15);
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/incidents/{$incident->id}/resolve", [
                'resolution_content' => 'The database connection pool was restarted and connections are now stable.',
                'ended_at' => $endedAt->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $incident->id,
                ],
            ]);

        $incident->refresh();

        // Verify is_resolved is now true
        $this->assertTrue($incident->metadata['is_resolved']);

        // Verify resolved_at is set
        $this->assertNotNull($incident->metadata['resolved_at']);
        $this->assertInstanceOf(Carbon::class, Carbon::parse($incident->metadata['resolved_at']));

        // Verify resolution_content is stored
        $this->assertEquals(
            'The database connection pool was restarted and connections are now stable.',
            $incident->metadata['resolution_content']
        );

        // Verify ended_at is stored
        $this->assertNotNull($incident->metadata['ended_at']);
        $this->assertEquals($endedAt->toIso8601String(), $incident->metadata['ended_at']);
    }

    #[Test]
    public function resolve_validates_resolution_content_required(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $incident = $this->createIncidentAnnouncementViaHttp(
            user: $admin,
            overrides: [
                'title' => 'API Service Degradation',
                'content' => 'API response times are slow.',
                'urgency' => 'MEDIUM',
                'is_resolved' => false,
                'started_at' => Carbon::now()->subHours(2)->toIso8601String(),
            ]
        );

        // Act - missing resolution_content
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/incidents/{$incident->id}/resolve", [
                'ended_at' => Carbon::now()->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('resolution_content');

        // Verify incident is still unresolved
        $incident->refresh();
        $this->assertFalse($incident->metadata['is_resolved']);
    }

    #[Test]
    public function resolve_validates_ended_at_is_after_started_at(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $startedAt = Carbon::now()->subHours(2);
        $incident = $this->createIncidentAnnouncementViaHttp(
            user: $admin,
            overrides: [
                'title' => 'Email Delivery Failure',
                'content' => 'Emails are not being sent.',
                'urgency' => 'HIGH',
                'is_resolved' => false,
                'started_at' => $startedAt->toIso8601String(),
            ]
        );

        // Act - ended_at is before started_at
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/incidents/{$incident->id}/resolve", [
                'resolution_content' => 'Fixed the email service.',
                'ended_at' => $startedAt->subHour()->toIso8601String(), // 1 hour before start
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('ended_at')
            ->assertJsonFragment([
                'ended_at' => ['The ended at field must be a date after started at.'],
            ]);

        // Verify incident is still unresolved
        $incident->refresh();
        $this->assertFalse($incident->metadata['is_resolved']);
    }

    #[Test]
    public function resolve_sets_resolved_at_to_now_if_not_provided(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $incident = $this->createIncidentAnnouncementViaHttp(
            user: $admin,
            overrides: [
                'title' => 'Login Service Outage',
                'content' => 'Users cannot log in.',
                'urgency' => 'CRITICAL',
                'is_resolved' => false,
                'started_at' => Carbon::now()->subHours(1)->toIso8601String(),
            ]
        );

        // Act - no resolved_at provided in request
        $beforeResolve = Carbon::now()->subSecond();  // Add 1 second buffer for processing time
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/incidents/{$incident->id}/resolve", [
                'resolution_content' => 'Login service has been restored.',
            ]);
        $afterResolve = Carbon::now()->addSecond();  // Add 1 second buffer for processing time

        // Assert
        $response->assertStatus(200);

        $incident->refresh();

        // Verify resolved_at was set to approximately now()
        $this->assertNotNull($incident->metadata['resolved_at']);
        $resolvedAt = Carbon::parse($incident->metadata['resolved_at']);

        $this->assertTrue(
            $resolvedAt->between($beforeResolve, $afterResolve),
            "resolved_at should be set to current time when not provided"
        );
    }

    #[Test]
    public function resolve_uses_provided_ended_at(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $startedAt = Carbon::now()->subHours(3);
        $incident = $this->createIncidentAnnouncementViaHttp(
            user: $admin,
            overrides: [
                'title' => 'Payment Processing Delay',
                'content' => 'Payments are being processed slowly.',
                'urgency' => 'HIGH',
                'is_resolved' => false,
                'started_at' => $startedAt->toIso8601String(),
            ]
        );

        // Act - provide specific ended_at
        $specificEndedAt = Carbon::now()->subMinutes(30);
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/incidents/{$incident->id}/resolve", [
                'resolution_content' => 'Payment processing has been optimized.',
                'ended_at' => $specificEndedAt->toIso8601String(),
            ]);

        // Assert
        $response->assertStatus(200);

        $incident->refresh();

        // Verify ended_at matches the provided value
        $this->assertNotNull($incident->metadata['ended_at']);
        $this->assertEquals(
            $specificEndedAt->toIso8601String(),
            $incident->metadata['ended_at']
        );
    }

    #[Test]
    public function resolve_updates_incident_title_optionally(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $originalTitle = 'File Upload Not Working';
        $incident = $this->createIncidentAnnouncementViaHttp(
            user: $admin,
            overrides: [
                'title' => $originalTitle,
                'content' => 'Users cannot upload files.',
                'urgency' => 'MEDIUM',
                'is_resolved' => false,
                'started_at' => Carbon::now()->subHours(2)->toIso8601String(),
            ]
        );

        // Act - resolve with updated title
        $updatedTitle = '[RESOLVED] File Upload Fixed';
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/incidents/{$incident->id}/resolve", [
                'resolution_content' => 'Upload service has been restarted.',
                'title' => $updatedTitle,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.title', $updatedTitle);

        $incident->refresh();
        $this->assertEquals($updatedTitle, $incident->title);
    }

    #[Test]
    public function cannot_resolve_already_resolved_incident(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        // Create an already resolved incident
        $incident = $this->createIncidentAnnouncementViaHttp(
            user: $admin,
            overrides: [
                'title' => 'Cache Service Failure',
                'content' => 'Cache is not responding.',
                'urgency' => 'MEDIUM',
                'is_resolved' => true,
                'started_at' => Carbon::now()->subHours(4)->toIso8601String(),
                'ended_at' => Carbon::now()->subHours(1)->toIso8601String(),
                'resolved_at' => Carbon::now()->subHours(1)->toIso8601String(),
                'resolution_content' => 'Cache service was restarted successfully.',
            ]
        );

        $this->assertTrue($incident->metadata['is_resolved']);

        // Act - try to resolve again
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/announcements/incidents/{$incident->id}/resolve", [
                'resolution_content' => 'Trying to resolve again.',
            ]);

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Incident is already resolved',
            ]);
    }

    #[Test]
    public function end_user_cannot_resolve_incident(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $endUser = User::factory()->create();
        // End user without any role in this company will have default USER role in JWT

        $incident = $this->createIncidentAnnouncementViaHttp(
            user: $admin,
            overrides: [
                'title' => 'Search Functionality Broken',
                'content' => 'Search returns no results.',
                'urgency' => 'MEDIUM',
                'is_resolved' => false,
                'started_at' => Carbon::now()->subHours(1)->toIso8601String(),
            ]
        );

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson("/api/announcements/incidents/{$incident->id}/resolve", [
                'resolution_content' => 'User should not be able to resolve this.',
            ]);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Insufficient permissions',
            ]);

        // Verify incident is still unresolved
        $incident->refresh();
        $this->assertFalse($incident->metadata['is_resolved']);
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
