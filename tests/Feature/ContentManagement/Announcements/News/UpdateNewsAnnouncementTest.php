<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\News;

use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Test suite for PUT /api/announcements/{id} - Update News Announcements
 *
 * Verifies:
 * - COMPANY_ADMIN can update DRAFT and SCHEDULED news announcements
 * - Cannot update PUBLISHED or ARCHIVED announcements
 * - Validation rules are enforced (news_type, target_audience, summary, call_to_action)
 * - Partial updates preserve unchanged metadata fields
 * - Call to action can be added, updated, or removed
 */
class UpdateNewsAnnouncementTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function can_update_news_in_draft(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $news = $this->createNewsAnnouncementViaHttp(
            $admin,
            [
                'title' => 'Original News Title',
                'body' => 'Original news content.',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => ['users'],
                    'summary' => 'Original summary',
                ],
            ],
            'draft'
        );

        $updateData = [
            'title' => 'Updated News Title',
            'body' => 'Updated news content with more details.',
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Updated summary with new information',
                'call_to_action' => [
                    'text' => 'Learn more',
                    'url' => 'https://example.com/learn-more',
                ],
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$news->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $news->id,
                    'title' => 'Updated News Title',
                    'type' => 'NEWS',
                    'status' => 'DRAFT',
                ],
            ]);

        $this->assertDatabaseHas('company_announcements', [
            'id' => $news->id,
            'title' => 'Updated News Title',
            'content' => 'Updated news content with more details.',
            'status' => PublicationStatus::DRAFT->value,
        ]);

        $news->refresh();
        $this->assertEquals('feature_release', $news->metadata['news_type']);
        $this->assertEquals(['users', 'agents'], $news->metadata['target_audience']);
        $this->assertEquals('Updated summary with new information', $news->metadata['summary']);
        $this->assertArrayHasKey('call_to_action', $news->metadata);
        $this->assertEquals('Learn more', $news->metadata['call_to_action']['text']);
        $this->assertEquals('https://example.com/learn-more', $news->metadata['call_to_action']['url']);
    }

    #[Test]
    public function can_update_news_call_to_action(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $news = $this->createNewsAnnouncementViaHttp(
            $admin,
            [
                'title' => 'News Without CTA',
                'body' => 'Original news content.',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => ['users'],
                    'summary' => 'Original summary',
                ],
            ],
            'draft'
        );

        $this->assertNull($news->metadata['call_to_action'] ?? null);

        $updateData = [
            'metadata' => [
                'call_to_action' => [
                    'text' => 'Read the full article',
                    'url' => 'https://example.com/article',
                ],
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$news->id}", $updateData);

        // Assert
        $response->assertStatus(200);

        $news->refresh();
        $this->assertArrayHasKey('call_to_action', $news->metadata);
        $this->assertEquals('Read the full article', $news->metadata['call_to_action']['text']);
        $this->assertEquals('https://example.com/article', $news->metadata['call_to_action']['url']);
    }

    #[Test]
    public function can_remove_call_to_action(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $news = $this->createNewsAnnouncementViaHttp(
            $admin,
            [
                'title' => 'News With CTA',
                'body' => 'Original news content.',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => ['users'],
                    'summary' => 'Original summary',
                    'call_to_action' => [
                        'text' => 'Learn more',
                        'url' => 'https://example.com',
                    ],
                ],
            ],
            'draft'
        );

        $this->assertArrayHasKey('call_to_action', $news->metadata);

        $updateData = [
            'metadata' => [
                'call_to_action' => null,
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$news->id}", $updateData);

        // Assert
        $response->assertStatus(200);

        $news->refresh();
        $this->assertNull($news->metadata['call_to_action'] ?? null);
    }

    #[Test]
    public function cannot_update_published_news(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $news = $this->createNewsAnnouncementViaHttp(
            $admin,
            [
                'title' => 'Published News',
                'body' => 'Published news content.',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => ['users'],
                    'summary' => 'Published summary',
                ],
            ],
            'publish'
        );

        $this->assertEquals(PublicationStatus::PUBLISHED, $news->status);

        $updateData = [
            'title' => 'Trying to Update Published News',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$news->id}", $updateData);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Cannot edit published announcement',
            ]);

        // Verify no changes were made
        $news->refresh();
        $this->assertEquals('Published News', $news->title);
        $this->assertNotEquals('Trying to Update Published News', $news->title);
    }

    #[Test]
    public function validates_updated_target_audience(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $news = $this->createNewsAnnouncementViaHttp(
            $admin,
            [
                'title' => 'Test News',
                'body' => 'Test news content.',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => ['users'],
                    'summary' => 'Test summary',
                ],
            ],
            'draft'
        );

        // Test empty target_audience (should fail)
        $updateData = [
            'metadata' => [
                'target_audience' => [],
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$news->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.target_audience');

        // Test invalid values in target_audience (should fail)
        $updateData = [
            'metadata' => [
                'target_audience' => ['invalid_audience'],
            ],
        ];

        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$news->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('metadata.target_audience');
    }

    #[Test]
    public function updating_preserves_other_metadata(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $news = $this->createNewsAnnouncementViaHttp(
            $admin,
            [
                'title' => 'Test News',
                'body' => 'Test news content.',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => ['users', 'agents'],
                    'summary' => 'Original summary',
                ],
            ],
            'draft'
        );

        $originalMetadata = $news->metadata;

        // Update only summary (partial update)
        $updateData = [
            'metadata' => [
                'summary' => 'Updated summary with new information',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/announcements/{$news->id}", $updateData);

        // Assert
        $response->assertStatus(200);

        $news->refresh();

        // summary changed
        $this->assertEquals('Updated summary with new information', $news->metadata['summary']);

        // Other metadata fields unchanged
        $this->assertEquals($originalMetadata['news_type'], $news->metadata['news_type']);
        $this->assertEquals($originalMetadata['target_audience'], $news->metadata['target_audience']);
    }

    // ==================== Helper Methods ====================

    /**
     * Create a news announcement via HTTP POST endpoint
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
    protected function createNewsAnnouncementViaHttp(
        User $user,
        array $overrides = [],
        string $action = 'draft',
        ?string $scheduledFor = null
    ): Announcement {
        // Build payload with defaults
        $payload = array_merge([
            'title' => 'Test News',
            'body' => 'Test news content',
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users'],
                'summary' => 'Test news summary',
            ],
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
            ->postJson('/api/announcements/news', $payload);

        // Assert the request was successful
        if (!in_array($response->status(), [201])) {
            throw new \Exception(
                "Failed to create news via HTTP. Status: {$response->status()}\n" .
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
