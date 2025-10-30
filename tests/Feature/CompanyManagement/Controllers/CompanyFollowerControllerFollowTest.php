<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para CompanyFollowerController@follow (REST API)
 *
 * Migrado desde FollowCompanyMutationTest (GraphQL)
 *
 * Verifica:
 * - Usuario puede seguir empresa activa (éxito)
 * - Retorna CompanyFollowResult con success, message, company, followedAt
 * - No puede seguir empresa que ya sigue (ALREADY_FOLLOWING)
 * - No puede seguir empresa suspendida (COMPANY_SUSPENDED)
 * - No puede exceder límite de 50 follows (MAX_FOLLOWS_EXCEEDED)
 * - Empresa inexistente retorna 404 (COMPANY_NOT_FOUND)
 * - Usuario no autenticado recibe 401
 * - followersCount aumenta en 1
 */
class CompanyFollowerControllerFollowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_follow_active_company_successfully()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create(['status' => 'active']);

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->postJson("/api/companies/{$company->id}/follow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
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
            ]);

        $result = $response->json('data');
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

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->postJson("/api/companies/{$company->id}/follow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $result = $response->json('data');
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

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->postJson("/api/companies/{$company->id}/follow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'You are already following this company',
            ]);

        $error = $response->json();
        $this->assertEquals('ALREADY_FOLLOWING', $error['code']);
    }

    /** @test */
    public function cannot_follow_suspended_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->suspended()->create();

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->postJson("/api/companies/{$company->id}/follow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot follow a suspended company',
            ]);

        $error = $response->json();
        $this->assertEquals('COMPANY_SUSPENDED', $error['code']);
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

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->postJson("/api/companies/{$company51->id}/follow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'You have reached the maximum number of companies you can follow',
            ]);

        $error = $response->json();
        $this->assertEquals('MAX_FOLLOWS_EXCEEDED', $error['code']);
        $this->assertEquals(50, $error['currentFollows']);
        $this->assertEquals(50, $error['maxAllowed']);
    }

    /** @test */
    public function nonexistent_company_returns_404()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->postJson("/api/companies/{$fakeId}/follow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Company not found',
            ]);

        $error = $response->json();
        $this->assertEquals('COMPANY_NOT_FOUND', $error['code']);
    }

    /** @test */
    public function unauthenticated_user_cannot_follow_company()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act - Sin autenticación
        $response = $this->postJson("/api/companies/{$company->id}/follow");

        // Assert
        $response->assertStatus(401)
            ->assertJsonStructure(['message', 'code', 'category'])
            ->assertJson(['code' => 'UNAUTHENTICATED']);
    }

    /** @test */
    public function followers_count_increases_by_one()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        $initialFollowersCount = CompanyFollower::where('company_id', $company->id)->count();

        $token = $this->generateAccessToken($user);

        // Act
        $this->postJson("/api/companies/{$company->id}/follow", [], [
            'Authorization' => "Bearer $token"
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

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->postJson("/api/companies/{$company->id}/follow", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $followedAt = $response->json('data.followedAt');
        $this->assertNotEmpty($followedAt);

        // Verificar formato ISO 8601
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $followedAt);
    }
}
