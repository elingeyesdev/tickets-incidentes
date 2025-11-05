<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Articles;

use Tests\TestCase;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests para visualización individual de artículos del Help Center
 *
 * Tests en formato TDD RED - Fallarán hasta implementar lógica en Controller/Service.
 *
 * Cubre:
 * - Visualización de artículos publicados por USER de empresas seguidas
 * - Incremento automático de views_count al ver PUBLISHED
 * - Visualización de DRAFT por COMPANY_ADMIN de su empresa
 * - Restricciones de acceso según rol y estado del artículo
 * - Validación de estructura completa de response
 * - Casos edge: soft-deletes, unauthenticated, artículos no existentes
 * - PLATFORM_ADMIN puede ver cualquier artículo
 *
 * Reglas de Visibilidad:
 * - USER: Solo PUBLISHED de empresas que sigue
 * - COMPANY_ADMIN: DRAFT + PUBLISHED de su empresa únicamente
 * - PLATFORM_ADMIN: Cualquier estado de cualquier empresa
 * - AGENT: Solo PUBLISHED de su empresa (mismo que USER pero limitado a su empresa)
 *
 * Side Effects:
 * - Ver artículo PUBLISHED incrementa views_count automáticamente
 * - Ver artículo DRAFT NO incrementa views_count
 * - views_count se preserva al unpublish
 */
class ViewArticleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected ArticleCategory $category;

    /**
     * Setup inicial para todos los tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Crear empresa activa para tests
        $this->company = Company::factory()->create(['status' => 'active']);

        // Obtener o crear categoría por defecto
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
     * Test #1: USER puede ver artículo PUBLISHED de empresa que sigue
     *
     * Arrange:
     * - Usuario con rol USER
     * - Usuario sigue la empresa
     * - Artículo en estado PUBLISHED
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response 200 OK
     * - Datos del artículo completos
     * - success = true
     *
     * @return void
     */
    public function test_user_can_view_published_article_from_followed_company(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $this->company->followers()->attach($user->id);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now(),
            'views_count' => 5,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'company_id',
                    'category_id',
                    'author_id',
                    'title',
                    'excerpt',
                    'content',
                    'status',
                    'views_count',
                    'published_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.status', 'PUBLISHED');
    }

    /**
     * Test #2: Visualizar artículo PUBLISHED incrementa views_count
     *
     * Arrange:
     * - Usuario USER que sigue empresa
     * - Artículo PUBLISHED con views_count inicial = 10
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - views_count incrementó a 11 en base de datos
     *
     * Side Effect: Este es el comportamiento core del feature.
     *
     * @return void
     */
    public function test_viewing_published_article_increments_views_count(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $this->company->followers()->attach($user->id);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now(),
            'views_count' => 10,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(200);

        // Verificar que views_count se incrementó en la base de datos
        $article->refresh();
        $this->assertEquals(11, $article->views_count, 'views_count debe incrementar de 10 a 11');
    }

    /**
     * Test #3: views_count solo incrementa para artículos PUBLISHED
     *
     * Arrange:
     * - COMPANY_ADMIN viendo artículo DRAFT (tiene permiso)
     * - Artículo DRAFT con views_count inicial = 0
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - views_count NO incrementa (permanece en 0)
     *
     * Rationale: Solo artículos PUBLISHED incrementan views, DRAFT no cuenta visitas.
     *
     * @return void
     */
    public function test_views_count_only_increments_for_published_articles(): void
    {
        // Arrange
        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->company->id)->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'author_id' => $admin->id,
            'status' => 'DRAFT',
            'published_at' => null,
            'views_count' => 0,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(200);

        // Verificar que views_count NO se incrementó
        $article->refresh();
        $this->assertEquals(0, $article->views_count, 'views_count NO debe incrementar para artículos DRAFT');
    }

    /**
     * Test #4: Múltiples vistas por el mismo usuario incrementan contador
     *
     * Arrange:
     * - Usuario USER
     * - Artículo PUBLISHED con views_count inicial = 5
     *
     * Act:
     * - Ver artículo 3 veces consecutivas
     *
     * Assert:
     * - views_count incrementa a 8 (5 + 3)
     *
     * Rationale: No hay deduplicación por usuario. Cada vista cuenta.
     *
     * @return void
     */
    public function test_multiple_views_by_same_user_increment_count(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $this->company->followers()->attach($user->id);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now(),
            'views_count' => 5,
        ]);

        // Act: Ver el artículo 3 veces
        $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}")
            ->assertStatus(200);

        $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}")
            ->assertStatus(200);

        $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}")
            ->assertStatus(200);

        // Assert
        $article->refresh();
        $this->assertEquals(8, $article->views_count, 'views_count debe ser 8 después de 3 vistas (5 + 3)');
    }

    /**
     * Test #5: USER no puede ver artículo DRAFT (incluso si sigue la empresa)
     *
     * Arrange:
     * - Usuario USER que sigue empresa
     * - Artículo en estado DRAFT
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response 403 Forbidden
     * - Solo COMPANY_ADMIN puede ver DRAFT
     *
     * @return void
     */
    public function test_user_cannot_view_draft_article(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $this->company->followers()->attach($user->id);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => 'DRAFT',
            'published_at' => null,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsStringIgnoringCase(
            'forbidden',
            $response->json('message') ?? '',
            'El mensaje debe indicar que el acceso está prohibido'
        );
    }

    /**
     * Test #6: USER no puede ver artículo de empresa que NO sigue
     *
     * Arrange:
     * - Usuario USER (NO sigue la empresa)
     * - Artículo PUBLISHED
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response 403 Forbidden
     * - Solo puede ver artículos de empresas que sigue
     *
     * @return void
     */
    public function test_user_cannot_view_article_from_non_followed_company(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        // Deliberadamente NO attach al follower

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsStringIgnoringCase(
            'forbidden',
            $response->json('message') ?? '',
            'El mensaje debe indicar que el usuario no tiene acceso'
        );
    }

    /**
     * Test #7: COMPANY_ADMIN puede ver artículo DRAFT de su empresa
     *
     * Arrange:
     * - Usuario COMPANY_ADMIN
     * - Artículo DRAFT de su empresa
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response 200 OK
     * - Datos completos del artículo
     * - status = DRAFT
     *
     * @return void
     */
    public function test_company_admin_can_view_draft_article(): void
    {
        // Arrange
        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->company->id)->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'author_id' => $admin->id,
            'status' => 'DRAFT',
            'published_at' => null,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.status', 'DRAFT');
    }

    /**
     * Test #8: Ver artículo DRAFT NO incrementa views_count
     *
     * Arrange:
     * - COMPANY_ADMIN viendo DRAFT
     * - views_count inicial = 0
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response 200 OK
     * - views_count permanece en 0
     *
     * @return void
     */
    public function test_viewing_draft_does_not_increment_views(): void
    {
        // Arrange
        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->company->id)->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'author_id' => $admin->id,
            'status' => 'DRAFT',
            'published_at' => null,
            'views_count' => 0,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(200);

        $article->refresh();
        $this->assertEquals(0, $article->views_count, 'views_count debe permanecer en 0 para DRAFT');
    }

    /**
     * Test #9: Response contiene todo el contenido completo del artículo
     *
     * Arrange:
     * - Artículo PUBLISHED con contenido largo
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - content completo retornado (no truncado)
     * - Todos los campos requeridos presentes
     *
     * @return void
     */
    public function test_article_content_is_returned_complete(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $this->company->followers()->attach($user->id);

        $longContent = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 100);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'title' => 'Test Article Title',
            'excerpt' => 'Test excerpt summary',
            'content' => $longContent,
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.title', 'Test Article Title')
            ->assertJsonPath('data.excerpt', 'Test excerpt summary')
            ->assertJsonPath('data.content', $longContent);

        // Verificar que el contenido no fue truncado
        $returnedContent = $response->json('data.content');
        $this->assertEquals(strlen($longContent), strlen($returnedContent), 'Content debe retornarse completo sin truncar');
    }

    /**
     * Test #10: Artículo no existente retorna 404
     *
     * Arrange:
     * - UUID inválido/no existe en BD
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response 404 Not Found
     *
     * @return void
     */
    public function test_nonexistent_article_returns_404(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $this->company->followers()->attach($user->id);

        $fakeId = '99999999-9999-9999-9999-999999999999';

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$fakeId}");

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsStringIgnoringCase(
            'not found',
            $response->json('message') ?? '',
            'El mensaje debe indicar que el recurso no fue encontrado'
        );
    }

    /**
     * Test #11 (GAP): PLATFORM_ADMIN puede ver cualquier artículo de cualquier empresa
     *
     * Arrange:
     * - Usuario con rol PLATFORM_ADMIN
     * - Artículo DRAFT de empresa X (admin no pertenece a esa empresa)
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response 200 OK
     * - PLATFORM_ADMIN tiene acceso total
     *
     * Rationale: PLATFORM_ADMIN es super-admin global.
     *
     * @return void
     */
    public function test_platform_admin_can_view_any_article_regardless_of_company(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => 'DRAFT',
            'published_at' => null,
        ]);

        // Act
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.status', 'DRAFT');
    }

    /**
     * Test #12 (GAP): Usuario no autenticado no puede ver artículos
     *
     * Arrange:
     * - Sin autenticación
     * - Artículo PUBLISHED
     *
     * Act:
     * - GET /api/help-center/articles/:id (sin token JWT)
     *
     * Assert:
     * - Response 401 Unauthorized
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_view_article(): void
    {
        // Arrange
        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);

        // Act (sin authenticateWithJWT)
        $response = $this->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsStringIgnoringCase(
            'unauthenticated',
            $response->json('message') ?? '',
            'El mensaje debe indicar que el usuario no está autenticado'
        );
    }

    /**
     * Test #13 (GAP): COMPANY_ADMIN no puede ver artículos de otra empresa
     *
     * Arrange:
     * - COMPANY_ADMIN de empresa A
     * - Artículo de empresa B
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response 403 Forbidden
     * - Solo puede ver artículos de su propia empresa
     *
     * @return void
     */
    public function test_company_admin_cannot_view_article_from_different_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create(['status' => 'active']);
        $companyB = Company::factory()->create(['status' => 'active']);

        $adminA = User::factory()->withRole('COMPANY_ADMIN', $companyA->id)->create();

        $articleB = HelpCenterArticle::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);

        // Act
        $response = $this->authenticateWithJWT($adminA)
            ->getJson("/api/help-center/articles/{$articleB->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsStringIgnoringCase(
            'forbidden',
            $response->json('message') ?? '',
            'El mensaje debe indicar que no tiene permiso'
        );
    }

    /**
     * Test #14 (GAP): Artículo soft-deleted retorna 404
     *
     * Arrange:
     * - Artículo PUBLISHED
     * - Artículo soft-deleted (deleted_at != null)
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response 404 Not Found
     * - Artículos eliminados no son accesibles
     *
     * @return void
     */
    public function test_soft_deleted_article_returns_404(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $this->company->followers()->attach($user->id);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);

        // Soft delete el artículo
        $article->delete();

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsStringIgnoringCase(
            'not found',
            $response->json('message') ?? '',
            'El mensaje debe indicar que el artículo no fue encontrado'
        );
    }

    /**
     * Test #15 (GAP): Response contiene todos los campos requeridos en estructura correcta
     *
     * Arrange:
     * - Artículo PUBLISHED con relaciones cargadas
     *
     * Act:
     * - GET /api/help-center/articles/:id
     *
     * Assert:
     * - Response contiene: id, company_id, company_name, author_id, author_name
     * - category_id, category_code, category_name
     * - title, excerpt, content, status, views_count
     * - published_at, created_at, updated_at
     *
     * @return void
     */
    public function test_view_article_response_has_all_required_fields(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $this->company->followers()->attach($user->id);

        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->company->id)->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'author_id' => $admin->id,
            'status' => 'PUBLISHED',
            'published_at' => now(),
            'views_count' => 42,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles/{$article->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'company_id',
                    'category_id',
                    'author_id',
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

        // Verificar campos críticos
        $data = $response->json('data');
        $this->assertNotNull($data['id'], 'id debe estar presente');
        $this->assertNotNull($data['company_id'], 'company_id debe estar presente');
        $this->assertNotNull($data['category_id'], 'category_id debe estar presente');
        $this->assertNotNull($data['author_id'], 'author_id debe estar presente');
        $this->assertNotNull($data['title'], 'title debe estar presente');
        $this->assertNotNull($data['content'], 'content debe estar presente');
        $this->assertEquals('PUBLISHED', $data['status'], 'status debe ser PUBLISHED');
        $this->assertEquals(43, $data['views_count'], 'views_count debe ser 43 (42 + 1 por esta vista)');
    }
}
