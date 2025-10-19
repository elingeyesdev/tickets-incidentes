<?php

namespace Tests\Feature\CompanyManagement\Queries;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para companies query (contextos)
 *
 * Verifica:
 * - Contexto MINIMAL retorna solo 4 campos
 * - Contexto EXPLORE retorna 11 campos + isFollowedByMe
 * - Contexto MANAGEMENT retorna todos los campos
 * - Filtros funcionan correctamente
 * - Búsqueda por nombre
 * - Paginación
 * - Permisos según contexto
 */
class CompaniesQueryTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function context_minimal_returns_only_4_fields()
    {
        // Arrange
        Company::factory()->count(3)->create();

        // Act
        $response = $this->graphQL('
            query CompaniesMinimal {
                companies(context: MINIMAL, first: 10) {
                    ... on CompanyMinimalList {
                        items {
                            id
                            companyCode
                            name
                            logoUrl
                        }
                        totalCount
                        hasNextPage
                    }
                }
            }
        ');

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'companies' => [
                    'items' => [
                        '*' => [
                            'id',
                            'companyCode',
                            'name',
                            'logoUrl',
                        ],
                    ],
                    'totalCount',
                    'hasNextPage',
                ],
            ],
        ]);

        $items = $response->json('data.companies.items');
        $this->assertCount(3, $items);
        $this->assertEquals(3, $response->json('data.companies.totalCount'));
        $this->assertFalse($response->json('data.companies.hasNextPage'));

        // Verificar que solo tiene 4 campos
        foreach ($items as $item) {
            $this->assertCount(4, $item);
        }
    }

    /** @test */
    public function context_explore_returns_11_fields_plus_is_followed_by_me()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Usuario sigue company1
        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company1->id,
        ]);

        // Act
        $response = $this->actingAs($user)->graphQL('
            query CompaniesExplore {
                companies(context: EXPLORE, first: 10) {
                    ... on CompanyExploreList {
                        items {
                            id
                            companyCode
                            name
                            logoUrl
                            description
                            industry
                            city
                            country
                            primaryColor
                            followersCount
                            isFollowedByMe
                        }
                        totalCount
                        hasNextPage
                    }
                }
            }
        ');

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'companies' => [
                    'items' => [
                        '*' => [
                            'id',
                            'companyCode',
                            'name',
                            'logoUrl',
                            'description',
                            'industry',
                            'city',
                            'country',
                            'primaryColor',
                            'followersCount',
                            'isFollowedByMe',
                        ],
                    ],
                ],
            ],
        ]);

        $items = $response->json('data.companies.items');
        $this->assertCount(2, $items);

        // Verificar isFollowedByMe
        $followedCompany = collect($items)->firstWhere('id', $company1->id);
        $notFollowedCompany = collect($items)->firstWhere('id', $company2->id);

        $this->assertTrue($followedCompany['isFollowedByMe']);
        $this->assertFalse($notFollowedCompany['isFollowedByMe']);
    }

    /** @test */
    public function context_management_returns_all_fields()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        Company::factory()->create();

        // Act
        $response = $this->actingAs($admin)->graphQL('
            query CompaniesManagement {
                companies(context: MANAGEMENT, first: 10) {
                    ... on CompanyFullList {
                        items {
                            id
                            companyCode
                            name
                            legalName
                            status
                            supportEmail
                            website
                            contactCity
                            contactCountry
                            adminId
                            adminName
                            adminEmail
                            activeAgentsCount
                            totalUsersCount
                            totalTicketsCount
                            openTicketsCount
                            followersCount
                            createdAt
                            updatedAt
                        }
                        totalCount
                        hasNextPage
                    }
                }
            }
        ');

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'companies' => [
                    'items' => [
                        '*' => [
                            'id',
                            'companyCode',
                            'name',
                            'legalName',
                            'status',
                            'supportEmail',
                            'adminName',
                            'adminEmail',
                            'followersCount',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function filter_by_status_works()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->count(3)->create(['status' => 'active']);
        Company::factory()->count(2)->suspended()->create();

        // Act
        $response = $this->actingAs($user)->graphQL('
            query CompaniesFiltered {
                companies(
                    context: EXPLORE
                    filters: { status: ACTIVE }
                ) {
                    ... on CompanyExploreList {
                        items {
                            id
                            status
                        }
                        totalCount
                    }
                }
            }
        ', [
            'filters' => ['status' => 'ACTIVE']
        ]);

        // Assert
        $items = $response->json('data.companies.items');
        $this->assertCount(3, $items);

        foreach ($items as $item) {
            $this->assertEquals('ACTIVE', $item['status']);
        }
    }

    /** @test */
    public function filter_by_country_works()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->count(2)->create(['contact_country' => 'Bolivia']);
        Company::factory()->create(['contact_country' => 'Peru']);

        // Act
        $response = $this->actingAs($user)->graphQL('
            query CompaniesFiltered($filters: CompanyFilters) {
                companies(
                    context: EXPLORE
                    filters: $filters
                ) {
                    ... on CompanyExploreList {
                        items {
                            id
                            country
                        }
                        totalCount
                    }
                }
            }
        ', [
            'filters' => ['country' => 'Bolivia']
        ]);

        // Assert
        $items = $response->json('data.companies.items');
        $this->assertCount(2, $items);
        $this->assertEquals(2, $response->json('data.companies.totalCount'));
    }

    /** @test */
    public function filter_followed_by_me_returns_only_followed_companies()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $company3 = Company::factory()->create();

        // Usuario sigue solo company1 y company2
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $company1->id]);
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $company2->id]);

        // Act
        $response = $this->actingAs($user)->graphQL('
            query FollowedCompanies($filters: CompanyFilters) {
                companies(
                    context: EXPLORE
                    filters: $filters
                ) {
                    ... on CompanyExploreList {
                        items {
                            id
                            isFollowedByMe
                        }
                        totalCount
                    }
                }
            }
        ', [
            'filters' => ['followedByMe' => true]
        ]);

        // Assert
        $items = $response->json('data.companies.items');
        $this->assertCount(2, $items);

        foreach ($items as $item) {
            $this->assertTrue($item['isFollowedByMe']);
        }
    }

    /** @test */
    public function search_by_name_works()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->create(['name' => 'Tech Solutions Inc.']);
        Company::factory()->create(['name' => 'Digital Innovations']);
        Company::factory()->create(['name' => 'Hardware Store']);

        // Act
        $response = $this->actingAs($user)->graphQL('
            query SearchCompanies($search: String) {
                companies(
                    context: EXPLORE
                    search: $search
                ) {
                    ... on CompanyExploreList {
                        items {
                            id
                            name
                        }
                        totalCount
                    }
                }
            }
        ', [
            'search' => 'tech'
        ]);

        // Assert
        $items = $response->json('data.companies.items');
        $this->assertCount(1, $items);
        $this->assertStringContainsStringIgnoringCase('tech', $items[0]['name']);
    }

    /** @test */
    public function pagination_works_correctly()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->count(25)->create();

        // Act - Primera página
        $response = $this->actingAs($user)->graphQL('
            query CompaniesPaginated($first: Int, $page: Int) {
                companies(
                    context: EXPLORE
                    first: $first
                    page: $page
                ) {
                    ... on CompanyExploreList {
                        items {
                            id
                        }
                        totalCount
                        hasNextPage
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1
        ]);

        // Assert
        $this->assertCount(10, $response->json('data.companies.items'));
        $this->assertEquals(25, $response->json('data.companies.totalCount'));
        $this->assertTrue($response->json('data.companies.hasNextPage'));
    }

    /** @test */
    public function has_next_page_is_true_when_more_pages_exist()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->count(25)->create();

        // Act
        $response = $this->actingAs($user)->graphQL('
            query {
                companies(context: EXPLORE, first: 20) {
                    ... on CompanyExploreList {
                        hasNextPage
                        totalCount
                    }
                }
            }
        ');

        // Assert
        $this->assertTrue($response->json('data.companies.hasNextPage'));
        $this->assertEquals(25, $response->json('data.companies.totalCount'));
    }

    /** @test */
    public function has_next_page_is_false_when_no_more_pages()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($user)->graphQL('
            query {
                companies(context: EXPLORE, first: 20) {
                    ... on CompanyExploreList {
                        hasNextPage
                        totalCount
                    }
                }
            }
        ');

        // Assert
        $this->assertFalse($response->json('data.companies.hasNextPage'));
        $this->assertEquals(5, $response->json('data.companies.totalCount'));
    }

    /** @test */
    public function is_followed_by_me_is_true_for_followed_companies_in_explore_context()
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
                companies(context: EXPLORE) {
                    ... on CompanyExploreList {
                        items {
                            id
                            isFollowedByMe
                        }
                    }
                }
            }
        ');

        // Assert
        $items = $response->json('data.companies.items');
        $this->assertTrue($items[0]['isFollowedByMe']);
    }

    /** @test */
    public function is_followed_by_me_is_false_for_non_followed_companies()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->create();

        // Act
        $response = $this->actingAs($user)->graphQL('
            query {
                companies(context: EXPLORE) {
                    ... on CompanyExploreList {
                        items {
                            id
                            isFollowedByMe
                        }
                    }
                }
            }
        ');

        // Assert
        $items = $response->json('data.companies.items');
        $this->assertFalse($items[0]['isFollowedByMe']);
    }

    /** @test */
    public function unauthenticated_user_can_access_minimal_context()
    {
        // Arrange
        Company::factory()->count(3)->create();

        // Act - Sin autenticación
        $response = $this->graphQL('
            query {
                companies(context: MINIMAL) {
                    ... on CompanyMinimalList {
                        items {
                            id
                            name
                        }
                        totalCount
                    }
                }
            }
        ');

        // Assert
        $items = $response->json('data.companies.items');
        $this->assertCount(3, $items);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_explore_context()
    {
        // Arrange
        Company::factory()->create();

        // Act - Sin autenticación
        $response = $this->graphQL('
            query {
                companies(context: EXPLORE) {
                    ... on CompanyExploreList {
                        items {
                            id
                        }
                    }
                }
            }
        ');

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }
}
