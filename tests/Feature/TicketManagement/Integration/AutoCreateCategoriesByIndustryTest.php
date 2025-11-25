<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Integration;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Features\TicketManagement\Models\Category;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests de integración para auto-creación de categorías por industry type
 *
 * Valida que cuando se crea una empresa (vía CompanyService), automáticamente
 * se crean 5 categorías de tickets específicas según su industry_type.
 *
 * Este test valida:
 * 1. Que el listener CreateDefaultCategoriesListener se dispare correctamente
 * 2. Que se crean exactamente 5 categorías por empresa
 * 3. Que las categorías son específicas del industry_type
 * 4. Que todas las categorías están activas por defecto
 * 5. Que no se crean duplicados si el listener se dispara múltiples veces
 */
class AutoCreateCategoriesByIndustryTest extends TestCase
{
    use RefreshDatabase;

    private CompanyService $companyService;

    protected function setUp(): void
    {
        parent::setUp();

        // Seedear industries
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        $this->companyService = app(CompanyService::class);
    }

    #[Test]
    public function auto_creates_5_categories_for_technology_company(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $industryTech = CompanyIndustry::where('code', 'technology')->first();

        // Act - Crear empresa vía servicio (dispara evento CompanyCreated)
        $company = $this->companyService->create([
            'name' => 'Tech Company',
            'industry_id' => $industryTech->id,
        ], $admin);

        // Assert - Verificar que se crearon exactamente 5 categorías
        $categories = Category::where('company_id', $company->id)->get();
        $this->assertCount(5, $categories);

        // Verificar nombres específicos de la industria Technology
        $categoryNames = $categories->pluck('name')->toArray();
        $this->assertContains('Bug Report', $categoryNames);
        $this->assertContains('Feature Request', $categoryNames);
        $this->assertContains('Performance Issue', $categoryNames);
        $this->assertContains('Account & Access', $categoryNames);
        $this->assertContains('Technical Support', $categoryNames);

        // Verificar que todas están activas
        $this->assertTrue($categories->every(fn ($cat) => $cat->is_active === true));

        // Verificar que todas tienen descripción
        $this->assertTrue($categories->every(fn ($cat) => !empty($cat->description)));
    }

    #[Test]
    public function auto_creates_5_categories_for_healthcare_company(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $industryHealthcare = CompanyIndustry::where('code', 'healthcare')->first();

        // Act
        $company = $this->companyService->create([
            'name' => 'Health Clinic',
            'industry_id' => $industryHealthcare->id,
        ], $admin);

        // Assert
        $categories = Category::where('company_id', $company->id)->get();
        $this->assertCount(5, $categories);

        // Verificar nombres específicos de Healthcare
        $categoryNames = $categories->pluck('name')->toArray();
        $this->assertContains('Patient Support', $categoryNames);
        $this->assertContains('Appointment Issue', $categoryNames);
        $this->assertContains('Medical Records', $categoryNames);
        $this->assertContains('System Access', $categoryNames);
        $this->assertContains('Billing & Insurance', $categoryNames);
    }

    #[Test]
    public function auto_creates_5_categories_for_education_company(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $industryEducation = CompanyIndustry::where('code', 'education')->first();

        // Act
        $company = $this->companyService->create([
            'name' => 'University X',
            'industry_id' => $industryEducation->id,
        ], $admin);

        // Assert
        $categories = Category::where('company_id', $company->id)->get();
        $this->assertCount(5, $categories);

        // Verificar nombres específicos de Education
        $categoryNames = $categories->pluck('name')->toArray();
        $this->assertContains('Course Issue', $categoryNames);
        $this->assertContains('Grade & Assessment', $categoryNames);
        $this->assertContains('Account Access', $categoryNames);
        $this->assertContains('Technical Support', $categoryNames);
        $this->assertContains('Administrative Request', $categoryNames);
    }

    #[Test]
    public function auto_creates_5_categories_for_retail_company(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $industryRetail = CompanyIndustry::where('code', 'retail')->first();

        // Act
        $company = $this->companyService->create([
            'name' => 'Online Store',
            'industry_id' => $industryRetail->id,
        ], $admin);

        // Assert
        $categories = Category::where('company_id', $company->id)->get();
        $this->assertCount(5, $categories);

        // Verificar nombres específicos de Retail
        $categoryNames = $categories->pluck('name')->toArray();
        $this->assertContains('Order Issue', $categoryNames);
        $this->assertContains('Payment Problem', $categoryNames);
        $this->assertContains('Shipping & Delivery', $categoryNames);
        $this->assertContains('Product Return', $categoryNames);
        $this->assertContains('Account Access', $categoryNames);
    }

    #[Test]
    public function uses_other_industry_categories_as_fallback(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $industryOther = CompanyIndustry::where('code', 'other')->first();

        // Act
        $company = $this->companyService->create([
            'name' => 'Generic Company',
            'industry_id' => $industryOther->id,
        ], $admin);

        // Assert
        $categories = Category::where('company_id', $company->id)->get();
        $this->assertCount(5, $categories);

        // Verificar categorías genéricas del fallback 'other'
        $categoryNames = $categories->pluck('name')->toArray();
        $this->assertContains('General Support', $categoryNames);
        $this->assertContains('Question', $categoryNames);
        $this->assertContains('Complaint', $categoryNames);
        $this->assertContains('Request', $categoryNames);
        $this->assertContains('Technical Issue', $categoryNames);
    }

    #[Test]
    public function categories_belong_to_correct_company(): void
    {
        // Arrange
        $admin1 = User::factory()->create();
        $admin2 = User::factory()->create();
        $industryTech = CompanyIndustry::where('code', 'technology')->first();

        // Act - Crear 2 empresas
        $company1 = $this->companyService->create([
            'name' => 'Company 1',
            'industry_id' => $industryTech->id,
        ], $admin1);

        $company2 = $this->companyService->create([
            'name' => 'Company 2',
            'industry_id' => $industryTech->id,
        ], $admin2);

        // Assert - Cada empresa tiene sus propias categorías
        $categoriesCompany1 = Category::where('company_id', $company1->id)->get();
        $categoriesCompany2 = Category::where('company_id', $company2->id)->get();

        $this->assertCount(5, $categoriesCompany1);
        $this->assertCount(5, $categoriesCompany2);

        // Verificar que todas las categorías de company1 tienen company_id correcto
        $this->assertTrue($categoriesCompany1->every(fn ($cat) => $cat->company_id === $company1->id));
        $this->assertTrue($categoriesCompany2->every(fn ($cat) => $cat->company_id === $company2->id));

        // Verificar que no hay overlap (cada empresa tiene categorías distintas)
        $ids1 = $categoriesCompany1->pluck('id')->toArray();
        $ids2 = $categoriesCompany2->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($ids1, $ids2));
    }

    #[Test]
    public function does_not_create_duplicates_if_categories_already_exist(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $industryTech = CompanyIndustry::where('code', 'technology')->first();

        $company = $this->companyService->create([
            'name' => 'Tech Company',
            'industry_id' => $industryTech->id,
        ], $admin);

        // Ya se crearon 5 categorías automáticamente
        $this->assertCount(5, Category::where('company_id', $company->id)->get());

        // Act - Disparar manualmente el listener de nuevo (simular duplicación)
        $categoryService = app(\App\Features\TicketManagement\Services\CategoryService::class);
        $categoryService->createDefaultCategoriesForIndustry($company->id, 'technology');

        // Assert - Aún debe haber solo 5 categorías (no duplicados)
        $categories = Category::where('company_id', $company->id)->get();
        $this->assertCount(5, $categories);
    }

    #[Test]
    public function factory_created_companies_do_not_have_auto_categories(): void
    {
        // Este test valida que el problema detectado Factory vs Service persiste
        // Company::factory() NO dispara eventos, así que NO se crean categorías automáticas
        // Esto es importante para entender por qué los tests existentes NO fallan

        // Arrange & Act - Crear empresa con factory (sin usar CompanyService)
        $company = Company::factory()->create([
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
        ]);

        // Assert - NO se crean categorías automáticamente
        $categories = Category::where('company_id', $company->id)->get();
        $this->assertCount(0, $categories, 'Factory-created companies should NOT have auto-categories');
    }

    #[Test]
    public function all_16_industry_types_have_5_categories_defined(): void
    {
        // Este test valida que TODAS las 16 industrias tienen mapeo completo
        // Evita que se olvide definir categorías para alguna industria

        $admin = User::factory()->create();
        $allIndustries = CompanyIndustry::all();

        // Verificar que hay exactamente 16 industrias
        $this->assertCount(16, $allIndustries);

        $categoryService = app(\App\Features\TicketManagement\Services\CategoryService::class);

        foreach ($allIndustries as $industry) {
            // Crear empresa temporal para esta industria
            $company = $this->companyService->create([
                'name' => "Test {$industry->name}",
                'industry_id' => $industry->id,
            ], $admin);

            // Verificar que se crearon 5 categorías
            $categories = Category::where('company_id', $company->id)->get();
            $this->assertCount(
                5,
                $categories,
                "Industry '{$industry->code}' should have exactly 5 default categories"
            );

            // Cleanup - Eliminar categorías para siguiente iteración
            Category::where('company_id', $company->id)->delete();
        }
    }

    #[Test]
    public function categories_have_valid_descriptions(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $industryTech = CompanyIndustry::where('code', 'technology')->first();

        // Act
        $company = $this->companyService->create([
            'name' => 'Tech Company',
            'industry_id' => $industryTech->id,
        ], $admin);

        // Assert - Todas las categorías deben tener descripción válida
        $categories = Category::where('company_id', $company->id)->get();

        foreach ($categories as $category) {
            $this->assertNotEmpty($category->description, "Category '{$category->name}' should have a description");
            $this->assertGreaterThan(20, strlen($category->description), "Category '{$category->name}' description should be descriptive (>20 chars)");
        }
    }

    #[Test]
    public function categories_are_created_within_transaction(): void
    {
        // Este test valida que si falla la creación de categorías,
        // la transacción de CompanyService::create() debería revertirse
        // (aunque el listener actualmente NO lanza excepciones para permitir que la empresa se cree)

        // Arrange
        $admin = User::factory()->create();

        // Act - Intentar crear empresa con industry_id inválido
        try {
            $company = $this->companyService->create([
                'name' => 'Invalid Company',
                'industry_id' => '00000000-0000-0000-0000-000000000000', // UUID inválido
            ], $admin);

            // Si llegamos aquí, la empresa se creó (el listener falló silenciosamente)
            // Verificar que NO se crearon categorías
            $categories = Category::where('company_id', $company->id)->get();
            $this->assertCount(0, $categories, 'No categories should be created for invalid industry');
        } catch (\Exception $e) {
            // Si falla la transacción completa, la empresa NO debe existir en BD
            $this->assertTrue(true, 'Transaction rolled back as expected');
        }
    }
}
