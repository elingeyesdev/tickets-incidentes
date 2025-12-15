<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para CompanyFollowerController@unfollow (REST API)
 *
 * Migrado desde UnfollowCompanyMutationTest (GraphQL)
 *
 * Verifica:
 * - Usuario puede dejar de seguir empresa (éxito)
 * - Retorna success true
 * - No puede unfollow empresa que no sigue (NOT_FOLLOWING)
 * - Empresa inexistente retorna 404
 * - Usuario no autenticado recibe 401
 * - followersCount disminuye en 1
 */
class CompanyFollowerControllerUnfollowTest extends TestCase
{
    use RefreshDatabase;

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

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->deleteJson("/api/companies/{$company->id}/unfollow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'success' => true,
                ],
            ]);

        // Verificar que se eliminó el registro de BD
        $this->assertDatabaseMissing('business.user_company_followers', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function returns_success_true_on_success()
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
        $response = $this->deleteJson("/api/companies/{$company->id}/unfollow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $this->assertTrue($response->json('data.success'));
    }

    /** @test */
    public function cannot_unfollow_company_not_following()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Usuario NO sigue la empresa

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->deleteJson("/api/companies/{$company->id}/unfollow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'code' => 'NOT_FOLLOWING',
                'category' => 'validation',
            ])
            ->assertJsonStructure(['message', 'code', 'category']);

        $error = $response->json();
        $this->assertEquals('NOT_FOLLOWING', $error['code']);
    }

    /** @test */
    public function nonexistent_company_returns_404()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->deleteJson("/api/companies/{$fakeId}/unfollow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Company not found',
            ]);
    }

    /** @test */
    public function unauthenticated_user_receives_401()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act - Sin autenticación
        $response = $this->deleteJson("/api/companies/{$company->id}/unfollow");

        // Assert
        $response->assertStatus(401)
            ->assertJsonStructure(['message', 'code', 'category'])
            ->assertJson(['code' => 'UNAUTHENTICATED']);
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

        $token = $this->generateAccessToken($user);

        // Act
        $this->deleteJson("/api/companies/{$company->id}/unfollow", [], [
            'Authorization' => "Bearer $token"
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

        $token = $this->generateAccessToken($user);

        // Dejar de seguir
        $this->deleteJson("/api/companies/{$company->id}/unfollow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Act - Seguir nuevamente
        $response = $this->postJson("/api/companies/{$company->id}/follow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'success' => true,
                ],
            ]);
    }
}
