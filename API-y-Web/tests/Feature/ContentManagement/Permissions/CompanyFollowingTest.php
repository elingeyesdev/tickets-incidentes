<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Permissions;

use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para visibilidad de contenido basado en "Company Following" (FASE 8 - TDD RED)
 *
 * Verifica que:
 * - USER: Solo ve contenido PUBLISHED de empresas que sigue
 * - COMPANY_ADMIN: Ve todo el contenido de su empresa (independiente de following)
 * - PLATFORM_ADMIN: Ve todo el contenido de todas las empresas
 * - Unfollow: Al dejar de seguir, pierde acceso inmediato
 * - Middleware: Valida correctamente el following status
 *
 * TDD RED PHASE: Tests creados ANTES de implementación
 * Los tests DEBEN FALLAR hasta que se implemente la lógica de following
 *
 * Endpoints probados:
 * - GET /api/announcements (lista)
 * - GET /api/announcements/{id} (detalle)
 * - GET /api/help-center/articles (lista)
 * - GET /api/help-center/articles/{id} (detalle)
 */
class CompanyFollowingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Datos de prueba comunes
     */
    protected Company $companyA;
    protected Company $companyB;
    protected ArticleCategory $category;

    /**
     * Setup común para todos los tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Crear 2 empresas activas
        $this->companyA = Company::factory()->create(['status' => 'active', 'name' => 'Company A']);
        $this->companyB = Company::factory()->create(['status' => 'active', 'name' => 'Company B']);

        // Crear categoría global para artículos
        $this->category = ArticleCategory::firstOrCreate(
            ['code' => 'ACCOUNT_PROFILE'],
            [
                'name' => 'Account & Profile',
                'description' => 'Articles about account management',
            ]
        );
    }

    // ==========================================
    // GRUPO 1: Following - Announcements (Tests 1-2)
    // ==========================================

    /**
     * Test 1: USER que sigue empresa puede ver anuncios PUBLISHED
     *
     * Arrange:
     * - USER sigue Empresa A
     * - Empresa A tiene 3 anuncios PUBLISHED
     *
     * Act:
     * - GET /api/announcements
     *
     * Assert:
     * - 200 OK
     * - Retorna los 3 anuncios PUBLISHED
     */
    public function test_user_following_company_can_see_announcements(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Usuario sigue Empresa A
        $this->companyA->followers()->attach($user->id);

        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        // Crear 3 anuncios PUBLISHED en Empresa A
        $announcement1 = Announcement::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'type' => 'MAINTENANCE',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Maintenance Announcement 1',
            'content' => 'Test content 1',
        ]);

        $announcement2 = Announcement::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'type' => 'INCIDENT',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2),
            'title' => 'Incident Announcement 2',
            'content' => 'Test content 2',
        ]);

        $announcement3 = Announcement::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'type' => 'NEWS',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(1),
            'title' => 'News Announcement 3',
            'content' => 'Test news content 3',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/announcements');

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verificar que retorna los 3 anuncios
        $this->assertCount(3, $response->json('data'));

        // Verificar que contiene los anuncios creados
        $response->assertJsonFragment(['id' => $announcement1->id]);
        $response->assertJsonFragment(['id' => $announcement2->id]);
        $response->assertJsonFragment(['id' => $announcement3->id]);
    }

    /**
     * Test 2: USER que NO sigue empresa NO puede ver anuncios
     *
     * Arrange:
     * - USER NO sigue Empresa B
     * - Empresa B tiene anuncios PUBLISHED
     *
     * Act:
     * - GET /api/announcements (sin filtro company_id)
     *
     * Assert:
     * - 200 OK
     * - NO retorna anuncios de Empresa B
     */
    public function test_user_not_following_company_cannot_see_announcements(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Usuario NO sigue Empresa B

        $adminB = User::factory()->withRole('COMPANY_ADMIN', $this->companyB->id)->create();

        // Crear 2 anuncios PUBLISHED en Empresa B
        $announcementB1 = Announcement::factory()->create([
            'company_id' => $this->companyB->id,
            'author_id' => $adminB->id,
            'type' => 'MAINTENANCE',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Company B Announcement',
            'content' => 'Test content',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/announcements');

        // Assert
        $response->assertStatus(200);

        // Verificar que NO retorna anuncios de Empresa B (usuario no la sigue)
        $response->assertJsonMissing(['id' => $announcementB1->id]);
    }

    // ==========================================
    // GRUPO 2: Following - Articles (Tests 3-4)
    // ==========================================

    /**
     * Test 3: USER que sigue empresa puede ver artículos PUBLISHED
     *
     * Arrange:
     * - USER sigue Empresa A
     * - Empresa A tiene 2 artículos PUBLISHED
     *
     * Act:
     * - GET /api/help-center/articles
     *
     * Assert:
     * - 200 OK
     * - Retorna los 2 artículos PUBLISHED
     */
    public function test_user_following_company_can_see_articles(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Usuario sigue Empresa A
        $this->companyA->followers()->attach($user->id);

        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        // Crear 2 artículos PUBLISHED en Empresa A
        $article1 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5),
            'title' => 'Published Article 1',
        ]);

        $article2 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Published Article 2',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/help-center/articles');

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verificar que retorna los 2 artículos
        $this->assertCount(2, $response->json('data'));

        // Verificar que contiene los artículos creados
        $response->assertJsonFragment(['id' => $article1->id]);
        $response->assertJsonFragment(['id' => $article2->id]);
    }

    /**
     * Test 4: USER que NO sigue empresa NO puede ver artículos
     *
     * Arrange:
     * - USER NO sigue Empresa B
     * - Empresa B tiene artículos PUBLISHED
     *
     * Act:
     * - GET /api/help-center/articles
     *
     * Assert:
     * - 200 OK
     * - NO retorna artículos de Empresa B
     */
    public function test_user_not_following_company_cannot_see_articles(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Usuario NO sigue Empresa B

        $adminB = User::factory()->withRole('COMPANY_ADMIN', $this->companyB->id)->create();

        // Crear 2 artículos PUBLISHED en Empresa B
        $articleB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'author_id' => $adminB->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Company B Article',
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/help-center/articles');

        // Assert
        $response->assertStatus(200);

        // Verificar que NO retorna artículos de Empresa B
        $response->assertJsonMissing(['id' => $articleB->id]);
    }

    // ==========================================
    // GRUPO 3: Unfollow - Pierde acceso (Test 5)
    // ==========================================

    /**
     * Test 5: USER pierde acceso al hacer unfollow
     *
     * Arrange:
     * - USER sigue Empresa A
     * - Empresa A tiene contenido PUBLISHED
     *
     * Act:
     * - USER deja de seguir Empresa A (unfollow)
     * - GET /api/announcements
     * - GET /api/help-center/articles
     *
     * Assert:
     * - 200 OK
     * - Ya NO retorna contenido de Empresa A
     * - Acceso se pierde inmediatamente tras unfollow
     */
    public function test_user_unfollows_company_loses_access(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Usuario INICIALMENTE sigue Empresa A
        $this->companyA->followers()->attach($user->id);

        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        // Crear anuncio y artículo PUBLISHED en Empresa A
        $announcement = Announcement::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'type' => 'MAINTENANCE',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2),
            'title' => 'Test Announcement',
            'content' => 'Test content',
        ]);

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Test Article',
        ]);

        // Verificar que PUEDE ver inicialmente
        $responseAnnouncements1 = $this->authenticateWithJWT($user)
            ->getJson('/api/announcements');
        $this->assertEquals(200, $responseAnnouncements1->status());
        $this->assertCount(1, $responseAnnouncements1->json('data'));
        $responseAnnouncements1->assertJsonFragment(['id' => $announcement->id]);

        $responseArticles1 = $this->authenticateWithJWT($user)
            ->getJson('/api/help-center/articles');
        $this->assertEquals(200, $responseArticles1->status());
        $this->assertCount(1, $responseArticles1->json('data'));
        $responseArticles1->assertJsonFragment(['id' => $article->id]);

        // Act - Usuario deja de seguir Empresa A (UNFOLLOW)
        $this->companyA->followers()->detach($user->id);

        // Assert - Verificar que ya NO puede ver contenido
        $responseAnnouncements2 = $this->authenticateWithJWT($user)
            ->getJson('/api/announcements');
        $this->assertEquals(200, $responseAnnouncements2->status());

        // NO debe retornar el anuncio (perdió acceso)
        $responseAnnouncements2->assertJsonMissing(['id' => $announcement->id]);

        $responseArticles2 = $this->authenticateWithJWT($user)
            ->getJson('/api/help-center/articles');
        $this->assertEquals(200, $responseArticles2->status());

        // NO debe retornar el artículo (perdió acceso)
        $responseArticles2->assertJsonMissing(['id' => $article->id]);
    }

    // ==========================================
    // GRUPO 4: ADMIN - Ignora following (Tests 6-7)
    // ==========================================

    /**
     * Test 6: COMPANY_ADMIN ve contenido sin necesidad de seguir
     *
     * Arrange:
     * - COMPANY_ADMIN de Empresa A (NO sigue su empresa)
     * - Empresa A tiene contenido DRAFT y PUBLISHED
     *
     * Act:
     * - GET /api/announcements
     * - GET /api/help-center/articles
     *
     * Assert:
     * - 200 OK
     * - Retorna TODOS los estados (DRAFT + PUBLISHED)
     * - No requiere following para acceder a su empresa
     */
    public function test_company_admin_sees_own_content_regardless_of_following(): void
    {
        // Arrange
        $admin = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();

        // ADMIN NO necesita seguir su empresa (no se hace attach)

        // Crear anuncio DRAFT en Empresa A
        $announcementDraft = Announcement::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'type' => 'MAINTENANCE',
            'status' => 'DRAFT',
            'published_at' => null,
            'title' => 'Draft Announcement',
            'content' => 'Draft content',
        ]);

        // Crear anuncio PUBLISHED en Empresa A
        $announcementPublished = Announcement::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'type' => 'INCIDENT',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2),
            'title' => 'Published Announcement',
            'content' => 'Published content',
        ]);

        // Crear artículo DRAFT en Empresa A
        $articleDraft = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'category_id' => $this->category->id,
            'status' => 'DRAFT',
            'published_at' => null,
            'title' => 'Draft Article',
        ]);

        // Crear artículo PUBLISHED en Empresa A
        $articlePublished = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $admin->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Published Article',
        ]);

        // Act & Assert - Announcements
        $responseAnnouncements = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements');

        $responseAnnouncements->assertStatus(200);
        $this->assertCount(2, $responseAnnouncements->json('data'));

        // Admin ve DRAFT y PUBLISHED sin seguir
        $responseAnnouncements->assertJsonFragment(['id' => $announcementDraft->id, 'status' => 'DRAFT']);
        $responseAnnouncements->assertJsonFragment(['id' => $announcementPublished->id, 'status' => 'PUBLISHED']);

        // Act & Assert - Articles
        $responseArticles = $this->authenticateWithJWT($admin)
            ->getJson('/api/help-center/articles');

        $responseArticles->assertStatus(200);
        $this->assertCount(2, $responseArticles->json('data'));

        // Admin ve DRAFT y PUBLISHED sin seguir
        $responseArticles->assertJsonFragment(['id' => $articleDraft->id, 'status' => 'DRAFT']);
        $responseArticles->assertJsonFragment(['id' => $articlePublished->id, 'status' => 'PUBLISHED']);
    }

    /**
     * Test 7: PLATFORM_ADMIN ve TODO sin necesidad de seguir
     *
     * Arrange:
     * - PLATFORM_ADMIN (NO sigue ninguna empresa)
     * - Empresa A y B tienen contenido PUBLISHED
     *
     * Act:
     * - GET /api/announcements
     * - GET /api/help-center/articles
     *
     * Assert:
     * - 200 OK
     * - Retorna contenido de TODAS las empresas
     * - No requiere following para acceso global
     */
    public function test_platform_admin_sees_all_content_regardless_of_following(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        // PLATFORM_ADMIN NO necesita seguir empresas

        $adminA = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();
        $adminB = User::factory()->withRole('COMPANY_ADMIN', $this->companyB->id)->create();

        // Crear anuncio en Empresa A
        $announcementA = Announcement::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $adminA->id,
            'type' => 'MAINTENANCE',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Company A Announcement',
            'content' => 'Content A',
        ]);

        // Crear anuncio en Empresa B
        $announcementB = Announcement::factory()->create([
            'company_id' => $this->companyB->id,
            'author_id' => $adminB->id,
            'type' => 'INCIDENT',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2),
            'title' => 'Company B Announcement',
            'content' => 'Content B',
        ]);

        // Crear artículo en Empresa A
        $articleA = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $adminA->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5),
            'title' => 'Company A Article',
        ]);

        // Crear artículo en Empresa B
        $articleB = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyB->id,
            'author_id' => $adminB->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(4),
            'title' => 'Company B Article',
        ]);

        // Act & Assert - Announcements
        $responseAnnouncements = $this->authenticateWithJWT($platformAdmin)
            ->getJson('/api/announcements');

        $responseAnnouncements->assertStatus(200);
        $this->assertCount(2, $responseAnnouncements->json('data'));

        // PLATFORM_ADMIN ve contenido de TODAS las empresas
        $responseAnnouncements->assertJsonFragment(['id' => $announcementA->id]);
        $responseAnnouncements->assertJsonFragment(['id' => $announcementB->id]);

        // Act & Assert - Articles
        $responseArticles = $this->authenticateWithJWT($platformAdmin)
            ->getJson('/api/help-center/articles');

        $responseArticles->assertStatus(200);
        $this->assertCount(2, $responseArticles->json('data'));

        // PLATFORM_ADMIN ve contenido de TODAS las empresas
        $responseArticles->assertJsonFragment(['id' => $articleA->id]);
        $responseArticles->assertJsonFragment(['id' => $articleB->id]);
    }

    // ==========================================
    // GRUPO 5: Middleware validación (Test 8)
    // ==========================================

    /**
     * Test 8: Middleware valida following status correctamente
     *
     * Arrange:
     * - USER sigue Empresa A
     * - USER NO sigue Empresa B
     *
     * Act:
     * - GET /api/announcements/{id} de Empresa A (sigue)
     * - GET /api/announcements/{id} de Empresa B (NO sigue)
     *
     * Assert:
     * - Empresa A: 200 OK (tiene acceso)
     * - Empresa B: 403 Forbidden (no sigue)
     */
    public function test_middleware_validates_following_status(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Usuario sigue SOLO Empresa A
        $this->companyA->followers()->attach($user->id);

        $adminA = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();
        $adminB = User::factory()->withRole('COMPANY_ADMIN', $this->companyB->id)->create();

        // Crear anuncio PUBLISHED en Empresa A (usuario LA SIGUE)
        $announcementA = Announcement::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $adminA->id,
            'type' => 'MAINTENANCE',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Company A Announcement',
            'content' => 'Content A',
        ]);

        // Crear anuncio PUBLISHED en Empresa B (usuario NO LA SIGUE)
        $announcementB = Announcement::factory()->create([
            'company_id' => $this->companyB->id,
            'author_id' => $adminB->id,
            'type' => 'INCIDENT',
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2),
            'title' => 'Company B Announcement',
            'content' => 'Content B',
        ]);

        // Act & Assert - Acceso a Empresa A (PERMITIDO)
        $responseA = $this->authenticateWithJWT($user)
            ->getJson("/api/announcements/{$announcementA->id}");

        $responseA->assertStatus(200);
        $responseA->assertJsonFragment(['id' => $announcementA->id]);

        // Act & Assert - Acceso a Empresa B (DENEGADO)
        $responseB = $this->authenticateWithJWT($user)
            ->getJson("/api/announcements/{$announcementB->id}");

        // Verificar que retorna 403 Forbidden
        $responseB->assertStatus(403);

        // Verificar que hay un mensaje de error (cualquier estructura)
        $this->assertNotNull(
            $responseB->json('message'),
            'Debe haber un mensaje de error en la respuesta'
        );
    }

    // ==========================================
    // GRUPO 6: Gap - Filtro company_id (Test 9)
    // ==========================================

    /**
     * Test 9: USER no puede filtrar lista por empresa que no sigue
     *
     * Arrange:
     * - USER sigue Empresa A
     * - USER NO sigue Empresa B
     *
     * Act:
     * - GET /api/help-center/articles?company_id=B
     *
     * Assert:
     * - 403 Forbidden
     * - No puede usar filtro de empresa no seguida
     */
    public function test_user_cannot_filter_list_by_non_followed_company(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Usuario sigue SOLO Empresa A
        $this->companyA->followers()->attach($user->id);

        $adminB = User::factory()->withRole('COMPANY_ADMIN', $this->companyB->id)->create();

        // Crear artículos en Empresa B
        HelpCenterArticle::factory()->count(3)->create([
            'company_id' => $this->companyB->id,
            'author_id' => $adminB->id,
            'category_id' => $this->category->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2),
        ]);

        // Act
        // Usuario intenta filtrar por Empresa B (NO seguida)
        $response = $this->authenticateWithJWT($user)
            ->getJson("/api/help-center/articles?company_id={$this->companyB->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        // Verificar mensaje de error indicando falta de permiso
        $this->assertStringContainsStringIgnoringCase(
            'forbidden',
            strtolower($response->json('message') ?? ''),
            'El mensaje de error debe indicar que no tiene permiso para acceder a esta empresa'
        );
    }
}
