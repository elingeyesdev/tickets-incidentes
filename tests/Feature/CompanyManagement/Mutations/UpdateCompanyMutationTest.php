<?php

namespace Tests\Feature\CompanyManagement\Mutations;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para updateCompany mutation
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
class UpdateCompanyMutationTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function platform_admin_can_update_any_company()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create(['name' => 'Old Name']);

        $input = [
            'name' => 'Updated Name',
            'supportEmail' => 'newsupport@company.com',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    id
                    name
                    supportEmail
                    updatedAt
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'updateCompany' => [
                    'id' => $company->id,
                    'name' => 'Updated Name',
                    'supportEmail' => 'newsupport@company.com',
                ],
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

        $input = [
            'website' => 'https://new-website.com',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    id
                    website
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'updateCompany' => [
                    'website' => 'https://new-website.com',
                ],
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

        $input = ['name' => 'Hacked Name'];

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    id
                }
            }
        ', [
            'id' => $otherCompany->id,
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('This action is unauthorized');
    }

    /** @test */
    public function agent_cannot_update_company()
    {
        // Arrange
        $agent = User::factory()->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $input = ['name' => 'New Name'];

        // Act
        $response = $this->authenticateWithJWT($agent)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    id
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function user_cannot_update_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        $input = ['name' => 'New Name'];

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    id
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function updates_basic_fields()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $input = [
            'name' => 'New Company Name',
            'legalName' => 'New Legal Name SRL',
            'supportEmail' => 'new@support.com',
            'phone' => '+59133998877',
            'website' => 'https://newwebsite.com',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    name
                    legalName
                    supportEmail
                    phone
                    website
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'updateCompany' => [
                    'name' => 'New Company Name',
                    'legalName' => 'New Legal Name SRL',
                    'supportEmail' => 'new@support.com',
                    'phone' => '+59133998877',
                    'website' => 'https://newwebsite.com',
                ],
            ],
        ]);
    }

    /** @test */
    public function updates_contact_info()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $input = [
            'contactInfo' => [
                'address' => 'New Address 123',
                'city' => 'La Paz',
                'state' => 'La Paz',
                'country' => 'Bolivia',
                'postalCode' => '1234',
                'taxId' => '123456789',
                'legalRepresentative' => 'Juan Pérez',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    contactAddress
                    contactCity
                    taxId
                    legalRepresentative
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
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

        $input = [
            'config' => [
                'businessHours' => [
                    'monday' => ['open' => '08:00', 'close' => '17:00'],
                    'tuesday' => ['open' => '08:00', 'close' => '17:00'],
                ],
                'timezone' => 'America/Sao_Paulo',
                'settings' => [
                    'autoAssignTickets' => false,
                    'allowPublicTickets' => true,
                ],
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    businessHours
                    timezone
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
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

        $input = [
            'branding' => [
                'logoUrl' => 'https://cdn.example.com/new-logo.png',
                'faviconUrl' => 'https://cdn.example.com/new-favicon.ico',
                'primaryColor' => '#2563EB',
                'secondaryColor' => '#1E3A8A',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    logoUrl
                    primaryColor
                    secondaryColor
                    updatedAt
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'updateCompany' => [
                    'logoUrl' => 'https://cdn.example.com/new-logo.png',
                    'primaryColor' => '#2563EB',
                    'secondaryColor' => '#1E3A8A',
                ],
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

        $input = ['name' => 'New Name'];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    id
                }
            }
        ', [
            'id' => $fakeId,
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLError([
            'message' => 'Company not found',
        ]);
    }

    /** @test */
    public function returns_updated_company()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $input = ['name' => 'Updated Company'];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    id
                    name
                    updatedAt
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'updateCompany' => [
                    'id',
                    'name',
                    'updatedAt',
                ],
            ],
        ]);

        $updated = $response->json('data.updateCompany');
        $this->assertEquals('Updated Company', $updated['name']);
        $this->assertNotEmpty($updated['updatedAt']);
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $company = Company::factory()->create();
        $input = ['name' => 'New Name'];

        // Act
        $response = $this->graphQL('
            mutation UpdateCompany($id: UUID!, $input: UpdateCompanyInput!) {
                updateCompany(id: $id, input: $input) {
                    id
                }
            }
        ', [
            'id' => $company->id,
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }
}
