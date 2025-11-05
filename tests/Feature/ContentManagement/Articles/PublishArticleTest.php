<?php

namespace Tests\Feature\ContentManagement\Articles;

use Tests\TestCase;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Events\ArticlePublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

/**
 * Tests para la publicación de artículos del Help Center
 *
 * Cubre:
 * - Publicación de artículos en estado DRAFT
 * - Validación de estado published_at
 * - Prevención de doble publicación
 * - Disparo de evento ArticlePublished
 * - Visibilidad para END_USER después de publicar
 * - Autorización: solo COMPANY_ADMIN puede publicar
 */
class PublishArticleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: COMPANY_ADMIN puede publicar un artículo en estado DRAFT
     *
     * Arrange:
     * - Usuario COMPANY_ADMIN
     * - Artículo en estado DRAFT
     *
     * Act:
     * - POST /api/help-center/articles/:id/publish
     *
     * Assert:
     * - Response 200 OK
     * - status = PUBLISHED
     * - published_at != null
     * - Mensaje de éxito
     */
    public function test_company_admin_can_publish_draft_article(): void
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
            ->postJson("/api/help-center/articles/{$article->id}/publish");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'status', 'published_at']
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Artículo publicado exitosamente',
                'data' => [
                    'id' => $article->id,
                    'status' => 'PUBLISHED'
                ]
            ]);

        $this->assertDatabaseHas('business.help_center_articles', [
            'id' => $article->id,
            'status' => 'PUBLISHED'
        ]);

        $article->refresh();
        $this->assertEquals('PUBLISHED', $article->status);
        $this->assertNotNull($article->published_at);
    }

    /**
     * Test: Publicar establece published_at con timestamp válido
     *
     * Arrange:
     * - Artículo en DRAFT sin published_at
     *
     * Act:
     * - POST /api/help-center/articles/:id/publish
     *
     * Assert:
     * - published_at tiene timestamp válido (Carbon/DateTime)
     * - published_at es aproximadamente now()
     */
    public function test_publish_sets_published_at(): void
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
        $beforePublish = now();
        $response = $this->authenticateWithJWT($admin)
            ->postJson("/api/help-center/articles/{$article->id}/publish");
        $afterPublish = now();

        // Assert
        $response->assertStatus(200);

        $article->refresh();
        $this->assertNotNull($article->published_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $article->published_at);

        // Verificar que published_at es aproximadamente now() (margen de 5 segundos)
        $this->assertLessThanOrEqual(
            5,
            abs($article->published_at->diffInSeconds(now())),
            'published_at debe ser aproximadamente now()'
        );
    }

    /**
     * Test: No se puede publicar un artículo que ya está publicado
     *
     * Arrange:
     * - Artículo en estado PUBLISHED
     *
     * Act:
     * - POST /api/help-center/articles/:id/publish
     *
     * Assert:
     * - Response 400 Bad Request
     * - Mensaje de error indicando que ya está publicado
     */
    public function test_cannot_publish_already_published_article(): void
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
            ->postJson("/api/help-center/articles/{$article->id}/publish");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false
            ]);

        // Verificar que el mensaje contiene información sobre el error
        $this->assertStringContainsStringIgnoringCase(
            'publicado',
            $response->json('message'),
            'El mensaje de error debe indicar que el artículo ya está publicado'
        );
    }

    /**
     * Test: Publicar dispara el evento ArticlePublished
     *
     * Arrange:
     * - Event fake
     * - Artículo en DRAFT
     *
     * Act:
     * - POST /api/help-center/articles/:id/publish
     *
     * Assert:
     * - Evento ArticlePublished fue disparado
     * - Evento contiene datos correctos (article_id, published_at, etc.)
     */
    public function test_publish_triggers_article_published_event(): void
    {
        // Arrange
        Event::fake([ArticlePublished::class]);

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
            ->postJson("/api/help-center/articles/{$article->id}/publish");

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(ArticlePublished::class, function ($event) use ($article) {
            return $event->article->id === $article->id
                && $event->article->status === 'PUBLISHED'
                && $event->article->published_at !== null;
        });
    }

    /**
     * Test: Artículo publicado se vuelve visible para END_USER
     *
     * Arrange:
     * - COMPANY_ADMIN publica artículo
     * - END_USER de la misma empresa
     *
     * Act:
     * - END_USER lista artículos (GET /api/help-center/articles)
     *
     * Assert:
     * - Artículo publicado aparece en la lista
     */
    public function test_published_article_becomes_visible_to_end_users(): void
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
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        // Act: Admin publica el artículo
        $publishResponse = $this->authenticateWithJWT($admin)
            ->postJson("/api/help-center/articles/{$article->id}/publish");

        $publishResponse->assertStatus(200);

        // Act: END_USER lista artículos
        $listResponse = $this->authenticateWithJWT($endUser)
            ->getJson('/api/help-center/articles');

        // Assert
        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $article->id,
                'status' => 'PUBLISHED'
            ]);
    }

    /**
     * Test: END_USER no puede publicar artículos
     *
     * Arrange:
     * - Usuario END_USER
     * - Artículo en DRAFT
     *
     * Act:
     * - POST /api/help-center/articles/:id/publish
     *
     * Assert:
     * - Response 403 Forbidden
     * - Solo COMPANY_ADMIN puede publicar
     */
    public function test_end_user_cannot_publish_article(): void
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        // USER role NO requiere company_id (es global)
        $endUser = User::factory()->withRole('USER')->create();

        $category = ArticleCategory::factory()->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson("/api/help-center/articles/{$article->id}/publish");

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'success' => false
            ]);

        // Verificar que el artículo sigue en DRAFT
        $article->refresh();
        $this->assertEquals('DRAFT', $article->status);
        $this->assertNull($article->published_at);
    }
}
