<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\News;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating News Announcements
 *
 * Tests the endpoint POST /api/announcements/news
 *
 * Coverage:
 * - Authorization (COMPANY_ADMIN, END_USER)
 * - Creating drafts, publishing, and scheduling
 * - Field validation (required fields, enums, summary length, target_audience array)
 * - Call to action structure validation (optional, text + https url)
 * - Company ID inference from JWT token
 * - News-specific metadata validation (news_type enum, target_audience array)
 */
class CreateNewsAnnouncementTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function company_admin_can_create_news_as_draft(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'New Feature Release: Dark Mode',
            'body' => 'We are excited to announce the launch of dark mode across all our applications.',
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Dark mode is now available for all users',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.title', 'New Feature Release: Dark Mode')
            ->assertJsonPath('data.type', 'NEWS')
            ->assertJsonMissing(['data.published_at']);

        $this->assertDatabaseHas('company_announcements', [
            'title' => 'New Feature Release: Dark Mode',
            'type' => 'NEWS',
            'status' => 'DRAFT',
            'author_id' => $admin->id,
        ]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function company_admin_can_create_and_publish_news(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Updated Privacy Policy',
            'body' => 'We have updated our privacy policy to comply with the latest regulations. Please review the changes.',
            'metadata' => [
                'news_type' => 'policy_update',
                'target_audience' => ['users', 'agents', 'admins'],
                'summary' => 'Privacy policy updated for compliance',
            ],
            'action' => 'publish',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'PUBLISHED')
            ->assertJsonPath('data.title', 'Updated Privacy Policy');

        $announcement = Announcement::where('title', 'Updated Privacy Policy')->first();

        $this->assertNotNull($announcement);
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
        $this->assertNotNull($announcement->published_at);
        $this->assertEquals('policy_update', $announcement->metadata['news_type']);
        $this->assertEquals(['users', 'agents', 'admins'], $announcement->metadata['target_audience']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function validates_required_fields_for_news(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test News',
            'body' => 'Test news content',
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users'],
                'summary' => 'This is a test summary with minimum length',
            ],
        ];

        // Test missing title
        $payload = $basePayload;
        unset($payload['title']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');

        // Test missing body
        $payload = $basePayload;
        unset($payload['body']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('body');

        // Test missing news_type
        $payload = $basePayload;
        unset($payload['metadata']['news_type']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.news_type');

        // Test missing target_audience
        $payload = $basePayload;
        unset($payload['metadata']['target_audience']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.target_audience');

        // Test missing summary
        $payload = $basePayload;
        unset($payload['metadata']['summary']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.summary');
    }

    #[Test]
    public function validates_news_type_enum(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Test invalid news_type
        $invalidPayload = [
            'title' => 'Test News',
            'body' => 'Test news content',
            'metadata' => [
                'news_type' => 'invalid_type',
                'target_audience' => ['users'],
                'summary' => 'This is a test summary',
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $invalidPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.news_type');

        // Test valid news_type values
        $validTypes = ['feature_release', 'policy_update', 'general_update'];
        foreach ($validTypes as $type) {
            Queue::fake();
            $validPayload = [
                'title' => "Test News {$type}",
                'body' => 'Test news content',
                'metadata' => [
                    'news_type' => $type,
                    'target_audience' => ['users'],
                    'summary' => 'This is a test summary',
                ],
            ];

            $response = $this->authenticateWithJWT($admin)
                ->postJson('/api/announcements/news', $validPayload);

            $response->assertStatus(201);
        }
    }

    #[Test]
    public function validates_target_audience_is_array_with_valid_values(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test News',
            'body' => 'Test news content',
            'metadata' => [
                'news_type' => 'general_update',
                'summary' => 'This is a test summary',
            ],
        ];

        // Test target_audience as string (should fail)
        $payload = $basePayload;
        $payload['metadata']['target_audience'] = 'users';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.target_audience');

        // Test empty target_audience array (should fail - min 1)
        $payload = $basePayload;
        $payload['metadata']['target_audience'] = [];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.target_audience');

        // Test invalid values in target_audience
        $payload = $basePayload;
        $payload['metadata']['target_audience'] = ['invalid'];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.target_audience');

        // Test valid target_audience with 3 items (should pass)
        Queue::fake();
        $payload = $basePayload;
        $payload['metadata']['target_audience'] = ['users', 'agents', 'admins'];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(201);

        // Test target_audience with more than 5 items (should fail - max 5)
        $payload = $basePayload;
        $payload['metadata']['target_audience'] = ['users', 'agents', 'admins', 'users', 'agents', 'admins'];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.target_audience');
    }

    #[Test]
    public function validates_summary_length(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test News',
            'body' => 'Test news content',
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users'],
            ],
        ];

        // Test summary too short (less than 10 chars)
        $payload = $basePayload;
        $payload['metadata']['summary'] = 'Short';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.summary');

        // Test summary too long (more than 500 chars)
        $payload = $basePayload;
        $payload['metadata']['summary'] = str_repeat('a', 501);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.summary');

        // Test valid summary (10-500 chars)
        Queue::fake();
        $payload = $basePayload;
        $payload['metadata']['summary'] = 'This is a valid summary with exactly the right length';
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(201);
    }

    #[Test]
    public function call_to_action_is_optional(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Test News Without CTA',
            'body' => 'Test news content without call to action',
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users'],
                'summary' => 'This is a test summary',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);

        // Assert
        $response->assertStatus(201);

        $announcement = Announcement::where('title', 'Test News Without CTA')->first();
        $this->assertNotNull($announcement);
        $this->assertNull($announcement->metadata['call_to_action'] ?? null);
    }

    #[Test]
    public function validates_call_to_action_structure(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $basePayload = [
            'title' => 'Test News',
            'body' => 'Test news content',
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users'],
                'summary' => 'This is a test summary',
            ],
        ];

        // Test call_to_action missing 'text'
        $payload = $basePayload;
        $payload['metadata']['call_to_action'] = [
            'url' => 'https://example.com',
        ];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.call_to_action.text');

        // Test call_to_action missing 'url'
        $payload = $basePayload;
        $payload['metadata']['call_to_action'] = [
            'text' => 'Read more',
        ];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.call_to_action.url');

        // Test call_to_action with invalid URL
        $payload = $basePayload;
        $payload['metadata']['call_to_action'] = [
            'text' => 'Read more',
            'url' => 'not-a-url',
        ];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.call_to_action.url');

        // Test call_to_action with http URL (should require https)
        $payload = $basePayload;
        $payload['metadata']['call_to_action'] = [
            'text' => 'Read more',
            'url' => 'http://example.com',
        ];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.call_to_action.url');

        // Test valid call_to_action
        Queue::fake();
        $payload = $basePayload;
        $payload['metadata']['call_to_action'] = [
            'text' => 'Read more',
            'url' => 'https://example.com',
        ];
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);
        $response->assertStatus(201);
    }

    #[Test]
    public function news_can_be_scheduled(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $scheduledFor = now()->addMinutes(10);
        $payload = [
            'title' => 'Scheduled News Announcement',
            'body' => 'This news will be published at a specific time.',
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users'],
                'summary' => 'Scheduled news summary',
            ],
            'action' => 'schedule',
            'scheduled_for' => $scheduledFor->toIso8601String(),
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/news', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'SCHEDULED')
            ->assertJsonPath('data.title', 'Scheduled News Announcement');

        $announcement = Announcement::where('title', 'Scheduled News Announcement')->first();

        $this->assertNotNull($announcement);
        $this->assertEquals(PublicationStatus::SCHEDULED, $announcement->status);
        $this->assertArrayHasKey('scheduled_for', $announcement->metadata);

        Queue::assertPushed(\App\Features\ContentManagement\Jobs\PublishAnnouncementJob::class);
    }

    #[Test]
    public function end_user_cannot_create_news(): void
    {
        // Arrange
        $endUser = $this->createEndUser();

        $payload = [
            'title' => 'Unauthorized News',
            'body' => 'This should not be allowed.',
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users'],
                'summary' => 'Unauthorized news summary',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson('/api/announcements/news', $payload);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Insufficient permissions']);

        $this->assertDatabaseMissing('company_announcements', [
            'title' => 'Unauthorized News',
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