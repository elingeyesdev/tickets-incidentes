<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyOnboardingDetails;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\CompanyManagement\SeedsCompanyIndustries;
use Tests\TestCase;

/**
 * Test suite completo para CompanyRequestController@index (REST API)
 *
 * ARQUITECTURA NORMALIZADA:
 * - Las solicitudes ahora son Company con status='pending', 'active', 'rejected'
 * - Los detalles de onboarding están en CompanyOnboardingDetails
 *
 * Endpoint: GET /api/company-requests
 *
 * Verifica:
 * - PLATFORM_ADMIN puede ver todas las solicitudes
 * - COMPANY_ADMIN no puede ver solicitudes (403)
 * - USER no puede ver solicitudes (403)
 * - Filtros por status funcionan (PENDING, APPROVED/ACTIVE, REJECTED)
 * - Sin filtro retorna todas las solicitudes
 * - Paginación con limit funciona
 */
class CompanyRequestControllerIndexTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCompanyIndustries;

    /**
     * Helper para crear empresa pendiente con detalles de onboarding
     */
    private function createPendingCompany(array $overrides = []): Company
    {
        $company = Company::factory()->pending()->create($overrides);
        CompanyOnboardingDetails::factory()->create([
            'company_id' => $company->id,
        ]);
        return $company;
    }

    /**
     * Helper para crear empresa activa (aprobada)
     */
    private function createActiveCompany(array $overrides = []): Company
    {
        return Company::factory()->create(array_merge(['status' => 'active'], $overrides));
    }

    /**
     * Helper para crear empresa rechazada con detalles de onboarding
     */
    private function createRejectedCompany(array $overrides = []): Company
    {
        $company = Company::factory()->rejected()->create($overrides);
        CompanyOnboardingDetails::factory()->create([
            'company_id' => $company->id,
            'rejection_reason' => 'Test rejection reason',
        ]);
        return $company;
    }

    /** @test */
    public function platform_admin_can_view_all_requests()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        // Crear 3 pending y 2 active
        for ($i = 0; $i < 3; $i++) {
            $this->createPendingCompany();
        }
        for ($i = 0; $i < 2; $i++) {
            $this->createActiveCompany();
        }

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

        $this->createPendingCompany();

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

        $this->createPendingCompany();

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

        for ($i = 0; $i < 3; $i++) {
            $this->createPendingCompany();
        }
        for ($i = 0; $i < 2; $i++) {
            $this->createActiveCompany();
        }
        $this->createRejectedCompany();

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

        for ($i = 0; $i < 2; $i++) {
            $this->createPendingCompany();
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createActiveCompany();
        }

        // Act - APPROVED se mapea a status='active'
        $response = $this->getJson('/api/company-requests?status=APPROVED', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);

        $requests = $response->json('data');
        $this->assertCount(3, $requests);

        foreach ($requests as $request) {
            // La API devuelve ACTIVE como APPROVED para compatibilidad
            $this->assertContains($request['status'], ['APPROVED', 'ACTIVE']);
        }
    }

    /** @test */
    public function filter_by_status_rejected_works()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        for ($i = 0; $i < 2; $i++) {
            $this->createPendingCompany();
        }
        for ($i = 0; $i < 2; $i++) {
            $this->createRejectedCompany();
        }

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

        for ($i = 0; $i < 2; $i++) {
            $this->createPendingCompany();
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createActiveCompany();
        }
        $this->createRejectedCompany();

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
        $this->assertTrue(
            in_array('APPROVED', $statuses) || in_array('ACTIVE', $statuses),
            'Expected APPROVED or ACTIVE status'
        );
        $this->assertContains('REJECTED', $statuses);
    }

    /** @test */
    public function pagination_with_limit_works()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        for ($i = 0; $i < 20; $i++) {
            $this->createPendingCompany();
        }

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

        $company = Company::factory()->pending()->create([
            'name' => 'Test Company',
        ]);
        CompanyOnboardingDetails::factory()->create([
            'company_id' => $company->id,
            'submitter_email' => 'admin@test.com',
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
        $this->createPendingCompany();

        // Act - Sin autenticación
        $response = $this->getJson('/api/company-requests');

        // Assert
        $response->assertStatus(401)
            ->assertJsonStructure(['message', 'code', 'category'])
            ->assertJson(['code' => 'UNAUTHENTICATED']);
    }
}
