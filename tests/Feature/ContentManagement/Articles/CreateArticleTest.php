<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Articles;

use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating Help Center Articles
 *
 * Tests the endpoint POST /api/help-center/articles
 *
 * Coverage:
 * - Authentication (unauthenticated, END_USER, COMPANY_ADMIN)
 * - Required fields validation (category_id, title, content)
 * - Title length validation (min 3, max 255)
 * - Content length validation (min 50, max 20000)
 * - Category validation (exists, global categories only)
 * - Optional excerpt validation (max 500 chars)
 * - Default status (DRAFT)
 * - Company ID inference from JWT token
 * - Author ID set to authenticated user
 * - Response structure validation
 */
class CreateArticleTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication (Tests 1-3) ====================

    /**
     * Test #1: Unauthenticated user cannot create article
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_create_article(): void
    {
        // Arrange
        $category = ArticleCategory::where('code', 'SECURITY_PRIVACY')->first();

        $payload = [
            'category_id' => $category->id,
            'title' => 'Unauthorized Article',
            'content' => str_repeat('This article should not be created. ', 10), // 50+ chars
        ];

        // Act - No authenticateWithJWT() call
        $response = $this->postJson('/api/help-center/articles', $payload);

        // Assert
        $response->assertStatus(401);

        $this->assertDatabaseMissing('help_center_articles', [
            'title' => 'Unauthorized Article',
        ]);
    }

    /**
     * Test #2: End user cannot create article
     *
     * Verifies that users with END_USER role are forbidden from creating articles.
     * Only COMPANY_ADMIN role should be able to create articles.
     *
     * Expected: 403 Forbidden with message including "COMPANY_ADMIN"
     */
    #[Test]
    public function end_user_cannot_create_article(): void
    {
        // Arrange
        $endUser = User::factory()->withRole('USER')->create();
        $category = ArticleCategory::where('code', 'ACCOUNT_PROFILE')->first();

        $payload = [
            'category_id' => $category->id,
            'title' => 'End User Article',
            'content' => str_repeat('This article should not be allowed. ', 10), // 50+ chars
        ];

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson('/api/help-center/articles', $payload);

        // Assert
        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Insufficient permissions']);

        $this->assertDatabaseMissing('help_center_articles', [
            'title' => 'End User Article',
        ]);
    }

    /**
     * Test #3: Only company admin can create article
     *
     * Verifies that users with COMPANY_ADMIN role can successfully create articles.
     *
     * Expected: 201 Created (no authorization errors)
     */
    #[Test]
    public function only_company_admin_can_create_article(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'BILLING_PAYMENTS')->first();

        $payload = [
            'category_id' => $category->id,
            'title' => 'Company Admin Article',
            'content' => str_repeat('This article is created by a company admin. ', 10), // 50+ chars
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Company Admin Article');

        $this->assertDatabaseHas('help_center_articles', [
            'title' => 'Company Admin Article',
            'author_id' => $admin->id,
        ]);
    }

    // ==================== GROUP 2: Validation of Required Fields (Tests 4-6) ====================

    /**
     * Test #4: Validates required fields
     *
     * Verifies that category_id, title, and content are required fields.
     * Sending an empty request should return 422 with validation errors for each field.
     *
     * Expected: 422 Unprocessable Entity with errors for category_id, title, and content
     */
    #[Test]
    public function validates_required_fields(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Empty payload (missing all required fields)
        $payload = [];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id', 'title', 'content']);
    }

    /**
     * Test #5: Validates title length
     *
     * Verifies title validation constraints:
     * - Minimum 3 characters (should fail)
     * - Maximum 255 characters (should fail)
     * - Valid length 10-200 characters (should pass)
     *
     * Expected: 422 for invalid lengths, 201 for valid length
     */
    #[Test]
    public function validates_title_length(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'TECHNICAL_SUPPORT')->first();

        $basePayload = [
            'category_id' => $category->id,
            'content' => str_repeat('Valid content for testing title length validation. ', 5), // 50+ chars
        ];

        // Case 1: Title too short (2 chars, min is 3)
        $payload = array_merge($basePayload, ['title' => 'AB']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');

        // Case 2: Title too long (256 chars, max is 255)
        $payload = array_merge($basePayload, ['title' => str_repeat('A', 256)]);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');

        // Case 3: Valid title length (between 3 and 255)
        $payload = array_merge($basePayload, ['title' => 'Valid Title Length']);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);
        $response->assertStatus(201);
    }

    /**
     * Test #6: Validates content length
     *
     * Verifies content validation constraints:
     * - Minimum 50 characters (should fail)
     * - Maximum 20000 characters (should fail)
     * - Valid length 100-10000 characters (should pass)
     *
     * Expected: 422 for invalid lengths, 201 for valid length
     */
    #[Test]
    public function validates_content_length(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'SECURITY_PRIVACY')->first();

        $basePayload = [
            'category_id' => $category->id,
            'title' => 'Content Length Test Article',
        ];

        // Case 1: Content too short (30 chars, min is 50)
        $payload = array_merge($basePayload, ['content' => str_repeat('A', 30)]);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');

        // Case 2: Content too long (25000 chars, max is 20000)
        $payload = array_merge($basePayload, ['content' => str_repeat('A', 25000)]);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');

        // Case 3: Valid content length (between 50 and 20000)
        $payload = array_merge($basePayload, ['content' => str_repeat('Valid content. ', 50)]); // ~750 chars
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);
        $response->assertStatus(201);
    }

    // ==================== GROUP 3: Category Validation (Tests 7-8) ====================

    /**
     * Test #7: Validates category exists
     *
     * Verifies that category_id must reference an existing category in the database.
     * Using a non-existent UUID should fail validation.
     *
     * Expected: 422 with validation error for category_id
     */
    #[Test]
    public function validates_category_exists(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $nonExistentCategoryId = Str::uuid()->toString(); // Random UUID that doesn't exist

        $payload = [
            'category_id' => $nonExistentCategoryId,
            'title' => 'Article with Invalid Category',
            'content' => str_repeat('This article references a non-existent category. ', 10), // 50+ chars
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('category_id');
    }

    /**
     * Test #8: Validates category is from global list
     *
     * Verifies that only the 4 global categories are valid:
     * - ACCOUNT_PROFILE
     * - SECURITY_PRIVACY
     * - BILLING_PAYMENTS
     * - TECHNICAL_SUPPORT
     *
     * This test is effectively covered by test #7, as categories must exist
     * and only global categories exist in the database (seeded).
     *
     * Expected: 422 for non-existent category, 201 for valid global category
     */
    #[Test]
    public function validates_category_is_from_global_list(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Verify each global category can be used
        $globalCategories = ['ACCOUNT_PROFILE', 'SECURITY_PRIVACY', 'BILLING_PAYMENTS', 'TECHNICAL_SUPPORT'];

        foreach ($globalCategories as $categoryCode) {
            $category = ArticleCategory::where('code', $categoryCode)->first();

            $this->assertNotNull($category, "Global category {$categoryCode} should exist");

            $payload = [
                'category_id' => $category->id,
                'title' => "Article for {$categoryCode}",
                'content' => str_repeat("Content for {$categoryCode} category. ", 10), // 50+ chars
            ];

            $response = $this->authenticateWithJWT($admin)
                ->postJson('/api/help-center/articles', $payload);

            $response->assertStatus(201);
        }

        // Non-existent category should fail (covered by test #7)
        $nonExistentId = Str::uuid()->toString();
        $payload = [
            'category_id' => $nonExistentId,
            'title' => 'Invalid Category Article',
            'content' => str_repeat('Should fail. ', 10),
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    // ==================== GROUP 4: Optional Fields (Tests 9-10) ====================

    /**
     * Test #9: Excerpt is optional but validated
     *
     * Verifies excerpt field validation:
     * - Case 1: Without excerpt (should accept, excerpt = null or auto-generated)
     * - Case 2: Excerpt with 600 chars (should fail, max 500)
     * - Case 3: Excerpt with 100 chars (should pass)
     *
     * Expected: 201 without excerpt, 422 for too long, 201 for valid length
     */
    #[Test]
    public function excerpt_is_optional_but_validated(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'ACCOUNT_PROFILE')->first();

        $basePayload = [
            'category_id' => $category->id,
            'title' => 'Excerpt Test Article',
            'content' => str_repeat('Valid content for excerpt testing. ', 10), // 50+ chars
        ];

        // Case 1: Without excerpt (should be accepted)
        $payload = $basePayload;
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);
        $response->assertStatus(201);

        // Case 2: Excerpt too long (600 chars, max is 500)
        $payload = array_merge($basePayload, [
            'title' => 'Excerpt Too Long',
            'excerpt' => str_repeat('A', 600),
        ]);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('excerpt');

        // Case 3: Valid excerpt length (100 chars)
        $payload = array_merge($basePayload, [
            'title' => 'Valid Excerpt Length',
            'excerpt' => str_repeat('Valid excerpt. ', 7), // ~105 chars
        ]);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);
        $response->assertStatus(201);
    }

    /**
     * Test #10: Article is created in draft status
     *
     * Verifies that articles are ALWAYS created with status = DRAFT:
     * - With action="draft" (or no action): status = DRAFT, published_at = null
     * - Even if request includes action="publish" or status="PUBLISHED", it should be IGNORED
     * - Only DRAFT status is accepted on creation
     *
     * Expected: status = DRAFT, published_at = null
     */
    #[Test]
    public function article_is_created_in_draft_status(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'BILLING_PAYMENTS')->first();

        $basePayload = [
            'category_id' => $category->id,
            'title' => 'Draft Status Test',
            'content' => str_repeat('Testing draft status on creation. ', 10), // 50+ chars
        ];

        // Case 1: Without action (default DRAFT)
        $payload = $basePayload;
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'DRAFT');
        $response->assertJsonPath('data.published_at', null);

        // Case 2: Explicitly set action="draft"
        $payload = array_merge($basePayload, [
            'title' => 'Explicit Draft Action',
            'action' => 'draft',
        ]);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'DRAFT');
        $response->assertJsonPath('data.published_at', null);

        // Case 3: Try to set action="publish" (should be IGNORED or fail validation)
        // According to specs, only DRAFT is accepted on creation
        // If action="publish" is sent, it should either:
        // a) Be ignored and create as DRAFT
        // b) Fail validation
        // We'll test that status is DRAFT regardless
        $payload = array_merge($basePayload, [
            'title' => 'Attempt Publish Action',
            'action' => 'publish',
        ]);
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        // If the endpoint accepts and ignores action=publish:
        if ($response->status() === 201) {
            $response->assertJsonPath('data.status', 'DRAFT');
            $response->assertJsonPath('data.published_at', null);
        } else {
            // Or if it fails validation (action must be 'draft' only)
            $response->assertStatus(422);
        }
    }

    // ==================== GROUP 5: Company ID and Author (Tests 11-12) ====================

    /**
     * Test #11: Company ID comes from JWT token
     *
     * Verifies that company_id is extracted from the JWT token (immutable):
     * - Request does NOT include company_id in payload
     * - Response should contain company_id matching the authenticated admin's company
     * - If request tries to pass a different company_id, it should be IGNORED
     *
     * Expected: company_id matches the admin's company from JWT
     */
    #[Test]
    public function company_id_comes_from_jwt_token(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'TECHNICAL_SUPPORT')->first();

        // Get admin's company ID
        $adminCompanyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $payload = [
            'category_id' => $category->id,
            'title' => 'Company ID from JWT Test',
            'content' => str_repeat('Testing company_id extraction from JWT. ', 10), // 50+ chars
            // Intentionally NOT including company_id in payload
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.company_id', $adminCompanyId);

        $this->assertDatabaseHas('help_center_articles', [
            'title' => 'Company ID from JWT Test',
            'company_id' => $adminCompanyId,
        ]);

        // Additional test: Try to pass a different company_id (should be IGNORED)
        $fakeCompanyId = Str::uuid()->toString();
        $payload = [
            'category_id' => $category->id,
            'title' => 'Attempt Different Company ID',
            'content' => str_repeat('Trying to override company_id. ', 10),
            'company_id' => $fakeCompanyId, // Should be ignored
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        // Response should use company_id from JWT, NOT the one in payload
        if ($response->status() === 201) {
            $response->assertJsonPath('data.company_id', $adminCompanyId);
            $this->assertDatabaseHas('help_center_articles', [
                'title' => 'Attempt Different Company ID',
                'company_id' => $adminCompanyId, // From JWT
            ]);
        }
    }

    /**
     * Test #12: Author ID is set to authenticated user
     *
     * Verifies that author_id is automatically set to the authenticated user's ID.
     *
     * Expected: author_id matches the authenticated admin's ID
     */
    #[Test]
    public function author_id_is_set_to_authenticated_user(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'SECURITY_PRIVACY')->first();

        $payload = [
            'category_id' => $category->id,
            'title' => 'Author ID Test Article',
            'content' => str_repeat('Testing author_id assignment. ', 10), // 50+ chars
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.author_id', $admin->id);

        $this->assertDatabaseHas('help_center_articles', [
            'title' => 'Author ID Test Article',
            'author_id' => $admin->id,
        ]);
    }

    // ==================== BONUS: Response Structure (Optional Test 13) ====================

    /**
     * BONUS Test: Response structure matches API docs
     *
     * Verifies that the response structure includes all expected fields:
     * - id, company_id, author_id, category_id (UUIDs)
     * - title, excerpt, content (strings)
     * - status (DRAFT), views_count (0), published_at (null)
     * - created_at, updated_at (ISO dates)
     *
     * Expected: Response structure matches ArticleResource schema
     */
    #[Test]
    public function response_structure_matches_api_docs(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $category = ArticleCategory::where('code', 'ACCOUNT_PROFILE')->first();

        $payload = [
            'category_id' => $category->id,
            'title' => 'Response Structure Test',
            'excerpt' => 'This is a test excerpt for structure validation.',
            'content' => str_repeat('Testing response structure compliance with API documentation. ', 10),
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/help-center/articles', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'company_id',
                'author_id',
                'category_id',
                'title',
                'excerpt',
                'content',
                'status',
                'views_count',
                'published_at',
                'created_at',
                'updated_at',
            ],
        ]);

        // Verify specific values
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'DRAFT');
        $response->assertJsonPath('data.views_count', 0);
        $response->assertJsonPath('data.published_at', null);
    }

    // ==================== Helper Methods ====================

    /**
     * Create an end user (USER role) for testing.
     *
     * Helper method to create users with END_USER role who should NOT
     * be able to create articles (only COMPANY_ADMIN can).
     *
     * @return User
     */
    private function createEndUser(): User
    {
        return User::factory()->withRole('USER')->create();
    }
}