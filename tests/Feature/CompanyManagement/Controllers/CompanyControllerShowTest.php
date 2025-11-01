<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para CompanyController@show (REST API)
 *
 * Migrado desde CompanyQueryTest (GraphQL)
 *
 * Verifica:
 * - Usuario autenticado puede ver empresa
 * - Usuario no autenticado recibe error
 * - Retorna 404 para empresa inexistente
 * - Permisos por rol (PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER)
 * - Retorna todos los campos del type Company
 * - isFollowedByMe se calcula correctamente
 */
class CompanyControllerShowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_view_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $company->id,
                    'companyCode' => $company->company_code,
                    'name' => $company->name,
                    'status' => 'ACTIVE',
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_view_company()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $response = $this->getJson("/api/companies/{$company->id}");

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function returns_404_for_nonexistent_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson("/api/companies/{$fakeId}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function platform_admin_can_view_any_company()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $token = $this->generateAccessToken($admin);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $company->id,
                ],
            ]);
    }

    /** @test */
    public function company_admin_can_view_own_company()
    {
        // Arrange
        $admin = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $admin->id]);
        $admin->assignRole('COMPANY_ADMIN', $company->id);

        $token = $this->generateAccessToken($admin);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $company->id,
                    'adminId' => $admin->id,
                ],
            ]);
    }

    /** @test */
    public function agent_can_view_their_company()
    {
        // Arrange
        $agent = User::factory()->create();
        $company = Company::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $token = $this->generateAccessToken($agent);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $company->id,
                ],
            ]);
    }

    /** @test */
    public function user_can_view_active_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create(['status' => 'active']);

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'ACTIVE',
                ],
            ]);
    }

    /** @test */
    public function returns_all_fields_of_company_type()
    {
        // Arrange - Seed industries first
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'companyCode',
                    'name',
                    'legalName',
                    'description',  // NEW V8.0
                    'industryId',   // NEW V8.0
                    'industry' => [ // NEW V8.0
                        'id',
                        'code',
                        'name',
                        'description',
                    ],
                    'supportEmail',
                    'phone',
                    'website',
                    'contactAddress',
                    'contactCity',
                    'contactCountry',
                    'taxId',
                    'legalRepresentative',
                    'businessHours',
                    'timezone',
                    'logoUrl',
                    'primaryColor',
                    'secondaryColor',
                    'status',
                    'adminId',
                    'adminName',
                    'adminEmail',
                    'activeAgentsCount',
                    'totalUsersCount',
                    'totalTicketsCount',
                    'openTicketsCount',
                    'followersCount',
                    'isFollowedByMe',
                    'createdAt',
                    'updatedAt',
                ],
            ]);

        $companyData = $response->json('data');
        $this->assertEquals($company->id, $companyData['id']);
        $this->assertEquals($company->company_code, $companyData['companyCode']);
    }

    /** @test */
    public function is_followed_by_me_is_true_when_user_follows_company()
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
        $response = $this->getJson("/api/companies/{$company->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'isFollowedByMe' => true,
                ],
            ]);
    }

    /** @test */
    public function is_followed_by_me_is_false_when_user_does_not_follow_company()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'isFollowedByMe' => false,
                ],
            ]);
    }
}
