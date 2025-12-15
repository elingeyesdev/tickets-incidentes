<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Articles;

use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Updating Help Center Articles
 *
 * Tests the endpoint PUT /api/help-center/articles/:id
 *
 * Coverage:
 * - Update draft article (title, content, excerpt)
 * - Update published article (allowed)
 * - Published_at preservation on update
 * - Category change validation
 * - Title uniqueness per company
 * - Partial updates (only some fields)
 * - Cross-company authorization check
 * - Views count preservation on update
 */
class UpdateArticleTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Basic Update Operations (Tests 1-3) ====================

    /**
     * Test #1: Company admin can update draft article
     *
     * Verifies that COMPANY_ADMIN can successfully update a DRAFT article.
     * Tests updating multiple fields: title, content, excerpt.
     * Status should remain DRAFT after update.
     *
     * Expected: 200 OK, changes applied, status remains DRAFT
     */
    #[Test]
    public function company_admin_can_update_draft_article(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'ACCOUNT_PROFILE')->first();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Original Draft Title',
            'excerpt' => 'Original excerpt',
            'content' => str_repeat('Original draft content. ', 10),
            'status' => 'DRAFT',
            'published_at' => null,
        ]);

        $updatePayload = [
            'title' => 'Updated Draft Title',
            'content' => str_repeat('Updated content for draft article. ', 10),
            'excerpt' => 'Updated excerpt for draft',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/help-center/articles/{$article->id}", $updatePayload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'Updated Draft Title');
        $response->assertJsonPath('data.content', str_repeat('Updated content for draft article. ', 10));
        $response->assertJsonPath('data.excerpt', 'Updated excerpt for draft');
        $response->assertJsonPath('data.status', 'DRAFT');

        $this->assertDatabaseHas('help_center_articles', [
            'id' => $article->id,
            'title' => 'Updated Draft Title',
            'content' => str_repeat('Updated content for draft article. ', 10),
            'excerpt' => 'Updated excerpt for draft',
            'status' => 'DRAFT',
        ]);
    }

    /**
     * Test #2: Company admin can update published article
     *
     * Verifies that PUBLISHED articles can be updated.
     * According to API docs, articles can be updated in any status.
     * Status should remain PUBLISHED after update.
     *
     * Expected: 200 OK, changes applied, status remains PUBLISHED
     */
    #[Test]
    public function company_admin_can_update_published_article(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'SECURITY_PRIVACY')->first();

        $publishedAt = Carbon::now()->subDays(5);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Original Published Title',
            'content' => str_repeat('Original published content. ', 10),
            'status' => 'PUBLISHED',
            'published_at' => $publishedAt,
        ]);

        $updatePayload = [
            'title' => 'Updated Published Title',
            'content' => str_repeat('Updated content for published article. ', 10),
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/help-center/articles/{$article->id}", $updatePayload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'Updated Published Title');
        $response->assertJsonPath('data.content', str_repeat('Updated content for published article. ', 10));
        $response->assertJsonPath('data.status', 'PUBLISHED');

        $this->assertDatabaseHas('help_center_articles', [
            'id' => $article->id,
            'title' => 'Updated Published Title',
            'status' => 'PUBLISHED',
        ]);
    }

    /**
     * Test #3: Updating published article does not change published_at
     *
     * Verifies that the published_at timestamp is preserved when updating
     * a PUBLISHED article. The original publication date should remain unchanged.
     *
     * Expected: 200 OK, published_at remains unchanged
     */
    #[Test]
    public function updating_published_article_does_not_change_published_at(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'BILLING_PAYMENTS')->first();

        $originalPublishedAt = Carbon::create(2025, 11, 1, 10, 0, 0);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Published Article with Fixed Date',
            'content' => str_repeat('Original content. ', 10),
            'status' => 'PUBLISHED',
            'published_at' => $originalPublishedAt,
        ]);

        $updatePayload = [
            'title' => 'Updated Title But Same Publish Date',
            'content' => str_repeat('Updated content. ', 10),
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/help-center/articles/{$article->id}", $updatePayload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Updated Title But Same Publish Date');

        // Verify published_at is preserved
        $article->refresh();
        $this->assertEquals(
            $originalPublishedAt->format('Y-m-d H:i:s'),
            $article->published_at->format('Y-m-d H:i:s'),
            'published_at should not change when updating a published article'
        );
    }

    // ==================== GROUP 2: Category Changes (Test 4) ====================

    /**
     * Test #4: Can change category
     *
     * Verifies that an article's category can be changed to a different
     * valid global category. The new category_id must exist and be valid.
     *
     * Expected: 200 OK, category_id updated successfully
     */
    #[Test]
    public function can_change_category(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $originalCategory = ArticleCategory::where('code', 'ACCOUNT_PROFILE')->first();
        $newCategory = ArticleCategory::where('code', 'SECURITY_PRIVACY')->first();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id,
            'author_id' => $admin->id,
            'category_id' => $originalCategory->id,
            'title' => 'Article for Category Change',
            'content' => str_repeat('Content about account profile. ', 10),
            'status' => 'DRAFT',
        ]);

        $updatePayload = [
            'category_id' => $newCategory->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/help-center/articles/{$article->id}", $updatePayload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.category_id', $newCategory->id);

        $this->assertDatabaseHas('help_center_articles', [
            'id' => $article->id,
            'category_id' => $newCategory->id,
        ]);
    }

    // ==================== GROUP 3: Validation (Tests 5-6) ====================

    /**
     * Test #5: Validates updated title uniqueness per company
     *
     * Verifies that title uniqueness validation applies on update.
     * Changing an article's title to one that already exists in the SAME company
     * should fail with 422 validation error.
     * Note: Title is unique PER COMPANY, not globally.
     *
     * Expected: 422 Unprocessable Entity with validation error
     */
    #[Test]
    public function validates_updated_title_uniqueness(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'TECHNICAL_SUPPORT')->first();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        // Create two articles in the same company
        $article1 = HelpCenterArticle::factory()->create([
            'company_id' => $companyId,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'How to Reset Your Password',
            'content' => str_repeat('Password reset instructions. ', 10),
            'status' => 'PUBLISHED',
        ]);

        $article2 = HelpCenterArticle::factory()->create([
            'company_id' => $companyId,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Introduction to Two-Factor Authentication',
            'content' => str_repeat('2FA setup guide. ', 10),
            'status' => 'DRAFT',
        ]);

        // Try to update article2 to have the same title as article1
        $updatePayload = [
            'title' => 'How to Reset Your Password', // Duplicate title in same company
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/help-center/articles/{$article2->id}", $updatePayload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');

        // Verify article2 title unchanged in database
        $article2->refresh();
        $this->assertEquals('Introduction to Two-Factor Authentication', $article2->title);
    }

    /**
     * Test #6: Partial update works
     *
     * Verifies that partial updates are supported.
     * Updating only one field (e.g., excerpt) should leave other fields unchanged.
     *
     * Expected: 200 OK, only specified field updated, others preserved
     */
    #[Test]
    public function partial_update_works(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'BILLING_PAYMENTS')->first();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Original Title',
            'excerpt' => 'Original excerpt',
            'content' => str_repeat('Original content. ', 10),
            'status' => 'DRAFT',
        ]);

        $originalTitle = $article->title;
        $originalContent = $article->content;

        $updatePayload = [
            'excerpt' => 'New updated excerpt only',
            // Not updating title or content
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/help-center/articles/{$article->id}", $updatePayload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.excerpt', 'New updated excerpt only');
        $response->assertJsonPath('data.title', $originalTitle);
        $response->assertJsonPath('data.content', $originalContent);

        $this->assertDatabaseHas('help_center_articles', [
            'id' => $article->id,
            'title' => $originalTitle,
            'content' => $originalContent,
            'excerpt' => 'New updated excerpt only',
        ]);
    }

    // ==================== GROUP 4: Authorization (Test 7) ====================

    /**
     * Test #7: Cannot update article from different company
     *
     * Verifies cross-company authorization check.
     * COMPANY_ADMIN from Company B should NOT be able to update
     * an article belonging to Company A.
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function cannot_update_article_from_different_company(): void
    {
        // Arrange
        $adminA = $this->createCompanyAdmin();
        $adminB = $this->createCompanyAdmin(); // Different company

        $category = ArticleCategory::where('code', 'ACCOUNT_PROFILE')->first();

        // Admin A creates an article for their company
        $articleCompanyA = HelpCenterArticle::factory()->create([
            'company_id' => $adminA->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id,
            'author_id' => $adminA->id,
            'category_id' => $category->id,
            'title' => 'Company A Article',
            'content' => str_repeat('Content from Company A. ', 10),
            'status' => 'PUBLISHED',
        ]);

        // Admin B tries to update Company A's article
        $updatePayload = [
            'title' => 'Admin B Trying to Hijack',
            'content' => str_repeat('Unauthorized update attempt. ', 10),
        ];

        // Act
        $response = $this->authenticateWithJWT($adminB)
            ->putJson("/api/help-center/articles/{$articleCompanyA->id}", $updatePayload);

        // Assert
        $response->assertStatus(403);

        // Verify article remains unchanged
        $articleCompanyA->refresh();
        $this->assertEquals('Company A Article', $articleCompanyA->title);
    }

    // ==================== GROUP 5: Data Preservation (Test 8) ====================

    /**
     * Test #8: Updating does not reset views count
     *
     * Verifies that the views_count field is preserved when updating an article.
     * views_count should NOT be reset to 0 on update.
     *
     * Expected: 200 OK, views_count remains unchanged
     */
    #[Test]
    public function updating_resets_views_count_is_false(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'TECHNICAL_SUPPORT')->first();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Popular Article with Views',
            'content' => str_repeat('Content with many views. ', 10),
            'status' => 'PUBLISHED',
            'views_count' => 50, // Article has 50 views
        ]);

        $updatePayload = [
            'title' => 'Updated Title for Popular Article',
            'content' => str_repeat('Updated content but views should persist. ', 10),
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->putJson("/api/help-center/articles/{$article->id}", $updatePayload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.views_count', 50);

        $this->assertDatabaseHas('help_center_articles', [
            'id' => $article->id,
            'views_count' => 50, // Should NOT be reset to 0
        ]);
    }

}
