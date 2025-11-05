<?php

namespace Tests\Feature\ContentManagement\Articles;

use Tests\TestCase;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests para listado de artículos del Help Center (FASE 6 - TDD RED Phase)
 *
 * Cubre:
 * - Visibilidad básica (END_USER ve solo PUBLISHED de empresas seguidas)
 * - Autorización/Permisos (COMPANY_ADMIN ve todos sus artículos)
 * - Filtros (category, status)
 * - Búsqueda (search en title/content)
 * - Ordenamiento (sort por views, title, created_at)
 * - Paginación (page, per_page)
 * - Autenticación (usuarios sin token = 401)
 * - Seguridad cross-company (prevención de breach)
 * - Admin global (PLATFORM_ADMIN ve todo)
 * - Comportamientos default (status por defecto según rol)
 *
 * TDD RED PHASE: Tests creados ANTES de implementación
 * Los tests DEBEN FALLAR hasta que se implemente el endpoint completo
 *
 * Endpoint: GET /api/help-center/articles
 */
class ListArticlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Datos de prueba comunes para todos los tests
     */
    protected Company $companyA;
    protected Company $companyB;
    protected Company $companyC;
    protected User $endUser;
    protected User $adminA;
    protected User $adminB;
    protected User $platformAdmin;
    protected ArticleCategory $categoryAccountProfile;
    protected ArticleCategory $categorySecurityPrivacy;
    protected ArticleCategory $categoryBillingPayments;
    protected ArticleCategory $categoryTechnicalSupport;

    /**
     * Setup común para todos los tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Crear 3 empresas activas
        $this->companyA = Company::factory()->create(['status' => 'active', 'name' => 'Company A']);
        $this->companyB = Company::factory()->create(['status' => 'active', 'name' => 'Company B']);
        $this->companyC = Company::factory()->create(['status' => 'active', 'name' => 'Company C']);

        // Crear usuarios con roles
        // USER role es global (NO requiere company_id)
        $this->endUser = User::factory()->withRole('USER')->create();

        // COMPANY_ADMIN roles requieren company_id
        $this->adminA = User::factory()->withRole('COMPANY_ADMIN', $this->companyA->id)->create();
        $this->adminB = User::factory()->withRole('COMPANY_ADMIN', $this->companyB->id)->create();

        // PLATFORM_ADMIN (acceso global, read-only)
        $this->platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        // Crear 4 categorías globales (seedeadas en producción)
        // Usar firstOrCreate para evitar duplicados en tests
        $this->categoryAccountProfile = ArticleCategory::firstOrCreate(
            ['code' => 'ACCOUNT_PROFILE'],
            [
                'name' => 'Account & Profile',
                'description' => 'Articles about account management'
            ]
        );

        $this->categorySecurityPrivacy = ArticleCategory::firstOrCreate(
            ['code' => 'SECURITY_PRIVACY'],
            [
                'name' => 'Security & Privacy',
                'description' => 'Articles about security and privacy'
            ]
        );

        $this->categoryBillingPayments = ArticleCategory::firstOrCreate(
            ['code' => 'BILLING_PAYMENTS'],
            [
                'name' => 'Billing & Payments',
                'description' => 'Articles about billing and payments'
            ]
        );

        $this->categoryTechnicalSupport = ArticleCategory::firstOrCreate(
            ['code' => 'TECHNICAL_SUPPORT'],
            [
                'name' => 'Technical Support',
                'description' => 'Articles about technical support'
            ]
        );
    }

    // ==========================================
    // GRUPO 1: Visibilidad Básica (Tests 1-2)
    // ==========================================

    /**
     * Test 1: END_USER puede listar artículos PUBLISHED de empresa seguida
     *
     * Arrange:
     * - END_USER sigue Empresa A
     * - Empresa A tiene 2 PUBLISHED + 1 DRAFT
     *
     * Act:
     * - GET /api/help-center/articles
     *
     * Assert:
     * - 200 OK
     * - Retorna SOLO 2 artículos PUBLISHED
     * - NO retorna el DRAFT
     */
    public function test_end_user_can_list_published_articles_from_followed_company(): void
    {
        // Arrange
        // END_USER sigue Empresa A
        $this->companyA->followers()->attach($this->endUser->id);

        // Crear 2 artículos PUBLISHED
        $publishedArticle1 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5),
            'title' => 'Published Article 1'
        ]);

        $publishedArticle2 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Published Article 2'
        ]);

        // Crear 1 artículo DRAFT (NO debe aparecer)
        $draftArticle = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryBillingPayments->id,
            'status' => 'DRAFT',
            'published_at' => null,
            'title' => 'Draft Article'
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'status', 'published_at']
                ]
            ])
            ->assertJson(['success' => true]);

        // Verificar que retorna SOLO 2 artículos PUBLISHED
        $this->assertCount(2, $response->json('data'));

        // Verificar que contiene los artículos PUBLISHED
        $response->assertJsonFragment(['id' => $publishedArticle1->id, 'status' => 'PUBLISHED']);
        $response->assertJsonFragment(['id' => $publishedArticle2->id, 'status' => 'PUBLISHED']);

        // Verificar que NO contiene el DRAFT
        $response->assertJsonMissing(['id' => $draftArticle->id]);
    }

    /**
     * Test 2: END_USER no puede ver artículos DRAFT
     *
     * Arrange:
     * - END_USER sigue Empresa A
     * - Empresa A tiene 3 PUBLISHED + 2 DRAFT
     *
     * Act:
     * - GET /api/help-center/articles
     *
     * Assert:
     * - 200 OK
     * - Retorna SOLO 3 PUBLISHED
     * - Los 2 DRAFT están excluidos
     */
    public function test_end_user_cannot_see_draft_articles(): void
    {
        // Arrange
        $this->companyA->followers()->attach($this->endUser->id);

        // Crear 3 artículos PUBLISHED
        $published1 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(10)
        ]);

        $published2 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(7)
        ]);

        $published3 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryBillingPayments->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3)
        ]);

        // Crear 2 artículos DRAFT (NO deben aparecer)
        $draft1 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryTechnicalSupport->id,
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        $draft2 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles');

        // Assert
        $response->assertStatus(200);

        // Verificar que retorna SOLO los 3 PUBLISHED
        $this->assertCount(3, $response->json('data'));

        // Verificar que contiene los PUBLISHED
        $response->assertJsonFragment(['id' => $published1->id]);
        $response->assertJsonFragment(['id' => $published2->id]);
        $response->assertJsonFragment(['id' => $published3->id]);

        // Verificar que NO contiene los DRAFT
        $response->assertJsonMissing(['id' => $draft1->id]);
        $response->assertJsonMissing(['id' => $draft2->id]);
    }

    // ==========================================
    // GRUPO 2: Autorización/Permisos (Tests 3-4)
    // ==========================================

    /**
     * Test 3: END_USER no puede listar artículos de empresa que NO sigue
     *
     * Arrange:
     * - END_USER SOLO sigue Empresa A
     * - Empresa B tiene artículos PUBLISHED
     *
     * Act:
     * - GET /api/help-center/articles?company_id=B
     *
     * Assert:
     * - 403 Forbidden
     * - No puede acceder a empresa que no sigue
     */
    public function test_end_user_cannot_list_articles_from_non_followed_company(): void
    {
        // Arrange
        // END_USER SOLO sigue Empresa A
        $this->companyA->followers()->attach($this->endUser->id);

        // Empresa B tiene artículos PUBLISHED (pero END_USER NO sigue B)
        HelpCenterArticle::factory()->count(3)->create([
            'company_id' => $this->companyB->id,
            'author_id' => $this->adminB->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2)
        ]);

        // Act
        // END_USER intenta listar artículos de Empresa B (NO seguida)
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson("/api/help-center/articles?company_id={$this->companyB->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        // Verificar mensaje de error indicando falta de permiso
        $this->assertStringContainsStringIgnoringCase(
            'forbidden',
            strtolower($response->json('message') ?? ''),
            'El mensaje de error debe indicar que no tiene permiso'
        );
    }

    /**
     * Test 4: COMPANY_ADMIN puede ver TODOS los artículos de su empresa
     *
     * Arrange:
     * - COMPANY_ADMIN de Empresa A
     * - Empresa A tiene 5 PUBLISHED + 3 DRAFT
     *
     * Act:
     * - GET /api/help-center/articles
     *
     * Assert:
     * - 200 OK
     * - Retorna TODOS (8 artículos: 5 PUBLISHED + 3 DRAFT)
     */
    public function test_company_admin_can_see_all_articles_of_own_company(): void
    {
        // Arrange
        // Crear 5 artículos PUBLISHED
        $publishedArticles = HelpCenterArticle::factory()->count(5)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5)
        ]);

        // Crear 3 artículos DRAFT
        $draftArticles = HelpCenterArticle::factory()->count(3)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->adminA)
            ->getJson('/api/help-center/articles');

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verificar que retorna TODOS (5 PUBLISHED + 3 DRAFT = 8)
        $this->assertCount(8, $response->json('data'));

        // Verificar que contiene artículos PUBLISHED y DRAFT
        $publishedCount = collect($response->json('data'))
            ->where('status', 'PUBLISHED')
            ->count();
        $draftCount = collect($response->json('data'))
            ->where('status', 'DRAFT')
            ->count();

        $this->assertEquals(5, $publishedCount, 'Debe haber 5 artículos PUBLISHED');
        $this->assertEquals(3, $draftCount, 'Debe haber 3 artículos DRAFT');
    }

    // ==========================================
    // GRUPO 3: Filtros (Tests 5-6)
    // ==========================================

    /**
     * Test 5: Filtro por categoría funciona
     *
     * Arrange:
     * - Empresa A con 12 artículos distribuidos en 4 categorías
     *   - ACCOUNT_PROFILE: 3 artículos
     *   - SECURITY_PRIVACY: 4 artículos
     *   - BILLING_PAYMENTS: 3 artículos
     *   - TECHNICAL_SUPPORT: 2 artículos
     *
     * Act:
     * - GET /api/help-center/articles?category=SECURITY_PRIVACY
     *
     * Assert:
     * - 200 OK
     * - Retorna SOLO 4 artículos de SECURITY_PRIVACY
     * - Otros excluidos
     */
    public function test_filter_by_category_works(): void
    {
        // Arrange
        $this->companyA->followers()->attach($this->endUser->id);

        // Crear 3 artículos en ACCOUNT_PROFILE
        HelpCenterArticle::factory()->count(3)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5)
        ]);

        // Crear 4 artículos en SECURITY_PRIVACY
        $securityArticles = HelpCenterArticle::factory()->count(4)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3)
        ]);

        // Crear 3 artículos en BILLING_PAYMENTS
        HelpCenterArticle::factory()->count(3)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryBillingPayments->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2)
        ]);

        // Crear 2 artículos en TECHNICAL_SUPPORT
        HelpCenterArticle::factory()->count(2)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryTechnicalSupport->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(1)
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles?category=SECURITY_PRIVACY');

        // Assert
        $response->assertStatus(200);

        // Verificar que retorna SOLO 4 artículos de SECURITY_PRIVACY
        $this->assertCount(4, $response->json('data'));

        // Verificar que todos son de la categoría SECURITY_PRIVACY
        $categories = collect($response->json('data'))
            ->pluck('category_id')
            ->unique();

        $this->assertCount(1, $categories, 'Todos los artículos deben ser de la misma categoría');
        $this->assertTrue(
            $categories->contains($this->categorySecurityPrivacy->id),
            'Todos los artículos deben ser de SECURITY_PRIVACY'
        );
    }

    /**
     * Test 6: Filtro por status funciona
     *
     * Arrange:
     * - COMPANY_ADMIN con 7 artículos (5 PUBLISHED + 2 DRAFT)
     *
     * Act:
     * - GET /api/help-center/articles?status=draft
     *
     * Assert:
     * - 200 OK
     * - Retorna SOLO 2 DRAFT
     * - Los 5 PUBLISHED están excluidos
     */
    public function test_filter_by_status_works(): void
    {
        // Arrange
        // Crear 5 artículos PUBLISHED
        HelpCenterArticle::factory()->count(5)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5)
        ]);

        // Crear 2 artículos DRAFT
        $draftArticles = HelpCenterArticle::factory()->count(2)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->adminA)
            ->getJson('/api/help-center/articles?status=draft');

        // Assert
        $response->assertStatus(200);

        // Verificar que retorna SOLO 2 DRAFT
        $this->assertCount(2, $response->json('data'));

        // Verificar que todos son DRAFT
        $statuses = collect($response->json('data'))
            ->pluck('status')
            ->unique();

        $this->assertCount(1, $statuses, 'Todos los artículos deben tener el mismo status');
        $this->assertEquals('DRAFT', $statuses->first(), 'Todos los artículos deben ser DRAFT');
    }

    // ==========================================
    // GRUPO 4: Búsqueda (Tests 7-8)
    // ==========================================

    /**
     * Test 7: Búsqueda por título funciona
     *
     * Arrange:
     * - Empresa A con 15 artículos
     * - 3 artículos contienen "contraseña" en el título
     *
     * Act:
     * - GET /api/help-center/articles?search=contraseña
     *
     * Assert:
     * - 200 OK
     * - Retorna SOLO 3 artículos
     * - Búsqueda case-insensitive (ILIKE)
     */
    public function test_search_by_title_works(): void
    {
        // Arrange
        $this->companyA->followers()->attach($this->endUser->id);

        // Crear 3 artículos con "contraseña" en el título
        HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5),
            'title' => 'Cómo cambiar tu contraseña',
            'content' => 'Guía para cambiar contraseña'
        ]);

        HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(4),
            'title' => 'Recuperar CONTRASEÑA olvidada',
            'content' => 'Proceso de recuperación'
        ]);

        HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'Políticas de ConTraSeÑa segura',
            'content' => 'Requisitos de seguridad'
        ]);

        // Crear 12 artículos sin "contraseña" en el título
        HelpCenterArticle::factory()->count(12)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2),
            'title' => 'Other Article Title',
            'content' => 'Other content'
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles?search=contraseña');

        // Assert
        $response->assertStatus(200);

        // Verificar que retorna SOLO 3 artículos
        $this->assertCount(3, $response->json('data'));

        // Verificar que todos contienen "contraseña" en el título (case-insensitive)
        $titles = collect($response->json('data'))->pluck('title');
        foreach ($titles as $title) {
            $this->assertTrue(
                str_contains(strtolower($title), 'contraseña'),
                "El título '{$title}' debe contener 'contraseña'"
            );
        }
    }

    /**
     * Test 8: Búsqueda por contenido funciona
     *
     * Arrange:
     * - Empresa A con 15 artículos
     * - 2 artículos mencionan "2FA" EN EL CONTENT (no en título)
     *
     * Act:
     * - GET /api/help-center/articles?search=2FA
     *
     * Assert:
     * - 200 OK
     * - Retorna los 2 artículos
     * - Búsqueda funcionó en content
     */
    public function test_search_by_content_works(): void
    {
        // Arrange
        $this->companyA->followers()->attach($this->endUser->id);

        // Crear 2 artículos con "2FA" SOLO en el contenido (NO en título)
        HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5),
            'title' => 'Configurar autenticación adicional',
            'content' => 'Para activar 2FA en tu cuenta, ve a configuración y habilita la verificación en dos pasos.'
        ]);

        HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(4),
            'title' => 'Mejora la seguridad de tu cuenta',
            'content' => 'Recomendamos activar 2FA para proteger tu cuenta contra accesos no autorizados.'
        ]);

        // Crear 13 artículos sin "2FA" en título ni contenido
        HelpCenterArticle::factory()->count(13)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2),
            'title' => 'Different Topic',
            'content' => 'Content without the search term'
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles?search=2FA');

        // Assert
        $response->assertStatus(200);

        // Verificar que retorna SOLO 2 artículos
        $this->assertCount(2, $response->json('data'));

        // Verificar que todos contienen "2FA" en el contenido
        $contents = collect($response->json('data'))->pluck('content');
        foreach ($contents as $content) {
            $this->assertTrue(
                str_contains($content, '2FA'),
                "El contenido debe contener '2FA'"
            );
        }
    }

    // ==========================================
    // GRUPO 5: Ordenamiento (Tests 9-10)
    // ==========================================

    /**
     * Test 9: Ordenar por vistas descendente
     *
     * Arrange:
     * - Empresa A con 5 artículos con diferentes view counts:
     *   1500, 800, 300, 150, 50
     *
     * Act:
     * - GET /api/help-center/articles?sort=-views
     *
     * Assert:
     * - 200 OK
     * - Retorna en orden descendente: 1500 → 800 → 300 → 150 → 50
     */
    public function test_sort_by_views_desc(): void
    {
        // Arrange
        $this->companyA->followers()->attach($this->endUser->id);

        // Crear artículos con diferentes view counts
        $article1500 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(10),
            'views_count' => 1500,
            'title' => 'Most viewed article'
        ]);

        $article800 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(9),
            'views_count' => 800,
            'title' => 'Second most viewed'
        ]);

        $article300 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(8),
            'views_count' => 300,
            'title' => 'Third most viewed'
        ]);

        $article150 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(7),
            'views_count' => 150,
            'title' => 'Fourth most viewed'
        ]);

        $article50 = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(6),
            'views_count' => 50,
            'title' => 'Least viewed'
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles?sort=-views');

        // Assert
        $response->assertStatus(200);

        $articles = $response->json('data');
        $this->assertCount(5, $articles);

        // Verificar orden descendente: 1500 → 800 → 300 → 150 → 50
        $this->assertEquals(1500, $articles[0]['views_count']);
        $this->assertEquals(800, $articles[1]['views_count']);
        $this->assertEquals(300, $articles[2]['views_count']);
        $this->assertEquals(150, $articles[3]['views_count']);
        $this->assertEquals(50, $articles[4]['views_count']);

        // Verificar IDs
        $this->assertEquals($article1500->id, $articles[0]['id']);
        $this->assertEquals($article800->id, $articles[1]['id']);
        $this->assertEquals($article300->id, $articles[2]['id']);
        $this->assertEquals($article150->id, $articles[3]['id']);
        $this->assertEquals($article50->id, $articles[4]['id']);
    }

    /**
     * Test 10: Ordenar por título ascendente
     *
     * Arrange:
     * - Empresa A con 5 artículos: Zebra, Apple, MongoDB, JavaScript, Beta
     *
     * Act:
     * - GET /api/help-center/articles?sort=title
     *
     * Assert:
     * - 200 OK
     * - Retorna alfabético ascendente:
     *   Apple → Beta → JavaScript → MongoDB → Zebra
     */
    public function test_sort_by_title_asc(): void
    {
        // Arrange
        $this->companyA->followers()->attach($this->endUser->id);

        // Crear artículos con títulos en diferentes órdenes
        $articleZebra = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5),
            'title' => 'Zebra Article'
        ]);

        $articleApple = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(4),
            'title' => 'Apple Article'
        ]);

        $articleMongo = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3),
            'title' => 'MongoDB Article'
        ]);

        $articleJS = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2),
            'title' => 'JavaScript Article'
        ]);

        $articleBeta = HelpCenterArticle::factory()->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(1),
            'title' => 'Beta Article'
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles?sort=title');

        // Assert
        $response->assertStatus(200);

        $articles = $response->json('data');
        $this->assertCount(5, $articles);

        // Verificar orden alfabético ascendente
        // Apple → Beta → JavaScript → MongoDB → Zebra
        $this->assertEquals('Apple Article', $articles[0]['title']);
        $this->assertEquals('Beta Article', $articles[1]['title']);
        $this->assertEquals('JavaScript Article', $articles[2]['title']);
        $this->assertEquals('MongoDB Article', $articles[3]['title']);
        $this->assertEquals('Zebra Article', $articles[4]['title']);

        // Verificar IDs
        $this->assertEquals($articleApple->id, $articles[0]['id']);
        $this->assertEquals($articleBeta->id, $articles[1]['id']);
        $this->assertEquals($articleJS->id, $articles[2]['id']);
        $this->assertEquals($articleMongo->id, $articles[3]['id']);
        $this->assertEquals($articleZebra->id, $articles[4]['id']);
    }

    // ==========================================
    // GRUPO 6: Paginación (Test 11)
    // ==========================================

    /**
     * Test 11: Paginación funciona correctamente
     *
     * Arrange:
     * - Empresa A con 50 artículos
     *
     * Act:
     * - GET /api/help-center/articles?page=2&per_page=20
     *
     * Assert:
     * - 200 OK
     * - Retorna items 21-40 (NO 1-20)
     * - Meta: {current_page: 2, per_page: 20, total: 50, last_page: 3}
     * - Items 41-50 en página 3
     */
    public function test_pagination_works(): void
    {
        // Arrange
        $this->companyA->followers()->attach($this->endUser->id);

        // Crear 50 artículos PUBLISHED
        $articles = collect();
        for ($i = 1; $i <= 50; $i++) {
            $article = HelpCenterArticle::factory()->create([
                'company_id' => $this->companyA->id,
                'author_id' => $this->adminA->id,
                'category_id' => $this->categoryAccountProfile->id,
                'status' => 'PUBLISHED',
                'published_at' => now()->subDays(50 - $i), // Orden cronológico inverso
                'title' => "Article {$i}"
            ]);
            $articles->push($article);
        }

        // Act - Solicitar página 2 con 20 items por página
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles?page=2&per_page=20');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page'
                ]
            ]);

        // Verificar que retorna 20 items (página 2)
        $this->assertCount(20, $response->json('data'));

        // Verificar meta de paginación
        $this->assertEquals(2, $response->json('meta.current_page'));
        $this->assertEquals(20, $response->json('meta.per_page'));
        $this->assertEquals(50, $response->json('meta.total'));
        $this->assertEquals(3, $response->json('meta.last_page'));

        // Verificar que NO contiene items de la página 1 (items 1-20)
        $firstArticleId = $articles->first()->id;
        $response->assertJsonMissing(['id' => $firstArticleId]);

        // Opcional: Verificar página 3 tiene items 41-50 (10 items)
        $responsePage3 = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles?page=3&per_page=20');

        $responsePage3->assertStatus(200);
        $this->assertCount(10, $responsePage3->json('data'), 'Página 3 debe tener 10 items (41-50)');
        $this->assertEquals(3, $responsePage3->json('meta.current_page'));
    }

    // ==========================================
    // GRUPO 7: Autenticación (Test 12)
    // ==========================================

    /**
     * Test 12: Usuario sin autenticar no puede listar artículos
     *
     * Arrange:
     * - Usuario SIN token JWT
     *
     * Act:
     * - GET /api/help-center/articles (sin Authorization header)
     *
     * Assert:
     * - 401 Unauthorized
     * - NO retorna artículos
     */
    public function test_unauthenticated_user_cannot_list_articles(): void
    {
        // Arrange
        // Crear algunos artículos en Empresa A
        HelpCenterArticle::factory()->count(5)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3)
        ]);

        // Act
        // Request SIN Authorization header (usuario no autenticado)
        $response = $this->getJson('/api/help-center/articles');

        // Assert
        $response->assertStatus(401);

        // Verificar que NO retorna datos
        $this->assertNull($response->json('data'));
    }

    // ==========================================
    // GRUPO 8: Seguridad Cross-Company (Test 13)
    // ==========================================

    /**
     * Test 13: COMPANY_ADMIN de empresa diferente no puede listar artículos
     *
     * Arrange:
     * - COMPANY_ADMIN de Empresa A
     * - Empresa B con artículos
     *
     * Act:
     * - GET /api/help-center/articles?company_id=B
     *
     * Assert:
     * - 403 Forbidden
     * - Previene cross-company breach
     *
     * Nota: Aunque el company_id es "inferido del JWT", un admin podría
     * intentar cambiar el parámetro manualmente
     */
    public function test_company_admin_from_different_company_cannot_list_articles(): void
    {
        // Arrange
        // Crear artículos en Empresa B
        HelpCenterArticle::factory()->count(5)->create([
            'company_id' => $this->companyB->id,
            'author_id' => $this->adminB->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3)
        ]);

        // Act
        // COMPANY_ADMIN de Empresa A intenta acceder artículos de Empresa B
        $response = $this->authenticateWithJWT($this->adminA)
            ->getJson("/api/help-center/articles?company_id={$this->companyB->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        // Verificar mensaje de error
        $this->assertStringContainsStringIgnoringCase(
            'forbidden',
            strtolower($response->json('message') ?? ''),
            'El mensaje de error debe indicar falta de permiso'
        );
    }

    // ==========================================
    // GRUPO 9: Admin Global (Test 14)
    // ==========================================

    /**
     * Test 14: PLATFORM_ADMIN puede listar TODOS los artículos de TODAS las empresas
     *
     * Arrange:
     * - PLATFORM_ADMIN (acceso global)
     * - 3 empresas con artículos:
     *   - Empresa A: 5 artículos
     *   - Empresa B: 8 artículos
     *   - Empresa C: 3 artículos
     *   Total: 16 artículos
     *
     * Act:
     * - GET /api/help-center/articles (sin filtro company_id)
     *
     * Assert:
     * - 200 OK
     * - Retorna TODOS 16 artículos
     * - Diferentes company_ids
     */
    public function test_platform_admin_can_list_all_articles_from_all_companies(): void
    {
        // Arrange
        // Crear 5 artículos en Empresa A
        $articlesA = HelpCenterArticle::factory()->count(5)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5)
        ]);

        // Crear 8 artículos en Empresa B
        $articlesB = HelpCenterArticle::factory()->count(8)->create([
            'company_id' => $this->companyB->id,
            'author_id' => $this->adminB->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(3)
        ]);

        // Crear 3 artículos en Empresa C
        $adminC = User::factory()->withRole('COMPANY_ADMIN', $this->companyC->id)->create();
        $articlesC = HelpCenterArticle::factory()->count(3)->create([
            'company_id' => $this->companyC->id,
            'author_id' => $adminC->id,
            'category_id' => $this->categoryBillingPayments->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(2)
        ]);

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)
            ->getJson('/api/help-center/articles');

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verificar que retorna TODOS 16 artículos (5 + 8 + 3)
        $this->assertCount(16, $response->json('data'));

        // Verificar que hay artículos de las 3 empresas
        $companyIds = collect($response->json('data'))
            ->pluck('company_id')
            ->unique();

        $this->assertCount(3, $companyIds, 'Debe haber artículos de 3 empresas diferentes');
        $this->assertTrue($companyIds->contains($this->companyA->id), 'Debe haber artículos de Empresa A');
        $this->assertTrue($companyIds->contains($this->companyB->id), 'Debe haber artículos de Empresa B');
        $this->assertTrue($companyIds->contains($this->companyC->id), 'Debe haber artículos de Empresa C');
    }

    // ==========================================
    // GRUPO 10: Comportamientos Default (Tests 15-16)
    // ==========================================

    /**
     * Test 15: Default status filter para END_USER es PUBLISHED
     *
     * Arrange:
     * - END_USER que sigue Empresa A
     * - Empresa A tiene 10 artículos: 7 PUBLISHED + 3 DRAFT
     *
     * Act:
     * - GET /api/help-center/articles (SIN ?status=)
     *
     * Assert:
     * - 200 OK
     * - Retorna SOLO 7 PUBLISHED por defecto
     * - DRAFT no aparecen
     *
     * Define el CONTRATO: "Sin status explícito, END_USER ve published"
     */
    public function test_default_status_filter_for_end_user_is_published(): void
    {
        // Arrange
        $this->companyA->followers()->attach($this->endUser->id);

        // Crear 7 artículos PUBLISHED
        $publishedArticles = HelpCenterArticle::factory()->count(7)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5)
        ]);

        // Crear 3 artículos DRAFT
        $draftArticles = HelpCenterArticle::factory()->count(3)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        // Act
        // Request SIN parámetro ?status=
        $response = $this->authenticateWithJWT($this->endUser)
            ->getJson('/api/help-center/articles');

        // Assert
        $response->assertStatus(200);

        // Verificar que retorna SOLO 7 PUBLISHED (default para END_USER)
        $this->assertCount(7, $response->json('data'));

        // Verificar que todos son PUBLISHED
        $statuses = collect($response->json('data'))
            ->pluck('status')
            ->unique();

        $this->assertCount(1, $statuses, 'Todos los artículos deben tener el mismo status');
        $this->assertEquals('PUBLISHED', $statuses->first(), 'Default status para END_USER debe ser PUBLISHED');

        // Verificar que NO contiene DRAFT
        foreach ($draftArticles as $draftArticle) {
            $response->assertJsonMissing(['id' => $draftArticle->id]);
        }
    }

    /**
     * Test 16: Default status filter para COMPANY_ADMIN muestra todos
     *
     * Arrange:
     * - COMPANY_ADMIN de Empresa A
     * - Empresa A tiene 10 artículos: 7 PUBLISHED + 3 DRAFT
     *
     * Act:
     * - GET /api/help-center/articles (SIN ?status=)
     *
     * Assert:
     * - 200 OK
     * - Retorna TODOS 10 (PUBLISHED + DRAFT)
     *
     * Define el CONTRATO: "Sin status explícito, ADMIN ve todo"
     * Evita que admin necesite hacer 2 requests
     */
    public function test_default_status_filter_for_company_admin_shows_all(): void
    {
        // Arrange
        // Crear 7 artículos PUBLISHED
        $publishedArticles = HelpCenterArticle::factory()->count(7)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categoryAccountProfile->id,
            'status' => 'PUBLISHED',
            'published_at' => now()->subDays(5)
        ]);

        // Crear 3 artículos DRAFT
        $draftArticles = HelpCenterArticle::factory()->count(3)->create([
            'company_id' => $this->companyA->id,
            'author_id' => $this->adminA->id,
            'category_id' => $this->categorySecurityPrivacy->id,
            'status' => 'DRAFT',
            'published_at' => null
        ]);

        // Act
        // Request SIN parámetro ?status=
        $response = $this->authenticateWithJWT($this->adminA)
            ->getJson('/api/help-center/articles');

        // Assert
        $response->assertStatus(200);

        // Verificar que retorna TODOS 10 artículos (7 PUBLISHED + 3 DRAFT)
        $this->assertCount(10, $response->json('data'));

        // Verificar que contiene PUBLISHED y DRAFT
        $publishedCount = collect($response->json('data'))
            ->where('status', 'PUBLISHED')
            ->count();
        $draftCount = collect($response->json('data'))
            ->where('status', 'DRAFT')
            ->count();

        $this->assertEquals(7, $publishedCount, 'Debe haber 7 artículos PUBLISHED');
        $this->assertEquals(3, $draftCount, 'Debe haber 3 artículos DRAFT');
    }
}
