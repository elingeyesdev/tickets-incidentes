<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Permissions;

use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected ArticleCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);
        // Categories are GLOBAL, not per-company
        $this->category = ArticleCategory::firstOrCreate(
            ['code' => 'SECURITY_PRIVACY'],
            [
                'name' => 'Security & Privacy',
                'slug' => 'security-privacy',
                'description' => 'Security and privacy related articles',
                'icon' => 'shield',
                'color' => '#10b981',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
    }

    /**
     * Test 1: USER can only read PUBLISHED content from companies they follow
     */
    public function test_user_can_only_read_published_content(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $this->companyA->followers()->attach($user->id);

        // Create PUBLISHED and DRAFT articles
        $publishedArticle = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        $draftArticle = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'DRAFT',
            'category_id' => $this->category->id,
        ]);

        // Can read PUBLISHED
        $response = $this->authenticateWithJWT($user)->getJson("/api/help-center/articles/{$publishedArticle->id}");
        $response->assertStatus(200);

        // Cannot read DRAFT
        $response = $this->authenticateWithJWT($user)->getJson("/api/help-center/articles/{$draftArticle->id}");
        $response->assertStatus(403);

        // Cannot CREATE
        $response = $this->authenticateWithJWT($user)->postJson('/api/help-center/articles', [
            'category_id' => $this->category->id,
            'title' => 'Test Article',
            'content' => 'This is a test article with enough content to pass validation requirements which need 50+ characters.',
        ]);
        $response->assertStatus(403);

        // Cannot UPDATE
        $response = $this->authenticateWithJWT($user)->putJson("/api/help-center/articles/{$publishedArticle->id}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(403);

        // Cannot DELETE
        $response = $this->authenticateWithJWT($user)->deleteJson("/api/help-center/articles/{$publishedArticle->id}");
        $response->assertStatus(403);
    }

    /**
     * Test 2: AGENT can only read PUBLISHED content of their own company
     */
    public function test_agent_can_only_read_published_content_of_own_company(): void
    {
        $agent = User::factory()->withRole('AGENT', $this->companyA->id)->create();

        $publishedArticle = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        $draftArticle = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'DRAFT',
            'category_id' => $this->category->id,
        ]);

        // Can read PUBLISHED of own company
        $response = $this->authenticateWithJWT($agent)->getJson("/api/help-center/articles/{$publishedArticle->id}");
        $response->assertStatus(200);

        // Cannot read DRAFT (AGENT has same read-only access as USER)
        $response = $this->authenticateWithJWT($agent)->getJson("/api/help-center/articles/{$draftArticle->id}");
        $response->assertStatus(403);

        // Cannot CREATE
        $response = $this->authenticateWithJWT($agent)->postJson('/api/help-center/articles', [
            'category_id' => $this->category->id,
            'title' => 'Test Article',
            'content' => 'This is a test article with enough content to pass validation requirements which need 50+ characters.',
        ]);
        $response->assertStatus(403);

        // Cannot UPDATE
        $response = $this->authenticateWithJWT($agent)->putJson("/api/help-center/articles/{$publishedArticle->id}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(403);

        // Cannot DELETE
        $response = $this->authenticateWithJWT($agent)->deleteJson("/api/help-center/articles/{$publishedArticle->id}");
        $response->assertStatus(403);
    }

    /**
     * Test 3: COMPANY_ADMIN has full CRUD on own company content
     */
    public function test_company_admin_has_full_crud_on_own_company_content(): void
    {
        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        // CREATE
        $response = $this->authenticateWithJWT($admin)->postJson('/api/help-center/articles', [
            'category_id' => $this->category->id,
            'title' => 'Test Article',
            'content' => 'This is a test article with enough content to pass validation requirements which need 50+ characters.',
        ]);
        $response->assertStatus(201);
        $articleId = $response->json('data.id');

        // READ
        $response = $this->authenticateWithJWT($admin)->getJson("/api/help-center/articles/{$articleId}");
        $response->assertStatus(200);

        // UPDATE
        $response = $this->authenticateWithJWT($admin)->putJson("/api/help-center/articles/{$articleId}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(200);

        // PUBLISH
        $response = $this->authenticateWithJWT($admin)->postJson("/api/help-center/articles/{$articleId}/publish");
        $response->assertStatus(200);

        // UNPUBLISH
        $response = $this->authenticateWithJWT($admin)->postJson("/api/help-center/articles/{$articleId}/unpublish");
        $response->assertStatus(200);

        // DELETE
        $response = $this->authenticateWithJWT($admin)->deleteJson("/api/help-center/articles/{$articleId}");
        $response->assertStatus(200);
    }

    /**
     * Test 4: COMPANY_ADMIN cannot access other company content
     */
    public function test_company_admin_cannot_access_other_company_content(): void
    {
        $adminA = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        $categoryB = ArticleCategory::factory()->create();
        $articleB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'status' => 'PUBLISHED',
            'category_id' => $categoryB->id,
        ]);

        // Cannot READ other company content
        $response = $this->authenticateWithJWT($adminA)->getJson("/api/help-center/articles/{$articleB->id}");
        $response->assertStatus(403);

        // Cannot UPDATE other company content
        $response = $this->authenticateWithJWT($adminA)->putJson("/api/help-center/articles/{$articleB->id}", [
            'title' => 'Hacked Title',
        ]);
        $response->assertStatus(403);

        // Cannot DELETE other company content
        $response = $this->authenticateWithJWT($adminA)->deleteJson("/api/help-center/articles/{$articleB->id}");
        $response->assertStatus(403);

        // Cannot PUBLISH other company content
        $response = $this->authenticateWithJWT($adminA)->postJson("/api/help-center/articles/{$articleB->id}/publish");
        $response->assertStatus(403);
    }

    /**
     * Test 5: PLATFORM_ADMIN has read-only access to all content
     */
    public function test_platform_admin_has_read_only_access_to_all_content(): void
    {
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $articleA = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'DRAFT',
            'category_id' => $this->category->id,
        ]);

        $categoryB = ArticleCategory::factory()->create();
        $articleB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'status' => 'PUBLISHED',
            'category_id' => $categoryB->id,
        ]);

        // Can READ all content (even DRAFT from different companies)
        $response = $this->authenticateWithJWT($platformAdmin)->getJson("/api/help-center/articles/{$articleA->id}");
        $response->assertStatus(200);

        $response = $this->authenticateWithJWT($platformAdmin)->getJson("/api/help-center/articles/{$articleB->id}");
        $response->assertStatus(200);

        // Can LIST all articles
        $response = $this->authenticateWithJWT($platformAdmin)->getJson('/api/help-center/articles');
        $response->assertStatus(200);

        // Cannot CREATE (read-only)
        $response = $this->authenticateWithJWT($platformAdmin)->postJson('/api/help-center/articles', [
            'category_id' => $this->category->id,
            'title' => 'Test Article',
            'content' => 'This is a test article with enough content to pass validation requirements which need 50+ characters.',
        ]);
        $response->assertStatus(403);

        // Cannot UPDATE (read-only)
        $response = $this->authenticateWithJWT($platformAdmin)->putJson("/api/help-center/articles/{$articleA->id}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(403);

        // Cannot DELETE (read-only)
        $response = $this->authenticateWithJWT($platformAdmin)->deleteJson("/api/help-center/articles/{$articleA->id}");
        $response->assertStatus(403);

        // Cannot PUBLISH (read-only)
        $response = $this->authenticateWithJWT($platformAdmin)->postJson("/api/help-center/articles/{$articleA->id}/publish");
        $response->assertStatus(403);
    }

    /**
     * Test 6: Unauthenticated user can only access categories
     */
    public function test_unauthenticated_user_can_only_access_categories(): void
    {
        // Can access categories endpoint
        $response = $this->getJson('/api/help-center/categories');
        $response->assertStatus(200);

        // Cannot access articles list
        $response = $this->getJson('/api/help-center/articles');
        $response->assertStatus(401);

        // Cannot access specific article
        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/help-center/articles/{$article->id}");
        $response->assertStatus(401);

        // Cannot access announcements
        $response = $this->getJson('/api/announcements');
        $response->assertStatus(401);
    }

    /**
     * Test 7: COMPANY_ADMIN cannot create content for other company
     */
    public function test_company_admin_cannot_create_content_for_other_company(): void
    {
        $adminA = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();
        $categoryB = ArticleCategory::factory()->create();

        // Attempt to create article for Company B (should fail with role check before validation)
        $response = $this->authenticateWithJWT($adminA)->postJson('/api/help-center/articles', [
            'category_id' => $categoryB->id, // Different company's category
            'title' => 'Cross-Company Article',
            'content' => 'This should not be allowed because admin is not from this company and validation should fail with 403 first',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 8: Role validation happens before business logic
     */
    public function test_role_validation_happens_before_business_logic(): void
    {
        $user = User::factory()->withRole('USER')->create();

        // Attempt to create article with invalid data
        // Should fail with 403 (role check) before 422 (validation)
        $response = $this->authenticateWithJWT($user)->postJson('/api/help-center/articles', [
            'category_id' => 'invalid-uuid',
            'title' => '', // Invalid: empty title
            'content' => '', // Invalid: empty content
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'You do not have permission to create articles',
        ]);
    }

    /**
     * Test 9: USER attempting admin action gets clear error
     */
    public function test_user_attempting_admin_action_gets_clear_error(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $this->companyA->followers()->attach($user->id);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        // Attempt to PUBLISH (admin-only action)
        $response = $this->authenticateWithJWT($user)->postJson("/api/help-center/articles/{$article->id}/publish");
        $response->assertStatus(403);
        $response->assertJsonStructure([
            'message',
            'success',
        ]);

        // Attempt to DELETE (admin-only action)
        $response = $this->authenticateWithJWT($user)->deleteJson("/api/help-center/articles/{$article->id}");
        $response->assertStatus(403);
    }

    /**
     * Test 10: Suspended user cannot access any endpoint
     */
    public function test_suspended_user_cannot_access_any_endpoint(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $user->update(['status' => 'SUSPENDED']);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        // Cannot LIST articles - JWT middleware rejects suspended users
        $response = $this->authenticateWithJWT($user)->getJson('/api/help-center/articles');
        $response->assertStatus(401);

        // Cannot READ article
        $response = $this->authenticateWithJWT($user)->getJson("/api/help-center/articles/{$article->id}");
        $response->assertStatus(401);

        // Cannot access categories
        $response = $this->authenticateWithJWT($user)->getJson('/api/help-center/categories');
        $response->assertStatus(401);
    }

    /**
     * Test 11: Expired token returns 401
     */
    public function test_expired_token_returns_401(): void
    {
        // Simulate expired token by using invalid Bearer token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.expired.token',
        ])->getJson('/api/help-center/articles');

        $response->assertStatus(401);
        $response->assertJsonFragment([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * Test 12: Invalid token returns 401
     */
    public function test_invalid_token_returns_401(): void
    {
        // Test with malformed token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-format',
        ])->getJson('/api/help-center/articles');

        $response->assertStatus(401);

        // Test with missing Authorization header
        $response = $this->getJson('/api/help-center/articles');
        $response->assertStatus(401);

        // Test with wrong auth scheme
        $response = $this->withHeaders([
            'Authorization' => 'Basic dXNlcjpwYXNz',
        ])->getJson('/api/help-center/articles');
        $response->assertStatus(401);
    }

    /**
     * Test 13: User with multiple roles gets correct permissions per company
     */
    public function test_user_with_multiple_roles_gets_correct_permissions_per_company(): void
    {
        $user = User::factory()->create();

        // Assign multiple roles
        $user->assignRole('COMPANY_ADMIN', $this->companyA->id); // Admin in Company A
        $user->assignRole('USER'); // Regular user globally
        $this->companyB->followers()->attach($user->id); // Follows Company B

        // In Company A: Can CREATE (is ADMIN)
        $response = $this->authenticateWithJWT($user)->postJson('/api/help-center/articles', [
            'category_id' => $this->category->id,
            'title' => 'Article for Company A',
            'content' => 'Content for A with enough characters to meet the 50 character minimum requirement for article content validation',
        ]);
        $response->assertStatus(201);
        $articleAId = $response->json('data.id');

        // In Company A: Can UPDATE (is ADMIN)
        $response = $this->authenticateWithJWT($user)->putJson("/api/help-center/articles/{$articleAId}", [
            'title' => 'Updated Title A',
        ]);
        $response->assertStatus(200);

        // In Company A: Can DELETE (is ADMIN)
        $response = $this->authenticateWithJWT($user)->deleteJson("/api/help-center/articles/{$articleAId}");
        $response->assertStatus(200);

        // In Company B: Can READ PUBLISHED (follows company)
        $categoryB = ArticleCategory::factory()->create();
        $publishedB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'status' => 'PUBLISHED',
            'category_id' => $categoryB->id,
        ]);

        $response = $this->authenticateWithJWT($user)->getJson("/api/help-center/articles/{$publishedB->id}");
        $response->assertStatus(200);

        // In Company B: Cannot CREATE (not admin there)
        $response = $this->authenticateWithJWT($user)->postJson('/api/help-center/articles', [
            'category_id' => $categoryB->id,
            'title' => 'Article for Company B',
            'content' => 'Content for B with enough characters to meet the 50 character minimum requirement for article content validation',
        ]);
        $response->assertStatus(403);

        // In Company B: Cannot UPDATE (not admin there)
        $response = $this->authenticateWithJWT($user)->putJson("/api/help-center/articles/{$publishedB->id}", [
            'title' => 'Hacked Title B',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test 14: AGENT assigned to company can only read PUBLISHED of own company
     */
    public function test_agent_assigned_to_company_can_only_read_published_of_own_company(): void
    {
        $agent = User::factory()->withRole('AGENT', $this->companyA->id)->create();

        // Create articles with different statuses in Company A
        $published = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        $draft = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'DRAFT',
            'category_id' => $this->category->id,
        ]);

        // Can READ PUBLISHED
        $response = $this->authenticateWithJWT($agent)->getJson("/api/help-center/articles/{$published->id}");
        $response->assertStatus(200);

        // Cannot READ DRAFT
        $response = $this->authenticateWithJWT($agent)->getJson("/api/help-center/articles/{$draft->id}");
        $response->assertStatus(403);

        // Can LIST PUBLISHED articles only
        $response = $this->authenticateWithJWT($agent)->getJson('/api/help-center/articles');
        $response->assertStatus(200);
        $articles = $response->json('data');

        // Should only see PUBLISHED articles
        foreach ($articles as $article) {
            $this->assertEquals('PUBLISHED', $article['status']);
        }
    }

    /**
     * Test 15: AGENT cannot access different company content
     */
    public function test_agent_cannot_access_different_company_content(): void
    {
        $agentA = User::factory()->withRole('AGENT', $this->companyA->id)->create();

        $categoryB = ArticleCategory::factory()->create();
        $articleB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'status' => 'PUBLISHED',
            'category_id' => $categoryB->id,
        ]);

        // Cannot READ other company content (even PUBLISHED)
        $response = $this->authenticateWithJWT($agentA)->getJson("/api/help-center/articles/{$articleB->id}");
        $response->assertStatus(403);

        // Cannot CREATE for other company
        $response = $this->authenticateWithJWT($agentA)->postJson('/api/help-center/articles', [
            'category_id' => $categoryB->id,
            'title' => 'Cross-Company Article',
            'content' => 'Not allowed with enough characters to meet the validation requirements of 50 minimum characters',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test 16: Role permissions across all statuses (DRAFT, PUBLISHED)
     */
    public function test_role_permissions_across_all_statuses(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $this->companyA->followers()->attach($user->id);

        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        // Create articles in all valid statuses (Articles only have DRAFT and PUBLISHED)
        $draft = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'DRAFT',
            'category_id' => $this->category->id,
        ]);

        $published = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        // USER can only see PUBLISHED
        $this->authenticateWithJWT($user)->getJson("/api/help-center/articles/{$draft->id}")
            ->assertStatus(403);

        $this->authenticateWithJWT($user)->getJson("/api/help-center/articles/{$published->id}")
            ->assertStatus(200);

        // ADMIN can see both DRAFT and PUBLISHED
        $this->authenticateWithJWT($admin)->getJson("/api/help-center/articles/{$draft->id}")
            ->assertStatus(200);

        $this->authenticateWithJWT($admin)->getJson("/api/help-center/articles/{$published->id}")
            ->assertStatus(200);

        // ADMIN can transition between statuses
        $this->authenticateWithJWT($admin)->postJson("/api/help-center/articles/{$draft->id}/publish")
            ->assertStatus(200);

        $this->authenticateWithJWT($admin)->postJson("/api/help-center/articles/{$draft->id}/unpublish")
            ->assertStatus(200);
    }

    /**
     * Test 17: USER cannot perform any write action (CREATE, UPDATE, PUBLISH, DELETE)
     */
    public function test_user_cannot_perform_any_write_action(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $this->companyA->followers()->attach($user->id);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        // Cannot CREATE
        $response = $this->authenticateWithJWT($user)->postJson('/api/help-center/articles', [
            'category_id' => $this->category->id,
            'title' => 'New Article',
            'content' => 'New Content with enough characters to meet the minimum validation requirements for articles',
        ]);
        $response->assertStatus(403);

        // Cannot UPDATE
        $response = $this->authenticateWithJWT($user)->putJson("/api/help-center/articles/{$article->id}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(403);

        // Cannot PUBLISH
        $response = $this->authenticateWithJWT($user)->postJson("/api/help-center/articles/{$article->id}/publish");
        $response->assertStatus(403);

        // Cannot UNPUBLISH
        $response = $this->authenticateWithJWT($user)->postJson("/api/help-center/articles/{$article->id}/unpublish");
        $response->assertStatus(403);

        // Cannot DELETE
        $response = $this->authenticateWithJWT($user)->deleteJson("/api/help-center/articles/{$article->id}");
        $response->assertStatus(403);

        // Verify article still exists (no write operations succeeded)
        $this->assertDatabaseHas('content_mgmt.help_center_articles', [
            'id' => $article->id,
            'title' => $article->title, // Title unchanged
            'status' => 'PUBLISHED', // Status unchanged
            'deleted_at' => null, // Not soft-deleted
        ]);
    }
}
