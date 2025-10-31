<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para PATCH /api/companies/{company} (CompanyController@update)
 *
 * Verifica:
 * - PLATFORM_ADMIN puede actualizar cualquier empresa
 * - COMPANY_ADMIN puede actualizar su propia empresa
 * - COMPANY_ADMIN no puede actualizar otra empresa (permisos)
 * - AGENT no puede actualizar empresa (permisos)
 * - USER no puede actualizar empresa (permisos)
 * - Actualiza campos básicos (name, supportEmail, website)
 * - Actualiza contactInfo
 * - Actualiza config (businessHours, timezone, settings)
 * - Actualiza branding (logoUrl, primaryColor, secondaryColor)
 * - Empresa inexistente lanza error
 * - Retorna Company actualizada
 */
class CompanyControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function platform_admin_can_update_any_company()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create(['name' => 'Old Name']);

        $inputData = [
            'name' => 'Updated Name',
            'support_email' => 'newsupport@company.com',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $company->id,
                    'name' => 'Updated Name',
                    'supportEmail' => 'newsupport@company.com',
                ],
            ]);

        $this->assertDatabaseHas('business.companies', [
            'id' => $company->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function company_admin_can_update_own_company()
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $inputData = [
            'website' => 'https://new-website.com',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'website' => 'https://new-website.com',
                ],
            ]);
    }

    /** @test */
    public function company_admin_cannot_update_another_company()
    {
        // Arrange
        $companyAdmin = User::factory()->create();
        $ownCompany = Company::factory()->create(['admin_user_id' => $companyAdmin->id]);
        $companyAdmin->assignRole('COMPANY_ADMIN', $ownCompany->id);

        $otherCompany = Company::factory()->create();

        $inputData = ['name' => 'Hacked Name'];

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)
            ->patchJson("/api/companies/{$otherCompany->id}", $inputData);

        // DEBUG: Ver respuesta
        dump($response->json());
        dump($response->status());

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'This action is unauthorized']);
    }

    /** @test */
    public function agent_cannot_update_company()
    {
        // Arrange
        $agent = User::factory()->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $inputData = ['name' => 'New Name'];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Insufficient permissions']);
    }

    /** @test */
    public function user_cannot_update_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        $inputData = ['name' => 'New Name'];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Insufficient permissions']);
    }

    /** @test */
    public function updates_basic_fields()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $inputData = [
            'name' => 'New Company Name',
            'legal_name' => 'New Legal Name SRL',
            'support_email' => 'new@support.com',
            'phone' => '+59133998877',
            'website' => 'https://newwebsite.com',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'New Company Name',
                    'legalName' => 'New Legal Name SRL',
                    'supportEmail' => 'new@support.com',
                    'phone' => '+59133998877',
                    'website' => 'https://newwebsite.com',
                ],
            ]);
    }

    /** @test */
    public function updates_contact_info()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $inputData = [
            'contact_info' => [
                'address' => 'New Address 123',
                'city' => 'La Paz',
                'state' => 'La Paz',
                'country' => 'Bolivia',
                'postal_code' => '1234',
                'tax_id' => '123456789',
                'legal_representative' => 'Juan Pérez',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('business.companies', [
            'id' => $company->id,
            'contact_address' => 'New Address 123',
            'contact_city' => 'La Paz',
            'tax_id' => '123456789',
        ]);
    }

    /** @test */
    public function updates_config_business_hours_and_timezone()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $inputData = [
            'config' => [
                'business_hours' => [
                    'monday' => ['open' => '08:00', 'close' => '17:00'],
                    'tuesday' => ['open' => '08:00', 'close' => '17:00'],
                ],
                'timezone' => 'America/Sao_Paulo',
                'settings' => [
                    'auto_assign_tickets' => false,
                    'allow_public_tickets' => true,
                ],
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('business.companies', [
            'id' => $company->id,
            'timezone' => 'America/Sao_Paulo',
        ]);

        $company->refresh();
        $this->assertIsArray($company->business_hours);
    }

    /** @test */
    public function updates_branding()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $inputData = [
            'branding' => [
                'logo_url' => 'https://cdn.example.com/new-logo.png',
                'favicon_url' => 'https://cdn.example.com/new-favicon.ico',
                'primary_color' => '#2563EB',
                'secondary_color' => '#1E3A8A',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'logoUrl' => 'https://cdn.example.com/new-logo.png',
                    'primaryColor' => '#2563EB',
                    'secondaryColor' => '#1E3A8A',
                ],
            ]);

        $this->assertDatabaseHas('business.companies', [
            'id' => $company->id,
            'logo_url' => 'https://cdn.example.com/new-logo.png',
            'primary_color' => '#2563EB',
        ]);
    }

    /** @test */
    public function nonexistent_company_throws_error()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        $inputData = ['name' => 'New Name'];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson("/api/companies/{$fakeId}", $inputData);

        // Assert
        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Company not found']);
    }

    /** @test */
    public function returns_updated_company()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $inputData = ['name' => 'Updated Company'];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'updatedAt',
                ],
            ]);

        $updated = $response->json('data');
        $this->assertEquals('Updated Company', $updated['name']);
        $this->assertNotEmpty($updated['updatedAt']);
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $company = Company::factory()->create();
        $inputData = ['name' => 'New Name'];

        // Act
        $response = $this->patchJson("/api/companies/{$company->id}", $inputData);

        // Assert
        $response->assertStatus(401)
            ->assertJson(['code' => 'UNAUTHENTICATED']);
    }
}
