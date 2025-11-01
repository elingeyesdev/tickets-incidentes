<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\CompanyManagement\SeedsCompanyIndustries;
use Tests\TestCase;

/**
 * Test suite completo para CompanyRequestController@index (REST API)
 *
 * Migrado desde: CompanyRequestsQueryTest (GraphQL)
 * Endpoint: GET /api/company-requests
 *
 * Verifica:
 * - PLATFORM_ADMIN puede ver todas las solicitudes
 * - COMPANY_ADMIN no puede ver solicitudes (403)
 * - USER no puede ver solicitudes (403)
 * - Filtros por status funcionan (PENDING, APPROVED, REJECTED)
 * - Sin filtro retorna todas las solicitudes
 * - Paginación con limit funciona
 */
class CompanyRequestControllerIndexTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCompanyIndustries;

    /** @test */
    public function platform_admin_can_view_all_requests()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        CompanyRequest::factory()->count(3)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(2)->create(['status' => 'approved']);

        // Act
        $response = $this->getJson('/api/company-requests', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'requestCode',
                        'companyName',
                        'adminEmail',
                        'status',
                    ],
                ],
            ]);

        $requests = $response->json('data');
        $this->assertCount(5, $requests);
    }

    /** @test */
    public function company_admin_cannot_view_requests()
    {
        // Arrange
        $company = Company::factory()->create();
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $token = $this->generateAccessToken($companyAdmin);

        CompanyRequest::factory()->create();

        // Act
        $response = $this->getJson('/api/company-requests', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions',
                'code' => 'INSUFFICIENT_PERMISSIONS'
            ]);
    }

    /** @test */
    public function user_cannot_view_requests()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        CompanyRequest::factory()->create();

        // Act
        $response = $this->getJson('/api/company-requests', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions',
                'code' => 'INSUFFICIENT_PERMISSIONS'
            ]);
    }

    /** @test */
    public function filter_by_status_pending_works()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        CompanyRequest::factory()->count(3)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(2)->create(['status' => 'approved']);
        CompanyRequest::factory()->create(['status' => 'rejected']);

        // Act
        $response = $this->getJson('/api/company-requests?status=PENDING', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);

        $requests = $response->json('data');
        $this->assertCount(3, $requests);

        foreach ($requests as $request) {
            $this->assertEquals('PENDING', $request['status']);
        }
    }

    /** @test */
    public function filter_by_status_approved_works()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        CompanyRequest::factory()->count(2)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(3)->create(['status' => 'approved']);

        // Act
        $response = $this->getJson('/api/company-requests?status=APPROVED', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);

        $requests = $response->json('data');
        $this->assertCount(3, $requests);

        foreach ($requests as $request) {
            $this->assertEquals('APPROVED', $request['status']);
        }
    }

    /** @test */
    public function filter_by_status_rejected_works()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        CompanyRequest::factory()->count(2)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(2)->create(['status' => 'rejected']);

        // Act
        $response = $this->getJson('/api/company-requests?status=REJECTED', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);

        $requests = $response->json('data');
        $this->assertCount(2, $requests);

        foreach ($requests as $request) {
            $this->assertEquals('REJECTED', $request['status']);
        }
    }

    /** @test */
    public function without_filter_returns_all_requests()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        CompanyRequest::factory()->count(2)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(3)->create(['status' => 'approved']);
        CompanyRequest::factory()->create(['status' => 'rejected']);

        // Act - Sin filtro
        $response = $this->getJson('/api/company-requests', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);

        $requests = $response->json('data');
        $this->assertCount(6, $requests);

        // Verificar que hay de todos los estados
        $statuses = array_column($requests, 'status');
        $this->assertContains('PENDING', $statuses);
        $this->assertContains('APPROVED', $statuses);
        $this->assertContains('REJECTED', $statuses);
    }

    /** @test */
    public function pagination_with_limit_works()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        CompanyRequest::factory()->count(20)->create(['status' => 'pending']);

        // Act
        $response = $this->getJson('/api/company-requests?per_page=10', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);

        $requests = $response->json('data');
        $this->assertCount(10, $requests);
    }

    /** @test */
    public function returns_all_fields_of_company_request()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Test Company',
            'admin_email' => 'admin@test.com',
        ]);

        // Act
        $response = $this->getJson('/api/company-requests', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'requestCode',
                        'companyName',
                        'adminEmail',
                        'businessDescription',
                        'status',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ]);

        $request = $response->json('data.0');
        $this->assertEquals('Test Company', $request['companyName']);
        $this->assertEquals('admin@test.com', $request['adminEmail']);
    }

    /** @test */
    public function unauthenticated_user_receives_401()
    {
        // Arrange
        CompanyRequest::factory()->create();

        // Act - Sin autenticación
        $response = $this->getJson('/api/company-requests');

        // Assert
        $response->assertStatus(401)
            ->assertJsonStructure(['message', 'code', 'category'])
            ->assertJson(['code' => 'UNAUTHENTICATED']);
    }
}
