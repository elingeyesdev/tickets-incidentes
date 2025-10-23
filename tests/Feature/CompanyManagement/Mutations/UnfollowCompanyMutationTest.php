<?php

namespace Tests\Feature\CompanyManagement\Mutations;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para unfollowCompany mutation
 *
 * Verifica:
 * - Usuario puede dejar de seguir empresa (éxito)
 * - Retorna true
 * - No puede unfollow empresa que no sigue (NOT_FOLLOWING)
 * - Empresa inexistente lanza error
 * - Usuario no autenticado recibe error
 * - followersCount disminuye en 1
 */
class UnfollowCompanyMutationTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function user_can_unfollow_company_successfully()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Usuario sigue la empresa
        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation UnfollowCompany($companyId: UUID!) {
                unfollowCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'unfollowCompany' => true,
            ],
        ]);

        // Verificar que se eliminó el registro de BD
        $this->assertDatabaseMissing('business.user_company_followers', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function returns_true_on_success()
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
            mutation UnfollowCompany($companyId: UUID!) {
                unfollowCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $this->assertTrue($response->json('data.unfollowCompany'));
    }

    /** @test */
    public function cannot_unfollow_company_not_following()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Usuario NO sigue la empresa

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation UnfollowCompany($companyId: UUID!) {
                unfollowCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('You are not following this company');

        $errors = $response->json('errors');
        $this->assertEquals('NOT_FOLLOWING', $errors[0]['extensions']['code']);
    }

    /** @test */
    public function nonexistent_company_throws_error()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation UnfollowCompany($companyId: UUID!) {
                unfollowCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $fakeId
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Company not found');
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act - Sin autenticación
        $response = $this->graphQL('
            mutation UnfollowCompany($companyId: UUID!) {
                unfollowCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function followers_count_decreases_by_one()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $initialFollowersCount = CompanyFollower::where('company_id', $company->id)->count();

        // Act
        $this->authenticateWithJWT($user)->graphQL('
            mutation UnfollowCompany($companyId: UUID!) {
                unfollowCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $newFollowersCount = CompanyFollower::where('company_id', $company->id)->count();
        $this->assertEquals($initialFollowersCount - 1, $newFollowersCount);
    }

    /** @test */
    public function can_follow_again_after_unfollowing()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Seguir
        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        // Dejar de seguir
        $this->authenticateWithJWT($user)->graphQL('
            mutation UnfollowCompany($companyId: UUID!) {
                unfollowCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Act - Seguir nuevamente
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
        $response->assertJson([
            'data' => [
                'followCompany' => [
                    'success' => true,
                ],
            ],
        ]);
    }
}
