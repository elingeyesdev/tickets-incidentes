<?php

namespace Tests\Feature\CompanyManagement\Queries;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para company query
 *
 * Verifica:
 * - Usuario autenticado puede ver empresa
 * - Usuario no autenticado recibe error
 * - Retorna null para empresa inexistente
 * - Permisos por rol (PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER)
 * - Retorna todos los campos del type Company
 * - isFollowedByMe se calcula correctamente
 */
class CompanyQueryTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function authenticated_user_can_view_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    companyCode
                    name
                    status
                }
            }
        ', [
            'id' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'company' => [
                    'id' => $company->id,
                    'companyCode' => $company->company_code,
                    'name' => $company->name,
                    'status' => 'ACTIVE',
                ],
            ],
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_view_company()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $response = $this->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    name
                }
            }
        ', [
            'id' => $company->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function returns_null_for_nonexistent_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    name
                }
            }
        ', [
            'id' => $fakeId
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'company' => null,
            ],
        ]);
    }

    /** @test */
    public function platform_admin_can_view_any_company()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    name
                    status
                }
            }
        ', [
            'id' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'company' => [
                    'id' => $company->id,
                ],
            ],
        ]);
    }

    /** @test */
    public function company_admin_can_view_own_company()
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    name
                    adminId
                }
            }
        ', [
            'id' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'company' => [
                    'id' => $company->id,
                    'adminId' => $admin->id,
                ],
            ],
        ]);
    }

    /** @test */
    public function agent_can_view_their_company()
    {
        // Arrange
        $agent = User::factory()->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        // Act
        $response = $this->authenticateWithJWT($agent)->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    name
                }
            }
        ', [
            'id' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'company' => [
                    'id' => $company->id,
                ],
            ],
        ]);
    }

    /** @test */
    public function user_can_view_active_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create(['status' => 'active']);

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    status
                }
            }
        ', [
            'id' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'company' => [
                    'status' => 'ACTIVE',
                ],
            ],
        ]);
    }

    /** @test */
    public function returns_all_fields_of_company_type()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    companyCode
                    name
                    legalName
                    supportEmail
                    phone
                    website
                    contactAddress
                    contactCity
                    contactCountry
                    taxId
                    legalRepresentative
                    businessHours
                    timezone
                    logoUrl
                    primaryColor
                    secondaryColor
                    status
                    adminId
                    adminName
                    adminEmail
                    activeAgentsCount
                    totalUsersCount
                    totalTicketsCount
                    openTicketsCount
                    followersCount
                    isFollowedByMe
                    createdAt
                    updatedAt
                }
            }
        ', [
            'id' => $company->id
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'company' => [
                    'id',
                    'companyCode',
                    'name',
                    'legalName',
                    'supportEmail',
                    'phone',
                    'website',
                    'contactAddress',
                    'contactCity',
                    'contactCountry',
                    'taxId',
                    'legalRepresentative',
                    'businessHours',
                    'timezone',
                    'logoUrl',
                    'primaryColor',
                    'secondaryColor',
                    'status',
                    'adminId',
                    'adminName',
                    'adminEmail',
                    'activeAgentsCount',
                    'totalUsersCount',
                    'totalTicketsCount',
                    'openTicketsCount',
                    'followersCount',
                    'isFollowedByMe',
                    'createdAt',
                    'updatedAt',
                ],
            ],
        ]);

        $companyData = $response->json('data.company');
        $this->assertEquals($company->id, $companyData['id']);
        $this->assertEquals($company->company_code, $companyData['companyCode']);
    }

    /** @test */
    public function is_followed_by_me_is_true_when_user_follows_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    isFollowedByMe
                }
            }
        ', [
            'id' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'company' => [
                    'isFollowedByMe' => true,
                ],
            ],
        ]);
    }

    /** @test */
    public function is_followed_by_me_is_false_when_user_does_not_follow_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            query GetCompany($id: UUID!) {
                company(id: $id) {
                    id
                    isFollowedByMe
                }
            }
        ', [
            'id' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'company' => [
                    'isFollowedByMe' => false,
                ],
            ],
        ]);
    }
}
