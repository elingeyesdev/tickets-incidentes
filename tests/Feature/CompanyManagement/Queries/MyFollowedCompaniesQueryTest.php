<?php

namespace Tests\Feature\CompanyManagement\Queries;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para myFollowedCompanies query
 *
 * Verifica:
 * - Retorna lista vacía si usuario no sigue empresas
 * - Retorna empresas seguidas con metadata
 * - Usuario no autenticado recibe error
 * - Orden correcto (followedAt DESC)
 * - Incluye todos los campos: company, followedAt, myTicketsCount
 */
class MyFollowedCompaniesQueryTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function returns_empty_list_when_user_follows_no_companies()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($user)->graphQL('
            query {
                myFollowedCompanies {
                    id
                    company {
                        id
                        name
                    }
                }
            }
        ');

        // Assert
        $response->assertJson([
            'data' => [
                'myFollowedCompanies' => [],
            ],
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

        // Act
        $response = $this->actingAs($user)->graphQL('
            query {
                myFollowedCompanies {
                    id
                    company {
                        id
                        companyCode
                        name
                        logoUrl
                    }
                    followedAt
                    myTicketsCount
                }
            }
        ');

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'myFollowedCompanies' => [
                    '*' => [
                        'id',
                        'company' => [
                            'id',
                            'companyCode',
                            'name',
                            'logoUrl',
                        ],
                        'followedAt',
                        'myTicketsCount',
                    ],
                ],
            ],
        ]);

        $followedCompanies = $response->json('data.myFollowedCompanies');
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
        $response = $this->graphQL('
            query {
                myFollowedCompanies {
                    id
                }
            }
        ');

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
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

        // Act
        $response = $this->actingAs($user)->graphQL('
            query {
                myFollowedCompanies {
                    company {
                        id
                        name
                    }
                    followedAt
                }
            }
        ');

        // Assert
        $followedCompanies = $response->json('data.myFollowedCompanies');
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

        // Act
        $response = $this->actingAs($user)->graphQL('
            query {
                myFollowedCompanies {
                    id
                    company {
                        id
                        companyCode
                        name
                        logoUrl
                    }
                    followedAt
                    myTicketsCount
                    lastTicketCreatedAt
                    hasUnreadAnnouncements
                }
            }
        ');

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'myFollowedCompanies' => [
                    '*' => [
                        'id',
                        'company',
                        'followedAt',
                        'myTicketsCount',
                        'lastTicketCreatedAt',
                        'hasUnreadAnnouncements',
                    ],
                ],
            ],
        ]);

        $followedCompany = $response->json('data.myFollowedCompanies.0');
        $this->assertIsInt($followedCompany['myTicketsCount']);
        $this->assertIsBool($followedCompany['hasUnreadAnnouncements']);
        $this->assertNotEmpty($followedCompany['followedAt']);
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

        // Act - user1 consulta sus empresas seguidas
        $response = $this->actingAs($user1)->graphQL('
            query {
                myFollowedCompanies {
                    company {
                        id
                    }
                }
            }
        ');

        // Assert - user1 solo ve company1
        $followedCompanies = $response->json('data.myFollowedCompanies');
        $this->assertCount(1, $followedCompanies);
        $this->assertEquals($company1->id, $followedCompanies[0]['company']['id']);
    }
}
