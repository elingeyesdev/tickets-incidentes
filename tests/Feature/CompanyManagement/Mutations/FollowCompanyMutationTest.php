<?php

namespace Tests\Feature\CompanyManagement\Mutations;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para followCompany mutation
 *
 * Verifica:
 * - Usuario puede seguir empresa activa (éxito)
 * - Retorna CompanyFollowResult con success, message, company, followedAt
 * - No puede seguir empresa que ya sigue (ALREADY_FOLLOWING)
 * - No puede seguir empresa suspendida (COMPANY_SUSPENDED)
 * - No puede exceder límite de 50 follows (MAX_FOLLOWS_EXCEEDED)
 * - Empresa inexistente lanza error (COMPANY_NOT_FOUND)
 * - Usuario no autenticado recibe error
 * - followersCount aumenta en 1
 */
class FollowCompanyMutationTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function user_can_follow_active_company_successfully()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create(['status' => 'active']);

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation FollowCompany($companyId: UUID!) {
                followCompany(companyId: $companyId) {
                    success
                    message
                    company {
                        id
                        companyCode
                        name
                        logoUrl
                    }
                    followedAt
                }
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'followCompany' => [
                    'success',
                    'message',
                    'company' => [
                        'id',
                        'companyCode',
                        'name',
                        'logoUrl',
                    ],
                    'followedAt',
                ],
            ],
        ]);

        $result = $response->json('data.followCompany');
        $this->assertTrue($result['success']);
        $this->assertStringContainsString($company->name, $result['message']);
        $this->assertEquals($company->id, $result['company']['id']);
        $this->assertNotEmpty($result['followedAt']);

        // Verificar que se creó el registro en BD
        $this->assertDatabaseHas('business.user_company_followers', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function returns_company_follow_result_with_all_fields()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create(['name' => 'Tech Solutions Inc.']);

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation FollowCompany($companyId: UUID!) {
                followCompany(companyId: $companyId) {
                    success
                    message
                    company {
                        id
                        companyCode
                        name
                        logoUrl
                    }
                    followedAt
                }
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $result = $response->json('data.followCompany');
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('company', $result);
        $this->assertArrayHasKey('followedAt', $result);

        $this->assertIsString($result['message']);
        $this->assertIsString($result['followedAt']);
        $this->assertIsArray($result['company']);
    }

    /** @test */
    public function cannot_follow_company_already_following()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Usuario ya sigue la empresa
        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation FollowCompany($companyId: UUID!) {
                followCompany(companyId: $companyId) {
                    success
                }
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertGraphQLError([
            'message' => 'You are already following this company',
        ]);

        $errors = $response->json('errors');
        $this->assertEquals('ALREADY_FOLLOWING', $errors[0]['extensions']['code']);
    }

    /** @test */
    public function cannot_follow_suspended_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->suspended()->create();

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation FollowCompany($companyId: UUID!) {
                followCompany(companyId: $companyId) {
                    success
                }
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertGraphQLError([
            'message' => 'Cannot follow a suspended company',
        ]);

        $errors = $response->json('errors');
        $this->assertEquals('COMPANY_SUSPENDED', $errors[0]['extensions']['code']);
    }

    /** @test */
    public function cannot_exceed_max_follows_limit_of_50()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Crear 50 follows (límite)
        Company::factory()->count(50)->create()->each(function ($company) use ($user) {
            CompanyFollower::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);
        });

        // Crear una empresa más para intentar seguir
        $company51 = Company::factory()->create();

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation FollowCompany($companyId: UUID!) {
                followCompany(companyId: $companyId) {
                    success
                }
            }
        ', [
            'companyId' => $company51->id
        ]);

        // Assert
        $response->assertGraphQLError([
            'message' => 'You have reached the maximum number of companies you can follow',
        ]);

        $errors = $response->json('errors');
        $this->assertEquals('MAX_FOLLOWS_EXCEEDED', $errors[0]['extensions']['code']);
        $this->assertEquals(50, $errors[0]['extensions']['currentFollows']);
        $this->assertEquals(50, $errors[0]['extensions']['maxAllowed']);
    }

    /** @test */
    public function nonexistent_company_throws_company_not_found_error()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation FollowCompany($companyId: UUID!) {
                followCompany(companyId: $companyId) {
                    success
                }
            }
        ', [
            'companyId' => $fakeId
        ]);

        // Assert
        $response->assertGraphQLError([
            'message' => 'Company not found',
        ]);

        $errors = $response->json('errors');
        $this->assertEquals('COMPANY_NOT_FOUND', $errors[0]['extensions']['code']);
    }

    /** @test */
    public function unauthenticated_user_cannot_follow_company()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act - Sin autenticación
        $response = $this->graphQL('
            mutation FollowCompany($companyId: UUID!) {
                followCompany(companyId: $companyId) {
                    success
                }
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function followers_count_increases_by_one()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        $initialFollowersCount = CompanyFollower::where('company_id', $company->id)->count();

        // Act
        $this->authenticateWithJWT($user)->graphQL('
            mutation FollowCompany($companyId: UUID!) {
                followCompany(companyId: $companyId) {
                    success
                }
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $newFollowersCount = CompanyFollower::where('company_id', $company->id)->count();
        $this->assertEquals($initialFollowersCount + 1, $newFollowersCount);
    }

    /** @test */
    public function followed_at_timestamp_is_recorded()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation FollowCompany($companyId: UUID!) {
                followCompany(companyId: $companyId) {
                    followedAt
                }
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $followedAt = $response->json('data.followCompany.followedAt');
        $this->assertNotEmpty($followedAt);

        // Verificar formato ISO 8601
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $followedAt);
    }
}
