<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Permissions;

use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Models\Category;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);
        $this->category = Category::factory()->create(['company_id' => $this->companyA->id]);
    }

    /**
     * Test 1: USER can only read PUBLISHED content from companies they follow
     */
    public function test_user_can_only_read_published_content(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $user->followCompanies([$this->companyA->id]);

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
        $response = $this->actingAs($user)->getJson("/api/articles/{$publishedArticle->id}");
        $response->assertStatus(200);

        // Cannot read DRAFT
        $response = $this->actingAs($user)->getJson("/api/articles/{$draftArticle->id}");
        $response->assertStatus(403);

        // Cannot CREATE
        $response = $this->actingAs($user)->postJson('/api/articles', [
            'category_id' => $this->category->id,
            'title' => 'Test Article',
            'content' => 'Test content',
        ]);
        $response->assertStatus(403);

        // Cannot UPDATE
        $response = $this->actingAs($user)->putJson("/api/articles/{$publishedArticle->id}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(403);

        // Cannot DELETE
        $response = $this->actingAs($user)->deleteJson("/api/articles/{$publishedArticle->id}");
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
        $response = $this->actingAs($agent)->getJson("/api/articles/{$publishedArticle->id}");
        $response->assertStatus(200);

        // Cannot read DRAFT (AGENT has same read-only access as USER)
        $response = $this->actingAs($agent)->getJson("/api/articles/{$draftArticle->id}");
        $response->assertStatus(403);

        // Cannot CREATE
        $response = $this->actingAs($agent)->postJson('/api/articles', [
            'category_id' => $this->category->id,
            'title' => 'Test Article',
            'content' => 'Test content',
        ]);
        $response->assertStatus(403);

        // Cannot UPDATE
        $response = $this->actingAs($agent)->putJson("/api/articles/{$publishedArticle->id}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(403);

        // Cannot DELETE
        $response = $this->actingAs($agent)->deleteJson("/api/articles/{$publishedArticle->id}");
        $response->assertStatus(403);
    }

    /**
     * Test 3: COMPANY_ADMIN has full CRUD on own company content
     */
    public function test_company_admin_has_full_crud_on_own_company_content(): void
    {
        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        // CREATE
        $response = $this->actingAs($admin)->postJson('/api/articles', [
            'category_id' => $this->category->id,
            'title' => 'Test Article',
            'content' => 'Test content',
        ]);
        $response->assertStatus(201);
        $articleId = $response->json('data.id');

        // READ
        $response = $this->actingAs($admin)->getJson("/api/articles/{$articleId}");
        $response->assertStatus(200);

        // UPDATE
        $response = $this->actingAs($admin)->putJson("/api/articles/{$articleId}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(200);

        // PUBLISH
        $response = $this->actingAs($admin)->postJson("/api/articles/{$articleId}/publish");
        $response->assertStatus(200);

        // UNPUBLISH
        $response = $this->actingAs($admin)->postJson("/api/articles/{$articleId}/unpublish");
        $response->assertStatus(200);

        // DELETE
        $response = $this->actingAs($admin)->deleteJson("/api/articles/{$articleId}");
        $response->assertStatus(200);
    }

    /**
     * Test 4: COMPANY_ADMIN cannot access other company content
     */
    public function test_company_admin_cannot_access_other_company_content(): void
    {
        $adminA = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        $categoryB = Category::factory()->create(['company_id' => $this->companyB->id]);
        $articleB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'status' => 'PUBLISHED',
            'category_id' => $categoryB->id,
        ]);

        // Cannot READ other company content
        $response = $this->actingAs($adminA)->getJson("/api/articles/{$articleB->id}");
        $response->assertStatus(403);

        // Cannot UPDATE other company content
        $response = $this->actingAs($adminA)->putJson("/api/articles/{$articleB->id}", [
            'title' => 'Hacked Title',
        ]);
        $response->assertStatus(403);

        // Cannot DELETE other company content
        $response = $this->actingAs($adminA)->deleteJson("/api/articles/{$articleB->id}");
        $response->assertStatus(403);

        // Cannot PUBLISH other company content
        $response = $this->actingAs($adminA)->postJson("/api/articles/{$articleB->id}/publish");
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

        $categoryB = Category::factory()->create(['company_id' => $this->companyB->id]);
        $articleB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'status' => 'PUBLISHED',
            'category_id' => $categoryB->id,
        ]);

        // Can READ all content (even DRAFT from different companies)
        $response = $this->actingAs($platformAdmin)->getJson("/api/articles/{$articleA->id}");
        $response->assertStatus(200);

        $response = $this->actingAs($platformAdmin)->getJson("/api/articles/{$articleB->id}");
        $response->assertStatus(200);

        // Can LIST all articles
        $response = $this->actingAs($platformAdmin)->getJson('/api/articles');
        $response->assertStatus(200);

        // Cannot CREATE (read-only)
        $response = $this->actingAs($platformAdmin)->postJson('/api/articles', [
            'category_id' => $this->category->id,
            'title' => 'Test Article',
            'content' => 'Test content',
        ]);
        $response->assertStatus(403);

        // Cannot UPDATE (read-only)
        $response = $this->actingAs($platformAdmin)->putJson("/api/articles/{$articleA->id}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(403);

        // Cannot DELETE (read-only)
        $response = $this->actingAs($platformAdmin)->deleteJson("/api/articles/{$articleA->id}");
        $response->assertStatus(403);

        // Cannot PUBLISH (read-only)
        $response = $this->actingAs($platformAdmin)->postJson("/api/articles/{$articleA->id}/publish");
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
        $response = $this->getJson('/api/articles');
        $response->assertStatus(401);

        // Cannot access specific article
        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/articles/{$article->id}");
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
        $categoryB = Category::factory()->create(['company_id' => $this->companyB->id]);

        // Attempt to create article for Company B (should fail)
        $response = $this->actingAs($adminA)->postJson('/api/articles', [
            'category_id' => $categoryB->id, // Category belongs to Company B
            'title' => 'Cross-Company Article',
            'content' => 'This should not be allowed',
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'You can only create content for your own company',
        ]);
    }

    /**
     * Test 8: Role validation happens before business logic
     */
    public function test_role_validation_happens_before_business_logic(): void
    {
        $user = User::factory()->withRole('USER')->create();

        // Attempt to create article with invalid data
        // Should fail with 403 (role check) before 422 (validation)
        $response = $this->actingAs($user)->postJson('/api/articles', [
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
        $user->followCompanies([$this->companyA->id]);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        // Attempt to PUBLISH (admin-only action)
        $response = $this->actingAs($user)->postJson("/api/articles/{$article->id}/publish");
        $response->assertStatus(403);
        $response->assertJsonStructure([
            'message',
            'error_code',
        ]);
        $response->assertJsonFragment([
            'error_code' => 'INSUFFICIENT_PERMISSIONS',
        ]);

        // Attempt to DELETE (admin-only action)
        $response = $this->actingAs($user)->deleteJson("/api/articles/{$article->id}");
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'error_code' => 'INSUFFICIENT_PERMISSIONS',
        ]);
    }

    /**
     * Test 10: Suspended user cannot access any endpoint
     */
    public function test_suspended_user_cannot_access_any_endpoint(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $user->update(['status' => 'suspended']);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        // Cannot LIST articles
        $response = $this->actingAs($user)->getJson('/api/articles');
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'Your account has been suspended',
        ]);

        // Cannot READ article
        $response = $this->actingAs($user)->getJson("/api/articles/{$article->id}");
        $response->assertStatus(403);

        // Cannot access categories
        $response = $this->actingAs($user)->getJson('/api/help-center/categories');
        $response->assertStatus(403);
    }

    /**
     * Test 11: Expired token returns 401
     */
    public function test_expired_token_returns_401(): void
    {
        // Simulate expired token by using invalid Bearer token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.expired.token',
        ])->getJson('/api/articles');

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
        ])->getJson('/api/articles');

        $response->assertStatus(401);

        // Test with missing Authorization header
        $response = $this->getJson('/api/articles');
        $response->assertStatus(401);

        // Test with wrong auth scheme
        $response = $this->withHeaders([
            'Authorization' => 'Basic dXNlcjpwYXNz',
        ])->getJson('/api/articles');
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
        $user->followCompanies([$this->companyB->id]); // Follows Company B

        // In Company A: Can CREATE (is ADMIN)
        $response = $this->actingAs($user)->postJson('/api/articles', [
            'category_id' => $this->category->id,
            'title' => 'Article for Company A',
            'content' => 'Content for A',
        ]);
        $response->assertStatus(201);
        $articleAId = $response->json('data.id');

        // In Company A: Can UPDATE (is ADMIN)
        $response = $this->actingAs($user)->putJson("/api/articles/{$articleAId}", [
            'title' => 'Updated Title A',
        ]);
        $response->assertStatus(200);

        // In Company A: Can DELETE (is ADMIN)
        $response = $this->actingAs($user)->deleteJson("/api/articles/{$articleAId}");
        $response->assertStatus(200);

        // In Company B: Can READ PUBLISHED (follows company)
        $categoryB = Category::factory()->create(['company_id' => $this->companyB->id]);
        $publishedB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'status' => 'PUBLISHED',
            'category_id' => $categoryB->id,
        ]);

        $response = $this->actingAs($user)->getJson("/api/articles/{$publishedB->id}");
        $response->assertStatus(200);

        // In Company B: Cannot CREATE (not admin there)
        $response = $this->actingAs($user)->postJson('/api/articles', [
            'category_id' => $categoryB->id,
            'title' => 'Article for Company B',
            'content' => 'Content for B',
        ]);
        $response->assertStatus(403);

        // In Company B: Cannot UPDATE (not admin there)
        $response = $this->actingAs($user)->putJson("/api/articles/{$publishedB->id}", [
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

        $archived = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'ARCHIVED',
            'category_id' => $this->category->id,
        ]);

        // Can READ PUBLISHED
        $response = $this->actingAs($agent)->getJson("/api/articles/{$published->id}");
        $response->assertStatus(200);

        // Cannot READ DRAFT
        $response = $this->actingAs($agent)->getJson("/api/articles/{$draft->id}");
        $response->assertStatus(403);

        // Cannot READ ARCHIVED
        $response = $this->actingAs($agent)->getJson("/api/articles/{$archived->id}");
        $response->assertStatus(403);

        // Can LIST PUBLISHED articles only
        $response = $this->actingAs($agent)->getJson('/api/articles');
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

        $categoryB = Category::factory()->create(['company_id' => $this->companyB->id]);
        $articleB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'status' => 'PUBLISHED',
            'category_id' => $categoryB->id,
        ]);

        // Cannot READ other company content (even PUBLISHED)
        $response = $this->actingAs($agentA)->getJson("/api/articles/{$articleB->id}");
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'You can only access content from your assigned company',
        ]);

        // Cannot CREATE for other company
        $response = $this->actingAs($agentA)->postJson('/api/articles', [
            'category_id' => $categoryB->id,
            'title' => 'Cross-Company Article',
            'content' => 'Not allowed',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test 16: Role permissions across all statuses (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED)
     */
    public function test_role_permissions_across_all_statuses(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $user->followCompanies([$this->companyA->id]);

        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        // Create articles in all statuses
        $draft = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'DRAFT',
            'category_id' => $this->category->id,
        ]);

        $scheduled = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'SCHEDULED',
            'category_id' => $this->category->id,
            'published_at' => now()->addDay(),
        ]);

        $published = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        $archived = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'ARCHIVED',
            'category_id' => $this->category->id,
        ]);

        // USER can only see PUBLISHED
        $this->actingAs($user)->getJson("/api/articles/{$draft->id}")
            ->assertStatus(403);

        $this->actingAs($user)->getJson("/api/articles/{$scheduled->id}")
            ->assertStatus(403);

        $this->actingAs($user)->getJson("/api/articles/{$published->id}")
            ->assertStatus(200);

        $this->actingAs($user)->getJson("/api/articles/{$archived->id}")
            ->assertStatus(403);

        // ADMIN can see all statuses
        $this->actingAs($admin)->getJson("/api/articles/{$draft->id}")
            ->assertStatus(200);

        $this->actingAs($admin)->getJson("/api/articles/{$scheduled->id}")
            ->assertStatus(200);

        $this->actingAs($admin)->getJson("/api/articles/{$published->id}")
            ->assertStatus(200);

        $this->actingAs($admin)->getJson("/api/articles/{$archived->id}")
            ->assertStatus(200);

        // ADMIN can transition between statuses
        $this->actingAs($admin)->postJson("/api/articles/{$draft->id}/publish")
            ->assertStatus(200);

        $this->actingAs($admin)->postJson("/api/articles/{$draft->id}/unpublish")
            ->assertStatus(200);
    }

    /**
     * Test 17: USER cannot perform any write action (CREATE, UPDATE, PUBLISH, DELETE)
     */
    public function test_user_cannot_perform_any_write_action(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $user->followCompanies([$this->companyA->id]);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'PUBLISHED',
            'category_id' => $this->category->id,
        ]);

        // Cannot CREATE
        $response = $this->actingAs($user)->postJson('/api/articles', [
            'category_id' => $this->category->id,
            'title' => 'New Article',
            'content' => 'New Content',
        ]);
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'You do not have permission to create articles',
        ]);

        // Cannot UPDATE
        $response = $this->actingAs($user)->putJson("/api/articles/{$article->id}", [
            'title' => 'Updated Title',
        ]);
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'You do not have permission to update articles',
        ]);

        // Cannot PUBLISH
        $response = $this->actingAs($user)->postJson("/api/articles/{$article->id}/publish");
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'You do not have permission to publish articles',
        ]);

        // Cannot UNPUBLISH
        $response = $this->actingAs($user)->postJson("/api/articles/{$article->id}/unpublish");
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'You do not have permission to unpublish articles',
        ]);

        // Cannot DELETE
        $response = $this->actingAs($user)->deleteJson("/api/articles/{$article->id}");
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'You do not have permission to delete articles',
        ]);

        // Verify article still exists (no write operations succeeded)
        $this->assertDatabaseHas('content_mgmt.help_center_articles', [
            'id' => $article->id,
            'title' => $article->title, // Title unchanged
            'status' => 'PUBLISHED', // Status unchanged
            'deleted_at' => null, // Not soft-deleted
        ]);
    }
}
