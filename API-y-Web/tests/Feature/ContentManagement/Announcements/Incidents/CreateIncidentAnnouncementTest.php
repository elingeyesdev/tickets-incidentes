<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Incidents;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating Incident Announcements
 *
 * Tests the endpoint POST /api/v1/announcements/incidents
 *
 * Coverage:
 * - Authorization (COMPANY_ADMIN, AGENT, END_USER)
 * - Creating drafts, publishing, and scheduling (unusual for incidents)
 * - Field validation (required fields, enums, date ranges, resolution data)
 * - Company ID inference from JWT token
 * - Incident-specific metadata validation (urgency includes CRITICAL, resolution fields)
 */
class CreateIncidentAnnouncementTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function company_admin_can_create_incident_as_draft(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Database Connection Issue',
            'content' => 'We are experiencing intermittent database connection issues.',
            'urgency' => 'HIGH',
            'is_resolved' => false,
            'started_at' => now()->subHours(2)->toIso8601String(),
            'affected_services' => ['database', 'api'],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.title', 'Database Connection Issue')
            ->assertJsonPath('data.type', 'INCIDENT')
            ->assertJsonMissing(['data.published_at']);

        $this->assertDatabaseHas('company_announcements', [
            'title' => 'Database Connection Issue',
            'type' => 'INCIDENT',
            'status' => 'DRAFT',
            'author_id' => $admin->id,
        ]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function company_admin_can_create_and_publish_incident_immediately(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Critical Payment Gateway Outage',
            'content' => 'Payment processing is currently unavailable due to gateway failure.',
            'urgency' => 'CRITICAL',
            'is_resolved' => false,
            'started_at' => now()->subMinutes(30)->toIso8601String(),
            'affected_services' => ['payment', 'billing'],
            'action' => 'publish',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'PUBLISHED')
            ->assertJsonPath('data.title', 'Critical Payment Gateway Outage');

        $announcement = Announcement::where('title', 'Critical Payment Gateway Outage')->first();

        $this->assertNotNull($announcement);
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
        $this->assertNotNull($announcement->published_at);
        $this->assertEquals('CRITICAL', $announcement->metadata['urgency']);
        $this->assertFalse($announcement->metadata['is_resolved']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function validates_required_fields_for_incident(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $testCases = [
            'title' => ['content', 'urgency', 'is_resolved', 'started_at'],
            'content' => ['title', 'urgency', 'is_resolved', 'started_at'],
            'urgency' => ['title', 'content', 'is_resolved', 'started_at'],
            'is_resolved' => ['title', 'content', 'urgency', 'started_at'],
            'started_at' => ['title', 'content', 'urgency', 'is_resolved'],
        ];

        foreach ($testCases as $missingField => $includedFields) {
            $payload = [
                'title' => 'Test Incident',
                'content' => 'Test content',
                'urgency' => 'HIGH',
                'is_resolved' => false,
                'started_at' => now()->subHours(1)->toIso8601String(),
            ];

            unset($payload[$missingField]);

            // Act
            $response = $this->authenticateWithJWT($admin)
                ->postJson('/api/announcements/incidents', $payload);

            // Assert
            $response->assertStatus(422)
                ->assertJsonValidationErrors($missingField);
        }
    }

    #[Test]
    public function validates_urgency_includes_critical_for_incidents(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Test CRITICAL urgency is allowed for incidents
        $criticalPayload = [
            'title' => 'Critical Incident',
            'content' => 'This is a critical incident.',
            'urgency' => 'CRITICAL',
            'is_resolved' => false,
            'started_at' => now()->subHours(1)->toIso8601String(),
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $criticalPayload);

        $response->assertStatus(201);

        // Test invalid urgency value
        $invalidPayload = [
            'title' => 'Test Incident',
            'content' => 'Test content',
            'urgency' => 'INVALID_URGENCY',
            'is_resolved' => false,
            'started_at' => now()->subHours(1)->toIso8601String(),
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $invalidPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('urgency');
    }

    #[Test]
    public function validates_is_resolved_is_boolean(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Test non-boolean value for is_resolved
        $payload = [
            'title' => 'Test Incident',
            'content' => 'Test content',
            'urgency' => 'HIGH',
            'is_resolved' => 'not-a-boolean',
            'started_at' => now()->subHours(1)->toIso8601String(),
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('is_resolved');
    }

    #[Test]
    public function validates_resolved_at_required_when_is_resolved_true(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Test missing resolved_at when is_resolved is true
        $payload = [
            'title' => 'Resolved Incident',
            'content' => 'This incident has been resolved.',
            'urgency' => 'HIGH',
            'is_resolved' => true,
            'started_at' => now()->subHours(2)->toIso8601String(),
            'resolution_content' => 'The issue was fixed by restarting the service.',
            // Missing resolved_at
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('resolved_at');
    }

    #[Test]
    public function validates_resolution_content_required_when_is_resolved_true(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Test missing resolution_content when is_resolved is true
        $payload = [
            'title' => 'Resolved Incident',
            'content' => 'This incident has been resolved.',
            'urgency' => 'HIGH',
            'is_resolved' => true,
            'started_at' => now()->subHours(2)->toIso8601String(),
            'resolved_at' => now()->toIso8601String(),
            // Missing resolution_content
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('resolution_content');
    }

    #[Test]
    public function validates_ended_at_is_after_started_at(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $startedAt = now()->subHours(2);
        $payload = [
            'title' => 'Invalid Timeframe Incident',
            'content' => 'This should fail validation.',
            'urgency' => 'MEDIUM',
            'is_resolved' => false,
            'started_at' => $startedAt->toIso8601String(),
            'ended_at' => $startedAt->subHour()->toIso8601String(), // End before start
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('ended_at');
    }

    #[Test]
    public function can_create_unresolved_incident_without_resolution_fields(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Ongoing Service Degradation',
            'content' => 'We are investigating slow response times.',
            'urgency' => 'MEDIUM',
            'is_resolved' => false,
            'started_at' => now()->subHours(1)->toIso8601String(),
            'affected_services' => ['api'],
            // No resolved_at, resolution_content, or ended_at
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(201);

        $announcement = Announcement::where('title', 'Ongoing Service Degradation')->first();

        $this->assertNotNull($announcement);
        $this->assertFalse($announcement->metadata['is_resolved']);
        $this->assertNull($announcement->metadata['resolved_at'] ?? null);
        $this->assertNull($announcement->metadata['resolution_content'] ?? null);
        $this->assertNull($announcement->metadata['ended_at'] ?? null);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function creating_resolved_incident_includes_all_resolution_data(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $startedAt = now()->subHours(3);
        $resolvedAt = now()->subMinutes(10);
        $endedAt = now()->subMinutes(15);

        $payload = [
            'title' => 'Email Service Outage - Resolved',
            'content' => 'Email delivery was interrupted for 2 hours.',
            'urgency' => 'HIGH',
            'is_resolved' => true,
            'started_at' => $startedAt->toIso8601String(),
            'ended_at' => $endedAt->toIso8601String(),
            'resolved_at' => $resolvedAt->toIso8601String(),
            'resolution_content' => 'The issue was resolved by restarting the email queue workers.',
            'affected_services' => ['email', 'notifications'],
            'action' => 'publish',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(201);

        $announcement = Announcement::where('title', 'Email Service Outage - Resolved')->first();

        $this->assertNotNull($announcement);
        $this->assertTrue($announcement->metadata['is_resolved']);
        $this->assertNotNull($announcement->metadata['resolved_at']);
        $this->assertEquals('The issue was resolved by restarting the email queue workers.', $announcement->metadata['resolution_content']);
        $this->assertNotNull($announcement->metadata['ended_at']);
        $this->assertEquals(['email', 'notifications'], $announcement->metadata['affected_services']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function incidents_can_be_scheduled_but_unusual(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $scheduledFor = now()->addMinutes(10);
        $payload = [
            'title' => 'Scheduled Incident Notification',
            'content' => 'We will notify about the incident at a specific time.',
            'urgency' => 'LOW',
            'is_resolved' => true,
            'started_at' => now()->subDays(1)->toIso8601String(),
            'ended_at' => now()->subHours(12)->toIso8601String(),
            'resolved_at' => now()->subHours(12)->toIso8601String(),
            'resolution_content' => 'Issue was resolved yesterday.',
            'action' => 'schedule',
            'scheduled_for' => $scheduledFor->toIso8601String(),
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'SCHEDULED')
            ->assertJsonPath('data.title', 'Scheduled Incident Notification');

        $announcement = Announcement::where('title', 'Scheduled Incident Notification')->first();

        $this->assertNotNull($announcement);
        $this->assertEquals(PublicationStatus::SCHEDULED, $announcement->status);
        $this->assertArrayHasKey('scheduled_for', $announcement->metadata);

        Queue::assertPushed(\App\Features\ContentManagement\Jobs\PublishAnnouncementJob::class);
    }

    #[Test]
    public function end_user_cannot_create_incident(): void
    {
        // Arrange
        $endUser = $this->createEndUser();

        $payload = [
            'title' => 'Unauthorized Incident',
            'content' => 'This should not be allowed.',
            'urgency' => 'HIGH',
            'is_resolved' => false,
            'started_at' => now()->subHours(1)->toIso8601String(),
        ];

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Insufficient permissions']);

        $this->assertDatabaseMissing('company_announcements', [
            'title' => 'Unauthorized Incident',
        ]);
    }

    // ==================== Helper Methods ====================

    /**
     * Create an end user (USER role).
     */
    private function createEndUser(): User
    {
        return User::factory()->withRole('USER')->create();
    }
}
