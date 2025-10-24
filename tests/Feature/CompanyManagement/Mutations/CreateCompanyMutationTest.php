<?php

namespace Tests\Feature\CompanyManagement\Mutations;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para createCompany mutation
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
class CreateCompanyMutationTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function platform_admin_can_create_company_directly()
    {
        // Arrange
        $admin = User::factory()->withProfile()->withRole('PLATFORM_ADMIN')->create();
        $adminUser = User::factory()->withProfile()->create(['email' => 'companyadmin@example.com']);

        $input = [
            'name' => 'Enterprise Solutions Corp',
            'legalName' => 'Enterprise Solutions Corporation SRL',
            'adminUserId' => $adminUser->id,
            'supportEmail' => 'support@enterprise-solutions.com',
            'phone' => '+59133445566',
            'website' => 'https://enterprise-solutions.com',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    id
                    companyCode
                    name
                    status
                    adminId
                    adminName
                    createdAt
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'createCompany' => [
                    'id',
                    'companyCode',
                    'name',
                    'status',
                    'adminId',
                    'adminName',
                    'createdAt',
                ],
            ],
        ]);

        $company = $response->json('data.createCompany');
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

        $input = [
            'name' => 'Complete Company',
            'legalName' => 'Complete Company SRL',
            'adminUserId' => $adminUser->id,
            'supportEmail' => 'support@complete.com',
            'phone' => '+59133445566',
            'website' => 'https://complete.com',
            'contactInfo' => [
                'address' => 'Av. Empresarial 789',
                'city' => 'Santa Cruz',
                'state' => 'Santa Cruz',
                'country' => 'Bolivia',
                'postalCode' => '0000',
                'taxId' => '456789123',
                'legalRepresentative' => 'María González',
            ],
            'initialConfig' => [
                'timezone' => 'America/La_Paz',
                'businessHours' => [
                    'monday' => ['open' => '09:00', 'close' => '18:00'],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                ],
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    id
                    name
                    supportEmail
                    website
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $companyId = $response->json('data.createCompany.id');

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

        $input = [
            'name' => 'Test Company',
            'adminUserId' => $adminUser->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    id
                    adminId
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $companyId = $response->json('data.createCompany.id');

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

        $input = [
            'name' => 'New Company',
            'adminUserId' => $adminUser->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    id
                    companyCode
                    name
                    status
                    createdAt
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'createCompany' => [
                    'id',
                    'companyCode',
                    'name',
                    'status',
                    'createdAt',
                ],
            ],
        ]);
    }

    /** @test */
    public function nonexistent_admin_user_throws_admin_user_not_found_error()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $fakeUserId = '550e8400-e29b-41d4-a716-446655440999';

        $input = [
            'name' => 'Test Company',
            'adminUserId' => $fakeUserId,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    id
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLValidationError('adminUserId', 'The selected admin user does not exist.');
    }

    /** @test */
    public function company_admin_cannot_create_company()
    {
        // Arrange
        // Create a company first so we can create a COMPANY_ADMIN with proper context
        $existingCompany = Company::factory()->create();
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN', $existingCompany->id)->create();
        $adminUser = User::factory()->create();

        $input = [
            'name' => 'Test Company',
            'adminUserId' => $adminUser->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    id
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function user_cannot_create_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $adminUser = User::factory()->create();

        $input = [
            'name' => 'Test Company',
            'adminUserId' => $adminUser->id,
        ];

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    id
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function generates_unique_company_code()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $adminUser1 = User::factory()->create();
        $adminUser2 = User::factory()->create();

        // Act - Crear dos empresas
        $response1 = $this->authenticateWithJWT($admin)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    companyCode
                }
            }
        ', [
            'input' => [
                'name' => 'Company 1',
                'adminUserId' => $adminUser1->id,
            ]
        ]);

        $response2 = $this->authenticateWithJWT($admin)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    companyCode
                }
            }
        ', [
            'input' => [
                'name' => 'Company 2',
                'adminUserId' => $adminUser2->id,
            ]
        ]);

        // Assert
        $companyCode1 = $response1->json('data.createCompany.companyCode');
        $companyCode2 = $response2->json('data.createCompany.companyCode');

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

        $input = [
            'name' => 'Test Company',
            'adminUserId' => $adminUser->id,
            'contactInfo' => [
                'address' => 'Test Address',
                'city' => 'Test City',
                'country' => 'Bolivia',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    id
                    name
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'createCompany' => [
                    'name' => 'Test Company',
                ],
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

    //     $input = [
    //         'name' => 'Test Company',
    //         'adminUserId' => $adminUser->id,
    //     ];

    //     // Act
    //     $response = $this->authenticateWithJWT($admin)->graphQL('
    //         mutation CreateCompany($input: CreateCompanyInput!) {
    //             createCompany(input: $input) {
    //                 id
    //                 primaryColor
    //                 secondaryColor
    //             }
    //         }
    //     ', [
    //         'input' => $input
    //     ]);

    //     // Assert - Colores por defecto
    //     $company = $response->json('data.createCompany');
    //     $this->assertNotEmpty($company['primaryColor']);
    //     $this->assertNotEmpty($company['secondaryColor']);
    // }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $adminUser = User::factory()->create();

        $input = [
            'name' => 'Test Company',
            'adminUserId' => $adminUser->id,
        ];

        // Act
        $response = $this->graphQL('
            mutation CreateCompany($input: CreateCompanyInput!) {
                createCompany(input: $input) {
                    id
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }
}
