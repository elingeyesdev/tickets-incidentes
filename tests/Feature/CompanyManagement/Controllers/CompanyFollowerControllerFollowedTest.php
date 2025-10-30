<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para CompanyFollowerController@followed (REST API)
 *
 * Migrado desde: tests/Feature/CompanyManagement/Queries/MyFollowedCompaniesQueryTest.php
 * GraphQL Query: myFollowedCompanies
 * REST Endpoint: GET /api/companies/followed
 *
 * Verifica:
 * - Retorna lista vacía si usuario no sigue empresas
 * - Retorna empresas seguidas con metadata
 * - Usuario no autenticado recibe error
 * - Orden correcto (followedAt DESC)
 * - Incluye todos los campos: company, followedAt, myTicketsCount
 */
class CompanyFollowerControllerFollowedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function returns_empty_list_when_user_follows_no_companies()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->count(3)->create();

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson('/api/companies/followed', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    /** @test */
    public function returns_followed_companies_with_metadata()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company1 = Company::factory()->create(['name' => 'Tech Solutions']);
        $company2 = Company::factory()->create(['name' => 'Digital Innovations']);

        // Usuario sigue ambas empresas
        $follower1 = CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company1->id,
        ]);

        sleep(1); // Asegurar diferencia en timestamps

        $follower2 = CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company2->id,
        ]);

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson('/api/companies/followed', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company' => [
                            'id',
                            'company_code',
                            'name',
                            'logo_url',
                        ],
                        'followed_at',
                        'my_tickets_count',
                    ],
                ],
            ]);

        $followedCompanies = $response->json('data');
        $this->assertCount(2, $followedCompanies);

        // Verificar que incluye las empresas correctas
        $companyIds = array_column($followedCompanies, 'company');
        $companyIds = array_column($companyIds, 'id');
        $this->assertContains($company1->id, $companyIds);
        $this->assertContains($company2->id, $companyIds);
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange - Sin autenticación
        Company::factory()->create();

        // Act
        $response = $this->getJson('/api/companies/followed');

        // Assert
        $response->assertStatus(401)
            ->assertJsonStructure(['message', 'code', 'category'])
            ->assertJson(['code' => 'UNAUTHENTICATED']);
    }

    /** @test */
    public function returns_companies_in_followed_at_desc_order()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company1 = Company::factory()->create(['name' => 'First Company']);
        $company2 = Company::factory()->create(['name' => 'Second Company']);
        $company3 = Company::factory()->create(['name' => 'Third Company']);

        // Crear follows en orden específico
        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company1->id,
            'followed_at' => now()->subDays(3),
        ]);

        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company2->id,
            'followed_at' => now()->subDays(1),
        ]);

        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company3->id,
            'followed_at' => now(),
        ]);

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson('/api/companies/followed', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $followedCompanies = $response->json('data');
        $this->assertCount(3, $followedCompanies);

        // Verificar orden DESC (más reciente primero)
        $this->assertEquals($company3->id, $followedCompanies[0]['company']['id']);
        $this->assertEquals($company2->id, $followedCompanies[1]['company']['id']);
        $this->assertEquals($company1->id, $followedCompanies[2]['company']['id']);
    }

    /** @test */
    public function includes_all_required_fields()
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
        $response = $this->getJson('/api/companies/followed', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company',
                        'followed_at',
                        'my_tickets_count',
                        'last_ticket_created_at',
                        'has_unread_announcements',
                    ],
                ],
            ]);

        $followedCompany = $response->json('data.0');
        $this->assertIsInt($followedCompany['my_tickets_count']);
        $this->assertIsBool($followedCompany['has_unread_announcements']);
        $this->assertNotEmpty($followedCompany['followed_at']);
    }

    /** @test */
    public function does_not_return_companies_followed_by_other_users()
    {
        // Arrange
        $user1 = User::factory()->withRole('USER')->create();
        $user2 = User::factory()->withRole('USER')->create();
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // user1 sigue company1
        CompanyFollower::create([
            'user_id' => $user1->id,
            'company_id' => $company1->id,
        ]);

        // user2 sigue company2
        CompanyFollower::create([
            'user_id' => $user2->id,
            'company_id' => $company2->id,
        ]);

        $token = $this->generateAccessToken($user1);

        // Act - user1 consulta sus empresas seguidas
        $response = $this->getJson('/api/companies/followed', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - user1 solo ve company1
        $followedCompanies = $response->json('data');
        $this->assertCount(1, $followedCompanies);
        $this->assertEquals($company1->id, $followedCompanies[0]['company']['id']);
    }
}
