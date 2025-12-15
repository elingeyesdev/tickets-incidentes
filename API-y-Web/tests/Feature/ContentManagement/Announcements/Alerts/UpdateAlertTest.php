<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Alerts;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Updating Alert Announcements
 *
 * Tests the endpoint PUT /api/announcements/{id}
 *
 * Coverage:
 * - Updating alert message and action_description
 * - Marking alert as ended (ended_at)
 * - Preventing changes to action_required from true to false
 * - Preventing updates to published alerts
 * - Validating updated urgency stays HIGH or CRITICAL
 */
class UpdateAlertTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function can_update_alert_message_and_action_description(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        // Create a draft alert using helper
        $alert = $this->createAlertAnnouncementViaHttp(
            $admin,
            [
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'system',
                    'message' => 'Original message',
                    'action_required' => true,
                    'action_description' => 'Original action description',
                    'started_at' => now()->addHour()->toIso8601String(),
                ],
            ]
        );

        // Act - Update the message and action_description
        $updatePayload = [
            'metadata' => [
                'message' => 'Updated alert message with new information',
                'action_description' => 'Updated action description with new steps',
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$alert->id}", $updatePayload);

        // Assert
        $response->assertStatus(200);

        $alert->refresh();
        $this->assertEquals('Updated alert message with new information', $alert->metadata['message']);
        $this->assertEquals('Updated action description with new steps', $alert->metadata['action_description']);
    }

    #[Test]
    public function can_mark_alert_as_ended(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        // Create a published alert
        $alert = $this->createAlertAnnouncementViaHttp(
            $admin,
            [
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'service',
                    'message' => 'Service disruption detected',
                    'action_required' => false,
                    'started_at' => now()->subHours(2)->toIso8601String(),
                ],
            ],
            'publish'
        );

        $this->assertNull($alert->metadata['ended_at'] ?? null);

        // Act - Mark alert as ended
        $endedAt = now()->toIso8601String();
        $updatePayload = [
            'metadata' => [
                'ended_at' => $endedAt,
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$alert->id}", $updatePayload);

        // Assert
        $response->assertStatus(200);

        $alert->refresh();
        $this->assertNotNull($alert->metadata['ended_at']);
    }

    #[Test]
    public function cannot_change_action_required_from_true_to_false(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        // Create alert with action_required=true
        $alert = $this->createAlertAnnouncementViaHttp(
            $admin,
            [
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'security',
                    'message' => 'Security action required',
                    'action_required' => true,
                    'action_description' => 'Take immediate action',
                    'started_at' => now()->addHour()->toIso8601String(),
                ],
            ]
        );

        $this->assertTrue($alert->metadata['action_required']);

        // Act - Attempt to change action_required from true to false
        $updatePayload = [
            'metadata' => [
                'action_required' => false,
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$alert->id}", $updatePayload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.action_required');

        $alert->refresh();
        $this->assertTrue($alert->metadata['action_required']); // Should remain true
    }

    #[Test]
    public function cannot_update_published_alert(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        // Create and publish alert
        $alert = $this->createAlertAnnouncementViaHttp(
            $admin,
            [
                'metadata' => [
                    'urgency' => 'CRITICAL',
                    'alert_type' => 'security',
                    'message' => 'Critical security alert',
                    'action_required' => true,
                    'action_description' => 'Immediate action required',
                    'started_at' => now()->toIso8601String(),
                ],
            ],
            'publish'
        );

        $this->assertEquals(PublicationStatus::PUBLISHED, $alert->status);

        // Act - Attempt to update published alert
        $updatePayload = [
            'metadata' => [
                'message' => 'Attempting to update published alert',
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$alert->id}", $updatePayload);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Cannot edit published announcement']);
    }

    #[Test]
    public function validates_updated_urgency_still_high_or_critical(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        // Create alert with urgency=CRITICAL
        $alert = $this->createAlertAnnouncementViaHttp(
            $admin,
            [
                'metadata' => [
                    'urgency' => 'CRITICAL',
                    'alert_type' => 'security',
                    'message' => 'Critical alert',
                    'action_required' => false,
                    'started_at' => now()->addHour()->toIso8601String(),
                ],
            ]
        );

        $this->assertEquals('CRITICAL', $alert->metadata['urgency']);

        // Act - Attempt to update urgency to MEDIUM (should fail)
        $updatePayload = [
            'metadata' => [
                'urgency' => 'MEDIUM',
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$alert->id}", $updatePayload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.urgency');

        $alert->refresh();
        $this->assertEquals('CRITICAL', $alert->metadata['urgency']); // Should remain CRITICAL

        // Verify that HIGH and CRITICAL are still valid
        Queue::fake();
        $updatePayload = [
            'metadata' => [
                'urgency' => 'HIGH',
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$alert->id}", $updatePayload);

        $response->assertStatus(200);
    }
}
