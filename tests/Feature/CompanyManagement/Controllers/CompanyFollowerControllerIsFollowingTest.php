<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para CompanyFollowerController@isFollowing (REST API)
 *
 * Migrado desde: tests/Feature/CompanyManagement/Queries/IsFollowingCompanyQueryTest.php
 * GraphQL Query: isFollowingCompany
 * REST Endpoint: GET /api/companies/{company}/is-following
 *
 * Verifica:
 * - Retorna true si usuario sigue la empresa
 * - Retorna false si usuario no sigue la empresa
 * - Usuario no autenticado recibe error
 * - Empresa inexistente lanza error COMPANY_NOT_FOUND
 */
class CompanyFollowerControllerIsFollowingTest extends TestCase
{
    use RefreshDatabase;

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

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/is-following", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_following' => true,
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

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/is-following", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_following' => false,
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act - Sin autenticación
        $response = $this->getJson("/api/companies/{$company->id}/is-following");

        // Assert
        $response->assertStatus(401)
            ->assertJsonStructure(['message', 'code', 'category'])
            ->assertJson(['code' => 'UNAUTHENTICATED']);
    }

    /** @test */
    public function nonexistent_company_throws_company_not_found_error()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson("/api/companies/{$fakeId}/is-following", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Company not found',
                'error' => [
                    'code' => 'COMPANY_NOT_FOUND'
                ]
            ]);
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

        $token = $this->generateAccessToken($user);

        // Act - Verificar company2
        $response = $this->getJson("/api/companies/{$company2->id}/is-following", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_following' => false,
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

        $token1 = $this->generateAccessToken($user1);
        $token2 = $this->generateAccessToken($user2);

        // Act - user1 consulta
        $response1 = $this->getJson("/api/companies/{$company->id}/is-following", [
            'Authorization' => "Bearer $token1"
        ]);

        // Act - user2 consulta
        $response2 = $this->getJson("/api/companies/{$company->id}/is-following", [
            'Authorization' => "Bearer $token2"
        ]);

        // Assert
        $this->assertTrue($response1->json('data.is_following'));
        $this->assertFalse($response2->json('data.is_following'));
    }
}
