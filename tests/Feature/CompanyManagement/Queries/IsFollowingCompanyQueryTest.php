<?php

namespace Tests\Feature\CompanyManagement\Queries;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para isFollowingCompany query
 *
 * Verifica:
 * - Retorna true si usuario sigue la empresa
 * - Retorna false si usuario no sigue la empresa
 * - Usuario no autenticado recibe error
 * - Empresa inexistente lanza error COMPANY_NOT_FOUND
 */
class IsFollowingCompanyQueryTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function returns_true_when_user_is_following_company()
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
            query IsFollowing($companyId: UUID!) {
                isFollowingCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'isFollowingCompany' => true,
            ],
        ]);
    }

    /** @test */
    public function returns_false_when_user_is_not_following_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // No crear CompanyFollower

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            query IsFollowing($companyId: UUID!) {
                isFollowingCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'isFollowingCompany' => false,
            ],
        ]);
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act - Sin autenticación
        $response = $this->graphQL('
            query IsFollowing($companyId: UUID!) {
                isFollowingCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function nonexistent_company_throws_company_not_found_error()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            query IsFollowing($companyId: UUID!) {
                isFollowingCompany(companyId: $companyId)
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
    public function returns_false_when_following_different_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Usuario sigue company1 pero no company2
        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company1->id,
        ]);

        // Act - Verificar company2
        $response = $this->authenticateWithJWT($user)->graphQL('
            query IsFollowing($companyId: UUID!) {
                isFollowingCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company2->id
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'isFollowingCompany' => false,
            ],
        ]);
    }

    /** @test */
    public function different_users_have_independent_follow_status()
    {
        // Arrange
        $user1 = User::factory()->withRole('USER')->create();
        $user2 = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Solo user1 sigue la empresa
        CompanyFollower::create([
            'user_id' => $user1->id,
            'company_id' => $company->id,
        ]);

        // Act - user1 consulta
        $response1 = $this->authenticateWithJWT($user1)->graphQL('
            query IsFollowing($companyId: UUID!) {
                isFollowingCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Act - user2 consulta
        $response2 = $this->authenticateWithJWT($user2)->graphQL('
            query IsFollowing($companyId: UUID!) {
                isFollowingCompany(companyId: $companyId)
            }
        ', [
            'companyId' => $company->id
        ]);

        // Assert
        $this->assertTrue($response1->json('data.isFollowingCompany'));
        $this->assertFalse($response2->json('data.isFollowingCompany'));
    }
}
