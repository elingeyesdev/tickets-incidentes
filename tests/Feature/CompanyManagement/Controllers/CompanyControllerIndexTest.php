<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para GET /api/companies (CompanyController index endpoints)
 *
 * Verifica:
 * - Contexto MINIMAL retorna solo 4 campos (GET /api/companies/minimal)
 * - Contexto EXPLORE retorna 11 campos + isFollowedByMe (GET /api/companies/explore)
 * - Contexto MANAGEMENT retorna todos los campos (GET /api/companies)
 * - Filtros funcionan correctamente
 * - Búsqueda por nombre
 * - Paginación
 * - Permisos según contexto
 */
class CompanyControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function context_minimal_returns_only_4_fields()
    {
        // Arrange
        Company::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/companies/minimal');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'companyCode',
                        'name',
                        'logoUrl',
                    ],
                ],
                'meta' => ['total', 'currentPage', 'lastPage', 'perPage'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);

        $items = $response->json('data');
        $this->assertCount(3, $items);
        $this->assertEquals(3, $response->json('meta.total'));
        $this->assertNull($response->json('links.next'));

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
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
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
                'meta' => ['total', 'currentPage', 'lastPage', 'perPage'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);

        $items = $response->json('data');
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
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/companies');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'companyCode',
                        'name',
                        'legalName',
                        'status',
                        'supportEmail',
                        'website',
                        'contactCity',
                        'contactCountry',
                        'adminId',
                        'adminName',
                        'adminEmail',
                        'activeAgentsCount',
                        'totalUsersCount',
                        'totalTicketsCount',
                        'openTicketsCount',
                        'followersCount',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
                'meta' => ['total', 'currentPage', 'lastPage', 'perPage'],
                'links' => ['first', 'last', 'prev', 'next'],
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
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore?status=ACTIVE');

        // Assert
        $items = $response->json('data');
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
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore?country=Bolivia');

        // Assert
        $items = $response->json('data');
        $this->assertCount(2, $items);
        $this->assertEquals(2, $response->json('meta.total'));
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
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore?followed_by_me=true');

        // Assert
        $items = $response->json('data');
        $this->assertCount(2, $items);

        foreach ($items as $item) {
            $this->assertTrue($item['is_followed_by_me']);
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
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore?search=tech');

        // Assert
        $items = $response->json('data');
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
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore?per_page=10&page=1');

        // Assert
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertNotNull($response->json('links.next'));
    }

    /** @test */
    public function has_next_page_is_true_when_more_pages_exist()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->count(25)->create();

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore?per_page=20');

        // Assert
        $this->assertNotNull($response->json('links.next'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    /** @test */
    public function has_next_page_is_false_when_no_more_pages()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->count(5)->create();

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore?per_page=20');

        // Assert
        $this->assertNull($response->json('links.next'));
        $this->assertEquals(5, $response->json('meta.total'));
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
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore');

        // Assert
        $items = $response->json('data');
        $this->assertTrue($items[0]['is_followed_by_me']);
    }

    /** @test */
    public function is_followed_by_me_is_false_for_non_followed_companies()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        Company::factory()->create();

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore');

        // Assert
        $items = $response->json('data');
        $this->assertFalse($items[0]['is_followed_by_me']);
    }

    /** @test */
    public function unauthenticated_user_can_access_minimal_context()
    {
        // Arrange
        Company::factory()->count(3)->create();

        // Act - Sin autenticación
        $response = $this->getJson('/api/companies/minimal');

        // Assert
        $items = $response->json('data');
        $this->assertCount(3, $items);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_explore_context()
    {
        // Arrange
        Company::factory()->create();

        // Act - Sin autenticación
        $response = $this->getJson('/api/companies/explore');

        // Assert
        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Unauthenticated']);
    }
}
