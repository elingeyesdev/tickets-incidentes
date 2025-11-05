<?php

namespace Tests\Feature\ContentManagement\Articles;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Support\Str;

/**
 * Tests para la eliminación de artículos del Help Center
 *
 * Cubre:
 * - Eliminación de artículos en estado DRAFT
 * - Prevención de eliminación de artículos PUBLISHED
 * - Soft delete verification (GET 404)
 * - Autorización: solo COMPANY_ADMIN
 * - Cross-company authorization checks
 * - Edge cases (DELETE inexistente, idempotencia)
 */
class DeleteArticleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: COMPANY_ADMIN puede eliminar artículo en DRAFT
     */
    public function test_company_admin_can_delete_draft_article(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $category = ArticleCategory::factory()->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Artículo eliminado permanentemente'
            ]);

        // Verificar que fue eliminado (soft delete)
        $this->assertSoftDeleted('business.help_center_articles', ['id' => $article->id]);
    }

    /**
     * Test: No se puede eliminar artículo PUBLISHED
     */
    public function test_cannot_delete_published_article(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $category = ArticleCategory::factory()->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'CANNOT_DELETE_PUBLISHED_ARTICLE'
            ])
            ->assertJsonFragment([
                'message' => 'No se puede eliminar un artículo publicado'
            ]);

        // Verificar que NO fue eliminado
        $this->assertDatabaseHas('business.help_center_articles', [
            'id' => $article->id,
            'status' => 'PUBLISHED'
        ]);
    }

    /**
     * Test: Artículo eliminado retorna 404 en GET
     */
    public function test_deleted_article_returns_404(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $category = ArticleCategory::factory()->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'DRAFT'
        ]);

        // Act: Eliminar
        $deleteResponse = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/help-center/articles/{$article->id}");
        $deleteResponse->assertStatus(200);

        // Act: Intentar GET (debe ser 404)
        $getResponse = $this->authenticateWithJWT($admin)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $getResponse->assertStatus(404);
    }

    /**
     * Test: COMPANY_ADMIN no puede eliminar artículo de otra empresa
     */
    public function test_company_admin_cannot_delete_article_from_other_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create(['status' => 'active']);
        $companyB = Company::factory()->create(['status' => 'active']);

        $adminA = User::factory()->withRole('COMPANY_ADMIN', $companyA->id)->create();
        $adminB = User::factory()->withRole('COMPANY_ADMIN', $companyB->id)->create();

        $category = ArticleCategory::factory()->create();

        $articleInCompanyA = HelpCenterArticle::factory()->create([
            'company_id' => $companyA->id,
            'author_id' => $adminA->id,
            'category_id' => $category->id,
            'status' => 'DRAFT'
        ]);

        // Act: AdminB intenta eliminar artículo de CompanyA
        $response = $this->authenticateWithJWT($adminB)
            ->deleteJson("/api/help-center/articles/{$articleInCompanyA->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        // Verificar que NO fue eliminado
        $this->assertDatabaseHas('business.help_center_articles', [
            'id' => $articleInCompanyA->id,
            'status' => 'DRAFT',
            'deleted_at' => null
        ]);
    }

    /**
     * Test: END_USER no puede eliminar artículos
     */
    public function test_end_user_cannot_delete_article(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $endUser = User::factory()->withRole('USER')->create();

        // End user sigue la empresa
        $company->followers()->attach($endUser->id);

        $category = ArticleCategory::factory()->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'DRAFT'
        ]);

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->deleteJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        // Verificar que NO fue eliminado
        $this->assertDatabaseHas('business.help_center_articles', [
            'id' => $article->id,
            'deleted_at' => null
        ]);
    }

    /**
     * Test: DELETE a UUID inexistente retorna 404 (NUEVO - OPCIÓN B)
     */
    public function test_delete_nonexistent_article_returns_404(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $fakeId = Str::uuid()->toString(); // UUID válido pero nunca existió

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/help-center/articles/{$fakeId}");

        // Assert
        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => false
            ]);
    }

    /**
     * Test: DELETE es idempotente (eliminar dos veces) (NUEVO - OPCIÓN B)
     */
    public function test_delete_is_idempotent(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $category = ArticleCategory::factory()->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'DRAFT'
        ]);

        $articleId = $article->id;

        // Act: Primera DELETE
        $response1 = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/help-center/articles/{$articleId}");

        // Assert: Primera DELETE OK
        $response1->assertStatus(200)
            ->assertJson(['success' => true]);

        // Act: Segunda DELETE (idempotencia)
        $response2 = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/help-center/articles/{$articleId}");

        // Assert: Segunda DELETE es 404 (ya fue eliminado)
        $response2->assertStatus(404)
            ->assertJsonStructure(['success', 'message'])
            ->assertJson(['success' => false]);
    }
}
