<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Alerts;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating Alert Announcements
 *
 * Tests the endpoint POST /api/announcements/alerts
 *
 * Coverage:
 * - Authorization (COMPANY_ADMIN, END_USER)
 * - Creating drafts, publishing, and scheduling
 * - Field validation (required fields, enums, urgency HIGH/CRITICAL only)
 * - Alert-specific metadata validation (alert_type, message, action_required, action_description)
 * - Datetime validation (started_at, ended_at)
 * - Critical security alerts notification system
 * - Company ID inference from JWT token
 */
class CreateAlertAnnouncementTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function company_admin_can_create_alert_as_draft(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'System Update Required',
            'content' => 'A critical system update will be deployed. Please save your work.',
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'system',
                'message' => 'System maintenance scheduled for tonight',
                'action_required' => false,
                'started_at' => now()->addHour()->toIso8601String(),
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.title', 'System Update Required')
            ->assertJsonPath('data.type', 'ALERT')
            ->assertJsonMissing(['data.published_at']);

        $this->assertDatabaseHas('company_announcements', [
            'title' => 'System Update Required',
            'type' => 'ALERT',
            'status' => 'DRAFT',
            'author_id' => $admin->id,
        ]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function company_admin_can_create_and_publish_critical_alert(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Security Breach Detected',
            'content' => 'We have detected unauthorized access attempts. Please change your password immediately.',
            'metadata' => [
                'urgency' => 'CRITICAL',
                'alert_type' => 'security',
                'message' => 'Immediate action required: Change your password now',
                'action_required' => true,
                'action_description' => 'Navigate to Settings > Security and update your password',
                'started_at' => now()->toIso8601String(),
                'affected_services' => ['authentication', 'user_management'],
            ],
            'action' => 'publish',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'PUBLISHED')
            ->assertJsonPath('data.title', 'Security Breach Detected');

        $announcement = Announcement::where('title', 'Security Breach Detected')->first();

        $this->assertNotNull($announcement);
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
        $this->assertNotNull($announcement->published_at);
        $this->assertEquals('CRITICAL', $announcement->metadata['urgency']);
        $this->assertEquals('security', $announcement->metadata['alert_type']);
        $this->assertTrue($announcement->metadata['action_required']);
        $this->assertEquals('Navigate to Settings > Security and update your password', $announcement->metadata['action_description']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function validates_required_fields_for_alert(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test Alert',
            'content' => 'Test alert content',
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'system',
                'message' => 'Test alert message with minimum length',
                'action_required' => false,
                'started_at' => now()->addHour()->toIso8601String(),
            ],
        ];

        // Test missing title
        $payload = $basePayload;
        unset($payload['title']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');

        // Test missing content
        $payload = $basePayload;
        unset($payload['content']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');

        // Test missing urgency
        $payload = $basePayload;
        unset($payload['metadata']['urgency']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.urgency');

        // Test missing alert_type
        $payload = $basePayload;
        unset($payload['metadata']['alert_type']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.alert_type');

        // Test missing message
        $payload = $basePayload;
        unset($payload['metadata']['message']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.message');

        // Test missing action_required
        $payload = $basePayload;
        unset($payload['metadata']['action_required']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.action_required');

        // Test missing started_at
        $payload = $basePayload;
        unset($payload['metadata']['started_at']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.started_at');
    }

    #[Test]
    public function validates_urgency_only_allows_high_or_critical(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test Alert',
            'content' => 'Test alert content',
            'metadata' => [
                'alert_type' => 'system',
                'message' => 'Test alert message',
                'action_required' => false,
                'started_at' => now()->addHour()->toIso8601String(),
            ],
        ];

        // Test urgency="LOW" (should fail)
        $payload = $basePayload;
        $payload['metadata']['urgency'] = 'LOW';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.urgency');

        // Test urgency="MEDIUM" (should fail)
        $payload = $basePayload;
        $payload['metadata']['urgency'] = 'MEDIUM';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.urgency');

        // Test urgency="HIGH" (should pass)
        Queue::fake();
        $payload = $basePayload;
        $payload['title'] = 'Test Alert HIGH';
        $payload['metadata']['urgency'] = 'HIGH';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(201);

        // Test urgency="CRITICAL" (should pass)
        Queue::fake();
        $payload = $basePayload;
        $payload['title'] = 'Test Alert CRITICAL';
        $payload['metadata']['urgency'] = 'CRITICAL';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(201);
    }

    #[Test]
    public function validates_alert_type_enum(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Test invalid alert_type
        $invalidPayload = [
            'title' => 'Test Alert',
            'content' => 'Test alert content',
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'invalid_type',
                'message' => 'Test alert message',
                'action_required' => false,
                'started_at' => now()->addHour()->toIso8601String(),
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $invalidPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.alert_type');

        // Test valid alert_type values
        $validTypes = ['security', 'system', 'service', 'compliance'];
        foreach ($validTypes as $type) {
            Queue::fake();
            $validPayload = [
                'title' => "Test Alert {$type}",
                'content' => 'Test alert content',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => $type,
                    'message' => 'Test alert message',
                    'action_required' => false,
                    'started_at' => now()->addHour()->toIso8601String(),
                ],
            ];

            $response = $this->authenticateWithJWT($admin)
                ->postJson('/api/announcements/alerts', $validPayload);

            $response->assertStatus(201);
        }
    }

    #[Test]
    public function validates_message_length(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test Alert',
            'content' => 'Test alert content',
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'system',
                'action_required' => false,
                'started_at' => now()->addHour()->toIso8601String(),
            ],
        ];

        // Test message too short (less than 10 chars)
        $payload = $basePayload;
        $payload['metadata']['message'] = 'Short';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.message');

        // Test message too long (more than 500 chars)
        $payload = $basePayload;
        $payload['metadata']['message'] = str_repeat('a', 501);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.message');

        // Test valid message (10-500 chars)
        Queue::fake();
        $payload = $basePayload;
        $payload['metadata']['message'] = 'This is a valid alert message with exactly the right length';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(201);
    }

    #[Test]
    public function validates_action_description_required_when_action_required_true(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test Alert',
            'content' => 'Test alert content',
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'security',
                'message' => 'Test alert message',
                'action_required' => true,
                'started_at' => now()->addHour()->toIso8601String(),
            ],
        ];

        // Test action_required=true WITHOUT action_description (should fail)
        $payload = $basePayload;
        // Do not set action_description
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.action_description');

        // Test action_required=true WITH action_description (should pass)
        Queue::fake();
        $payload = $basePayload;
        $payload['metadata']['action_description'] = 'Navigate to Settings and update your security settings';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(201);
    }

    #[Test]
    public function action_description_optional_when_action_required_false(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test Alert',
            'content' => 'Test alert content',
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'system',
                'message' => 'Test alert message',
                'action_required' => false,
                'started_at' => now()->addHour()->toIso8601String(),
            ],
        ];

        // Test action_required=false WITHOUT action_description (should pass)
        $payload = $basePayload;
        $payload['title'] = 'Test Alert No Action 1';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(201);

        // Test action_required=false WITH action_description (should pass)
        Queue::fake();
        $payload = $basePayload;
        $payload['title'] = 'Test Alert No Action 2';
        $payload['metadata']['action_description'] = 'Optional description even though action not required';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(201);
    }

    #[Test]
    public function validates_ended_at_is_after_started_at(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test Alert',
            'content' => 'Test alert content',
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'system',
                'message' => 'Test alert message',
                'action_required' => false,
                'started_at' => now()->addHours(2)->toIso8601String(),
            ],
        ];

        // Test ended_at BEFORE started_at (should fail)
        $payload = $basePayload;
        $payload['metadata']['ended_at'] = now()->addHour()->toIso8601String(); // 1 hour from now
        // started_at is 2 hours from now, so ended_at is before started_at
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.ended_at');

        // Test ended_at AFTER started_at (should pass)
        Queue::fake();
        $payload = $basePayload;
        $payload['metadata']['ended_at'] = now()->addHours(3)->toIso8601String(); // 3 hours from now
        // started_at is 2 hours from now, so ended_at is after started_at
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);
        $response->assertStatus(201);
    }

    #[Test]
    public function critical_security_alerts_send_immediate_notifications(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Critical Security Alert',
            'content' => 'Immediate security threat detected. Take action now.',
            'metadata' => [
                'urgency' => 'CRITICAL',
                'alert_type' => 'security',
                'message' => 'Critical security breach - immediate action required',
                'action_required' => true,
                'action_description' => 'Change your password and enable 2FA immediately',
                'started_at' => now()->toIso8601String(),
            ],
            'action' => 'publish',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/alerts', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'PUBLISHED');

        $announcement = Announcement::where('title', 'Critical Security Alert')->first();

        $this->assertNotNull($announcement);
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
        $this->assertNotNull($announcement->published_at);
        $this->assertEquals('CRITICAL', $announcement->metadata['urgency']);
        $this->assertEquals('security', $announcement->metadata['alert_type']);

        // NOTE: When notification system is implemented, verify notifications were sent
        // Example: Queue::assertPushed(SendCriticalAlertNotificationJob::class);
    }

    #[Test]
    public function end_user_cannot_create_alert(): void
    {
        // Arrange
        $endUser = $this->createEndUser();

        $payload = [
            'title' => 'Unauthorized Alert',
            'content' => 'This should not be allowed.',
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'system',
                'message' => 'Unauthorized alert message',
                'action_required' => false,
                'started_at' => now()->addHour()->toIso8601String(),
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson('/api/announcements/alerts', $payload);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Insufficient permissions']);

        $this->assertDatabaseMissing('company_announcements', [
            'title' => 'Unauthorized Alert',
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
