<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\CompanyManagement\SeedsCompanyIndustries;
use Tests\TestCase;

/**
 * Test suite completo para CompanyIndustryController (REST API)
 *
 * Endpoint: GET /api/company-industries
 *
 * Verifica:
 * - index() retorna todas las 16 industrias
 * - with_counts=true incluye activeCompaniesCount
 * - Ordenamiento alfabético por nombre
 * - Estructura JSON correcta
 * - Campo público (sin autenticación requerida)
 */
class CompanyIndustryControllerTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCompanyIndustries;

    /** @test */
    public function index_returns_all_16_industries()
    {
        // Arrange - Seed industries
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        // Act - Sin autenticación (endpoint público)
        $response = $this->getJson('/api/company-industries');

        // Assert
        $response->assertOk()
            ->assertJsonCount(16, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'description',
                        'createdAt',
                    ]
                ]
            ]);

        $industries = $response->json('data');
        $this->assertCount(16, $industries);

        // Verificar que tiene technology, finance, healthcare, etc
        $codes = collect($industries)->pluck('code')->toArray();
        $this->assertContains('technology', $codes);
        $this->assertContains('finance', $codes);
        $this->assertContains('healthcare', $codes);
    }

    /** @test */
    public function index_with_counts_includes_company_counts()
    {
        // Arrange - Seed industries
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        // Create test companies
        $techIndustry = CompanyIndustry::where('code', 'technology')->first();
        $financeIndustry = CompanyIndustry::where('code', 'finance')->first();

        Company::factory()->count(3)->create(['industry_id' => $techIndustry->id, 'status' => 'active']);
        Company::factory()->count(2)->create(['industry_id' => $financeIndustry->id, 'status' => 'active']);
        Company::factory()->create(['industry_id' => $techIndustry->id, 'status' => 'suspended']); // No cuenta (suspended)

        // Act - Con parámetro with_counts
        $response = $this->getJson('/api/company-industries?with_counts=true');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'description',
                        'activeCompaniesCount', // Solo cuando with_counts=true
                        'createdAt',
                    ]
                ]
            ]);

        $industries = $response->json('data');

        // Verificar conteos correctos
        $tech = collect($industries)->firstWhere('code', 'technology');
        $finance = collect($industries)->firstWhere('code', 'finance');

        $this->assertEquals(3, $tech['activeCompaniesCount']); // Solo activas
        $this->assertEquals(2, $finance['activeCompaniesCount']);
    }

    /** @test */
    public function index_returns_industries_alphabetically_sorted()
    {
        // Arrange - Seed industries
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        // Act
        $response = $this->getJson('/api/company-industries');

        // Assert
        $response->assertOk();

        $industries = $response->json('data');
        $names = collect($industries)->pluck('name')->toArray();

        // Verificar que están ordenados alfabéticamente
        $sortedNames = $names;
        sort($sortedNames);

        $this->assertEquals($sortedNames, $names, 'Industries should be sorted alphabetically by name');
    }

    /** @test */
    public function index_without_counts_does_not_include_active_companies_count()
    {
        // Arrange - Seed industries
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        Company::factory()->count(5)->create(); // Create some companies

        // Act - Sin parámetro with_counts
        $response = $this->getJson('/api/company-industries');

        // Assert
        $response->assertOk();

        $industries = $response->json('data');
        $firstIndustry = $industries[0];

        // activeCompaniesCount NO debe estar presente
        $this->assertArrayNotHasKey('activeCompaniesCount', $firstIndustry);
    }

    /** @test */
    public function index_returns_correct_industry_structure()
    {
        // Arrange - Seed industries
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        // Act
        $response = $this->getJson('/api/company-industries');

        // Assert
        $response->assertOk();

        $industries = $response->json('data');
        $technology = collect($industries)->firstWhere('code', 'technology');

        // Verificar estructura de Technology industry
        $this->assertNotNull($technology);
        $this->assertEquals('technology', $technology['code']);
        $this->assertEquals('Tecnología', $technology['name']);
        $this->assertNotEmpty($technology['description']);
        $this->assertNotEmpty($technology['createdAt']);
        $this->assertArrayHasKey('id', $technology);
    }

    /** @test */
    public function index_is_publicly_accessible_without_authentication()
    {
        // Arrange - Seed industries
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        // Act - Sin token de autenticación
        $response = $this->getJson('/api/company-industries');

        // Assert - Debe funcionar sin autenticación (endpoint público)
        $response->assertOk()
            ->assertJsonCount(16, 'data');
    }
}
