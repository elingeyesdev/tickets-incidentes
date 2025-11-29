<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\CompanyManagement\SeedsCompanyIndustries;
use Tests\TestCase;

/**
 * Test suite completo para POST /api/companies (CompanyController@store)
 *
 * Verifica:
 * - PLATFORM_ADMIN puede crear empresa directamente
 * - Crea empresa con datos completos (contactInfo, config, branding)
 * - Asigna rol COMPANY_ADMIN al admin user
 * - Retorna Company creada
 * - Admin user inexistente lanza error (ADMIN_USER_NOT_FOUND)
 * - COMPANY_ADMIN no puede crear empresa (permisos)
 * - USER no puede crear empresa (permisos)
 * - Genera companyCode único (formato CMP-YYYY-NNNNN)
 * - Validación de campos opcionales
 */
class CompanyControllerCreateTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCompanyIndustries;

    /** @test */
    public function platform_admin_can_create_company_directly()
    {
        // Arrange
        $admin = User::factory()->withProfile()->withRole('PLATFORM_ADMIN')->create();
        $adminUser = User::factory()->withProfile()->create(['email' => 'companyadmin@example.com']);
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        $inputData = [
            'name' => 'Enterprise Solutions Corp',
            'legal_name' => 'Enterprise Solutions Corporation SRL',
            'industry_id' => $industry->id,
            'admin_user_id' => $adminUser->id,
            'support_email' => 'support@enterprise-solutions.com',
            'phone' => '+59133445566',
            'website' => 'https://enterprise-solutions.com',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/companies', $inputData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'companyCode',
                    'name',
                    'status',
                    'adminId',
                    'adminName',
                    'createdAt',
                ],
            ]);

        $company = $response->json('data');
        $this->assertEquals('Enterprise Solutions Corp', $company['name']);
        $this->assertEquals('ACTIVE', $company['status']);
        $this->assertEquals($adminUser->id, $company['adminId']);
    }

    /** @test */
    public function creates_company_with_complete_data()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $adminUser = User::factory()->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        $inputData = [
            'name' => 'Complete Company',
            'legal_name' => 'Complete Company SRL',
            'industry_id' => $industry->id,
            'admin_user_id' => $adminUser->id,
            'support_email' => 'support@complete.com',
            'phone' => '+59133445566',
            'website' => 'https://complete.com',
            'contact_info' => [
                'address' => 'Av. Empresarial 789',
                'city' => 'Santa Cruz',
                'state' => 'Santa Cruz',
                'country' => 'Bolivia',
                'postal_code' => '0000',
                'tax_id' => '456789123',
                'legal_representative' => 'María González',
            ],
            'initial_config' => [
                'timezone' => 'America/La_Paz',
                'business_hours' => [
                    'monday' => ['open' => '09:00', 'close' => '18:00'],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                ],
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/companies', $inputData);

        // Assert
        $response->assertStatus(201);

        $companyId = $response->json('data.id');

        $this->assertDatabaseHas('business.companies', [
            'id' => $companyId,
            'name' => 'Complete Company',
            'support_email' => 'support@complete.com',
        ]);
    }

    /** @test */
    public function assigns_company_admin_role_to_admin_user()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $adminUser = User::factory()->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        $inputData = [
            'name' => 'Test Company',
            'industry_id' => $industry->id,
            'admin_user_id' => $adminUser->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/companies', $inputData);

        // Assert
        $response->assertStatus(201);

        $companyId = $response->json('data.id');

        $this->assertDatabaseHas('auth.user_roles', [
            'user_id' => $adminUser->id,
            'role_code' => 'COMPANY_ADMIN',
            'company_id' => $companyId,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function returns_created_company()
    {
        // Arrange
        $admin = User::factory()->withProfile()->withRole('PLATFORM_ADMIN')->create();
        $adminUser = User::factory()->withProfile()->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        $inputData = [
            'name' => 'New Company',
            'industry_id' => $industry->id,
            'admin_user_id' => $adminUser->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/companies', $inputData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'companyCode',
                    'name',
                    'status',
                    'createdAt',
                ],
            ]);
    }

    /** @test */
    public function nonexistent_admin_user_throws_admin_user_not_found_error()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();
        $fakeUserId = '550e8400-e29b-41d4-a716-446655440999';

        $inputData = [
            'name' => 'Test Company',
            'industry_id' => $industry->id,
            'admin_user_id' => $fakeUserId,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/companies', $inputData);

        // Assert - Laravel REST validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['admin_user_id']);
    }

    /** @test */
    public function company_admin_cannot_create_company()
    {
        // Arrange
        // Create a company first so we can create a COMPANY_ADMIN with proper context
        $existingCompany = Company::factory()->create();
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN', $existingCompany->id)->create();
        $adminUser = User::factory()->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        $inputData = [
            'name' => 'Test Company',
            'industry_id' => $industry->id,
            'admin_user_id' => $adminUser->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)
            ->postJson('/api/companies', $inputData);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Insufficient permissions']);
    }

    /** @test */
    public function user_cannot_create_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $adminUser = User::factory()->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        $inputData = [
            'name' => 'Test Company',
            'industry_id' => $industry->id,
            'admin_user_id' => $adminUser->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/companies', $inputData);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Insufficient permissions']);
    }

    /** @test */
    public function generates_unique_company_code()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $adminUser1 = User::factory()->create();
        $adminUser2 = User::factory()->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        // Act - Crear dos empresas
        $response1 = $this->authenticateWithJWT($admin)
            ->postJson('/api/companies', [
                'name' => 'Company 1',
                'industry_id' => $industry->id,
                'admin_user_id' => $adminUser1->id,
            ]);

        $response2 = $this->authenticateWithJWT($admin)
            ->postJson('/api/companies', [
                'name' => 'Company 2',
                'industry_id' => $industry->id,
                'admin_user_id' => $adminUser2->id,
            ]);

        // Assert
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $companyCode1 = $response1->json('data.companyCode');
        $companyCode2 = $response2->json('data.companyCode');

        $this->assertNotEquals($companyCode1, $companyCode2);
        $this->assertMatchesRegularExpression('/^CMP-\d{4}-\d{5}$/', $companyCode1);
        $this->assertMatchesRegularExpression('/^CMP-\d{4}-\d{5}$/', $companyCode2);
    }

    /** @test */
    public function validates_optional_contact_info_fields()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $adminUser = User::factory()->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        $inputData = [
            'name' => 'Test Company',
            'industry_id' => $industry->id,
            'admin_user_id' => $adminUser->id,
            'contact_info' => [
                'address' => 'Test Address',
                'city' => 'Test City',
                'country' => 'Bolivia',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/companies', $inputData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Test Company',
                ],
            ]);
    }

    // /** @test */
    // COMENTADO: CreateCompanyInput NO tiene campo branding, solo UpdateCompanyInput
    // public function validates_optional_branding_fields()
    // {
    //     // Arrange
    //     $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
    //     $adminUser = User::factory()->create();
    //
    //     $inputData = [
    //         'name' => 'Test Company',
    //         'admin_user_id' => $adminUser->id,
    //     ];
    //
    //     // Act
    //     $response = $this->authenticateWithJWT($admin)
    //         ->postJson('/api/companies', $inputData);
    //
    //     // Assert - Colores por defecto
    //     $response->assertStatus(201);
    //     $company = $response->json('data');
    //     $this->assertNotEmpty($company['primary_color']);
    //     $this->assertNotEmpty($company['secondary_color']);
    // }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $adminUser = User::factory()->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        $inputData = [
            'name' => 'Test Company',
            'industry_id' => $industry->id,
            'admin_user_id' => $adminUser->id,
        ];

        // Act
        $response = $this->postJson('/api/companies', $inputData);

        // Assert
        $response->assertStatus(401)
            ->assertJson(['code' => 'UNAUTHENTICATED']);
    }

    /** @test */
    public function can_create_company_with_settings_areas_enabled()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $adminUser = User::factory()->create();
        $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::inRandomOrder()->first();

        $inputData = [
            'name' => 'Company with Areas Enabled',
            'industry_id' => $industry->id,
            'admin_user_id' => $adminUser->id,
            'settings' => [
                'areas_enabled' => true,
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/companies', $inputData);

        // Assert
        $response->assertStatus(201);

        $companyId = $response->json('data.id');
        $company = Company::find($companyId);

        $this->assertNotNull($company);
        $this->assertTrue($company->hasAreasEnabled());
        $this->assertTrue($company->settings['areas_enabled']);
    }
}
