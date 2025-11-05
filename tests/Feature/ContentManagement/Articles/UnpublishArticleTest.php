<?php

namespace Tests\Feature\ContentManagement\Articles;

use Tests\TestCase;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests para la despublicación de artículos del Help Center
 *
 * Cubre:
 * - Despublicación de artículos en estado PUBLISHED
 * - Validación de published_at = null
 * - Invisibilidad para END_USER después de despublicar
 * - Prevención de despublicar artículos en DRAFT
 * - Preservación de views_count al despublicar
 */
class UnpublishArticleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: COMPANY_ADMIN puede despublicar un artículo publicado
     *
     * Arrange:
     * - Usuario COMPANY_ADMIN
     * - Artículo en estado PUBLISHED
     *
     * Act:
     * - POST /api/help-center/articles/:id/unpublish
     *
     * Assert:
     * - Response 200 OK
     * - status = DRAFT
     * - published_at = null
     * - Mensaje de éxito
     */
    public function test_company_admin_can_unpublish_article(): void
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
            'published_at' => now()->subDays(5)
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/help-center/articles/{$article->id}/unpublish");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'status', 'published_at']
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Artículo despublicado y regresado a borrador',
                'data' => [
                    'id' => $article->id,
                    'status' => 'DRAFT',
                    'published_at' => null
                ]
            ]);

        $this->assertDatabaseHas('business.help_center_articles', [
            'id' => $article->id,
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        $article->refresh();
        $this->assertEquals('DRAFT', $article->status);
        $this->assertNull($article->published_at);
    }

    /**
     * Test: Despublicar establece published_at a null
     *
     * Arrange:
     * - Artículo PUBLISHED con published_at = "2025-11-02T23:00:00Z"
     *
     * Act:
     * - POST /api/help-center/articles/:id/unpublish
     *
     * Assert:
     * - published_at = null
     */
    public function test_unpublish_sets_published_at_to_null(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();

        $category = ArticleCategory::factory()->create();

        $publishedDate = now()->subDays(10);
        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'PUBLISHED',
            'published_at' => $publishedDate
        ]);

        // Verificar estado inicial
        $this->assertNotNull($article->published_at);
        $this->assertEquals($publishedDate->toDateTimeString(), $article->published_at->toDateTimeString());

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/help-center/articles/{$article->id}/unpublish");

        // Assert
        $response->assertStatus(200);

        $article->refresh();
        $this->assertNull($article->published_at);
    }

    /**
     * Test: Artículo despublicado no es visible para END_USER
     *
     * Arrange:
     * - Artículo PUBLISHED visible para END_USER
     * - COMPANY_ADMIN lo despublica
     *
     * Act:
     * - END_USER lista artículos (GET /api/help-center/articles)
     *
     * Assert:
     * - Artículo NO aparece en la lista
     */
    public function test_unpublished_article_not_visible_to_end_users(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        // USER role NO requiere company_id (es global)
        $endUser = User::factory()->withRole('USER')->create();

        // END_USER debe seguir la empresa para ver sus artículos
        $company->followers()->attach($endUser->id);

        $category = ArticleCategory::factory()->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3)
        ]);

        // Verificar que END_USER puede ver el artículo inicialmente
        $initialListResponse = $this->authenticateWithJWT($endUser)
            ->getJson('/api/help-center/articles');

        $initialListResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $article->id]);

        // Act: Admin despublica el artículo
        $unpublishResponse = $this->authenticateWithJWT($admin)
            ->postJson("/api/help-center/articles/{$article->id}/unpublish");

        $unpublishResponse->assertStatus(200);

        // Act: END_USER intenta listar artículos
        $listResponse = $this->authenticateWithJWT($endUser)
            ->getJson('/api/help-center/articles');

        // Assert
        $listResponse->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Verificar que el artículo despublicado NO aparece en la lista
        $listResponse->assertJsonMissing(['id' => $article->id]);
    }

    /**
     * Test: No se puede despublicar un artículo que está en DRAFT
     *
     * Arrange:
     * - Artículo en estado DRAFT
     *
     * Act:
     * - POST /api/help-center/articles/:id/unpublish
     *
     * Assert:
     * - Response 400 Bad Request
     * - Mensaje de error indicando que no está publicado
     */
    public function test_cannot_unpublish_draft_article(): void
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
            ->postJson("/api/help-center/articles/{$article->id}/unpublish");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false
            ]);

        // Verificar que el mensaje contiene información sobre el error
        $this->assertStringContainsStringIgnoringCase(
            'publicado',
            $response->json('message'),
            'El mensaje de error debe indicar que el artículo no está publicado'
        );

        // Verificar que el artículo sigue en DRAFT
        $article->refresh();
        $this->assertEquals('DRAFT', $article->status);
        $this->assertNull($article->published_at);
    }

    /**
     * Test: Despublicar preserva el contador de vistas (views_count)
     *
     * Arrange:
     * - Artículo PUBLISHED con views_count = 100
     *
     * Act:
     * - POST /api/help-center/articles/:id/unpublish
     *
     * Assert:
     * - views_count = 100 (NO cambió)
     * - Contador de vistas se preserva
     */
    public function test_unpublish_preserves_views_count(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();

        $category = ArticleCategory::factory()->create();

        $initialViewsCount = 100;
        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(7),
            'views_count' => $initialViewsCount
        ]);

        // Verificar estado inicial
        $this->assertEquals($initialViewsCount, $article->views_count);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/help-center/articles/{$article->id}/unpublish");

        // Assert
        $response->assertStatus(200);

        $article->refresh();
        $this->assertEquals($initialViewsCount, $article->views_count);
        $this->assertEquals('DRAFT', $article->status);
        $this->assertNull($article->published_at);

        // Verificar en base de datos
        $this->assertDatabaseHas('business.help_center_articles', [
            'id' => $article->id,
            'views_count' => $initialViewsCount,
            'status' => 'DRAFT',
            'published_at' => null
        ]);
    }
}
