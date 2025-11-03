<?php

declare(strict_types=1);

namespace Tests\Unit\ContentManagement\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas unitarias para el modelo HelpCenterArticle
 *
 * Prueba las relaciones, scopes, métodos personalizados y lógica de negocio
 * siguiendo el enfoque TDD para la característica de Gestión de Contenido.
 *
 */
#[CoversClass(HelpCenterArticle::class)]
class HelpCenterArticleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba 1: Relación de artículo pertenece a empresa
     */
    #[Test]
    public function test_article_belongs_to_company(): void
    {
        // Arrange: Crear artículo con compañía asociada
        $company = Company::factory()->create();
        $category = ArticleCategory::factory()->create();
        $author = User::factory()->create();

        $article = HelpCenterArticle::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);

        // Act: Acceder a la relación de compañía
        $relatedCompany = $article->company;

        // Assert: La relación funciona correctamente
        $this->assertInstanceOf(Company::class, $relatedCompany);
        $this->assertEquals($company->id, $relatedCompany->id);
        $this->assertEquals($company->name, $relatedCompany->name);
    }

    /**
     * Prueba 2: Relación de artículo pertenece a ArticleCategory
     */
    #[Test]
    public function test_article_belongs_to_category(): void
    {
        // Arrange: Crear artículo con categoría asociada
        $category = ArticleCategory::factory()->create([
            'code' => 'SECURITY_PRIVACY',
            'name' => 'Security & Privacy',
        ]);

        $article = HelpCenterArticle::factory()->create([
            'category_id' => $category->id,
        ]);

        // Act: Acceder a la relación de categoría
        $relatedCategory = $article->category;

        // Assert: La relación funciona correctamente
        $this->assertEquals($category->id, $relatedCategory->id);
        $this->assertEquals('SECURITY_PRIVACY', $relatedCategory->code);
        $this->assertEquals('Security & Privacy', $relatedCategory->name);
    }

    /**
     * Prueba 3: Relación de artículo pertenece a usuario (autor)
     */
    #[Test]
    public function test_article_belongs_to_author(): void
    {
        // Arrange: Crear artículo con autor asociado
        $author = User::factory()->create([
            'email' => 'author@example.com',
        ]);

        $article = HelpCenterArticle::factory()->create([
            'author_id' => $author->id,
        ]);

        // Act: Acceder a la relación de autor
        $relatedAuthor = $article->author;

        // Assert: La relación funciona correctamente
        $this->assertInstanceOf(User::class, $relatedAuthor);
        $this->assertEquals($author->id, $relatedAuthor->id);
        $this->assertEquals('author@example.com', $relatedAuthor->email);
    }

    /**
     * Prueba 4: El método incrementViews() incrementa views_count correctamente
     */
    #[Test]
    public function test_increment_views_increments_correctly(): void
    {
        // Arrange: Crear artículo con views_count = 0
        $article = HelpCenterArticle::factory()->create([
            'views_count' => 0,
        ]);

        $this->assertEquals(0, $article->views_count);

        // Act: Llamar a incrementViews()
        $article->incrementViews();

        // Assert: views_count incrementado a 1
        $article->refresh();
        $this->assertEquals(1, $article->views_count);

        // Act: Llamar a incrementViews() de nuevo
        $article->incrementViews();

        // Assert: views_count incrementado a 2
        $article->refresh();
        $this->assertEquals(2, $article->views_count);
    }

    /**
     * Prueba 5: El scope published() devuelve solo artículos publicados
     */
    #[Test]
    public function test_is_published_scope(): void
    {
        // Arrange: Crear artículos DRAFT y PUBLISHED
        $draftArticle = HelpCenterArticle::factory()->create([
            'status' => 'DRAFT',
            'published_at' => null,
        ]);

        $publishedArticle1 = HelpCenterArticle::factory()->create([
            'status' => 'PUBLISHED',
            'published_at' => now()->subDay(),
        ]);

        $publishedArticle2 = HelpCenterArticle::factory()->create([
            'status' => 'PUBLISHED',
            'published_at' => now()->subHours(2),
        ]);

        // Act: Usar el scope published()
        $publishedArticles = HelpCenterArticle::published()->get();

        // Assert: Solo se devuelven artículos PUBLISHED
        $this->assertCount(2, $publishedArticles);
        $this->assertTrue($publishedArticles->contains($publishedArticle1));
        $this->assertTrue($publishedArticles->contains($publishedArticle2));
        $this->assertFalse($publishedArticles->contains($draftArticle));
    }

    /**
     * Prueba 6: El scope byCategory() filtra artículos por código de categoría
     */
    #[Test]
    public function test_by_category_scope(): void
    {
        // Arrange: Crear categorías y artículos
        $securityCategory = ArticleCategory::factory()->create([
            'code' => 'SECURITY_PRIVACY',
        ]);

        $billingCategory = ArticleCategory::factory()->create([
            'code' => 'BILLING_PAYMENTS',
        ]);

        $securityArticle1 = HelpCenterArticle::factory()->create([
            'category_id' => $securityCategory->id,
            'title' => 'How to reset password',
        ]);

        $securityArticle2 = HelpCenterArticle::factory()->create([
            'category_id' => $securityCategory->id,
            'title' => 'Enable 2FA',
        ]);

        $billingArticle = HelpCenterArticle::factory()->create([
            'category_id' => $billingCategory->id,
            'title' => 'Payment methods',
        ]);

        // Act: Usar el scope byCategory()
        $securityArticles = HelpCenterArticle::byCategory('SECURITY_PRIVACY')->get();

        // Assert: Solo se devuelven artículos de la categoría SECURITY_PRIVACY
        $this->assertCount(2, $securityArticles);
        $this->assertTrue($securityArticles->contains($securityArticle1));
        $this->assertTrue($securityArticles->contains($securityArticle2));
        $this->assertFalse($securityArticles->contains($billingArticle));
    }

    /**
     * Prueba 7: El scope search() busca tanto en el título como en el contenido
     */
    #[Test]
    public function test_search_scope_searches_title_and_content(): void
    {
        // Arrange: Crear artículos con "password" en diferentes campos
        $articleWithPasswordInTitle = HelpCenterArticle::factory()->create([
            'title' => 'How to change your password',
            'content' => 'This guide will help you update your account settings.',
        ]);

        $articleWithPasswordInContent = HelpCenterArticle::factory()->create([
            'title' => 'Security best practices',
            'content' => 'Always use a strong password with at least 12 characters.',
        ]);

        $articleWithPasswordInBoth = HelpCenterArticle::factory()->create([
            'title' => 'Password requirements',
            'content' => 'Your password must meet the following criteria...',
        ]);

        $articleWithoutPassword = HelpCenterArticle::factory()->create([
            'title' => 'How to update profile',
            'content' => 'Navigate to settings and change your information.',
        ]);

        // Act: Usar el scope search()
        $searchResults = HelpCenterArticle::search('password')->get();

        // Assert: Se devuelven todos los artículos con "password" en el título o contenido
        $this->assertCount(3, $searchResults);
        $this->assertTrue($searchResults->contains($articleWithPasswordInTitle));
        $this->assertTrue($searchResults->contains($articleWithPasswordInContent));
        $this->assertTrue($searchResults->contains($articleWithPasswordInBoth));
        $this->assertFalse($searchResults->contains($articleWithoutPassword));
    }

    /**
     * Prueba 8: formattedPublishedDate() devuelve una cadena de fecha formateada
     */
    #[Test]
    public function test_formatted_published_date(): void
    {
        // Arrange: Crear artículo con fecha published_at específica
        $publishedAt = now()->parse('2024-10-15 10:00:00');

        $article = HelpCenterArticle::factory()->create([
            'status' => 'PUBLISHED',
            'published_at' => $publishedAt,
        ]);

        // Act: Llamar a formattedPublishedDate()
        $formattedDate = $article->formattedPublishedDate();

        // Assert: Devuelve una cadena de fecha formateada
        // Formato esperado: "15 Oct 2024" o similar
        $this->assertIsString($formattedDate);
        $this->assertStringContainsString('15', $formattedDate);
        $this->assertStringContainsString('Oct', $formattedDate);
        $this->assertStringContainsString('2024', $formattedDate);
    }

    /**
     * Prueba Adicional: Verifica que views_count por defecto es 0
     */
    #[Test]
    public function test_views_count_defaults_to_zero(): void
    {
        // Arrange & Act: Crear artículo sin especificar views_count
        $article = HelpCenterArticle::factory()->create();

        // Assert: views_count es 0 por defecto
        $this->assertEquals(0, $article->views_count);
    }

    /**
     * Prueba Adicional: Verifica que el estado por defecto es DRAFT
     */
    #[Test]
    public function test_status_defaults_to_draft(): void
    {
        // Arrange & Act: Crear artículo sin especificar estado
        $article = HelpCenterArticle::factory()->create([
            'published_at' => null,
        ]);

        // Assert: el estado es DRAFT por defecto
        $this->assertEquals('DRAFT', $article->status);
    }

    /**
     * Prueba Adicional: Verifica que published_at es null para artículos en borrador
     */
    #[Test]
    public function test_draft_article_has_null_published_at(): void
    {
        // Arrange & Act: Crear artículo en borrador
        $article = HelpCenterArticle::factory()->create([
            'status' => 'DRAFT',
            'published_at' => null,
        ]);

        // Assert: published_at es null
        $this->assertNull($article->published_at);
    }

    /**
     * Prueba Adicional: Verifica que la búsqueda no distingue entre mayúsculas y minúsculas
     */
    #[Test]
    public function test_search_scope_is_case_insensitive(): void
    {
        // Arrange: Crear artículo con mayúsculas y minúsculas mezcladas
        $article = HelpCenterArticle::factory()->create([
            'title' => 'How to Reset Your PASSWORD',
            'content' => 'This article explains password reset.',
        ]);

        // Act: Buscar con minúsculas
        $searchResults = HelpCenterArticle::search('password')->get();

        // Assert: El artículo se encuentra a pesar de la diferencia de mayúsculas y minúsculas
        $this->assertCount(1, $searchResults);
        $this->assertTrue($searchResults->contains($article));
    }
}
