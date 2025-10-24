<?php

namespace Tests\Feature\CompanyManagement\Queries;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para companyRequests query
 *
 * Verifica:
 * - PLATFORM_ADMIN puede ver todas las solicitudes
 * - COMPANY_ADMIN no puede ver solicitudes
 * - USER no puede ver solicitudes
 * - Filtros por status funcionan (PENDING, APPROVED, REJECTED)
 * - Sin filtro retorna todas las solicitudes
 * - Paginación con limit funciona
 */
class CompanyRequestsQueryTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function platform_admin_can_view_all_requests()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        CompanyRequest::factory()->count(3)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(2)->create(['status' => 'approved']);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            query {
                companyRequests {
                    id
                    requestCode
                    companyName
                    adminEmail
                    status
                }
            }
        ');

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'companyRequests' => [
                    '*' => [
                        'id',
                        'requestCode',
                        'companyName',
                        'adminEmail',
                        'status',
                    ],
                ],
            ],
        ]);

        $requests = $response->json('data.companyRequests');
        $this->assertCount(5, $requests);
    }

    /** @test */
    public function company_admin_cannot_view_requests()
    {
        // Arrange
        $company = Company::factory()->create();
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        CompanyRequest::factory()->create();

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)->graphQL('
            query {
                companyRequests {
                    id
                }
            }
        ');

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function user_cannot_view_requests()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        CompanyRequest::factory()->create();

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            query {
                companyRequests {
                    id
                }
            }
        ');

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function filter_by_status_pending_works()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        CompanyRequest::factory()->count(3)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(2)->create(['status' => 'approved']);
        CompanyRequest::factory()->create(['status' => 'rejected']);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            query CompanyRequests($status: CompanyRequestStatus) {
                companyRequests(status: $status) {
                    id
                    status
                }
            }
        ', [
            'status' => 'PENDING'
        ]);

        // Assert
        $requests = $response->json('data.companyRequests');
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
        CompanyRequest::factory()->count(2)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(3)->create(['status' => 'approved']);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            query CompanyRequests($status: CompanyRequestStatus) {
                companyRequests(status: $status) {
                    id
                    status
                }
            }
        ', [
            'status' => 'APPROVED'
        ]);

        // Assert
        $requests = $response->json('data.companyRequests');
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
        CompanyRequest::factory()->count(2)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(2)->create(['status' => 'rejected']);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            query CompanyRequests($status: CompanyRequestStatus) {
                companyRequests(status: $status) {
                    id
                    status
                }
            }
        ', [
            'status' => 'REJECTED'
        ]);

        // Assert
        $requests = $response->json('data.companyRequests');
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
        CompanyRequest::factory()->count(2)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(3)->create(['status' => 'approved']);
        CompanyRequest::factory()->create(['status' => 'rejected']);

        // Act - Sin filtro
        $response = $this->authenticateWithJWT($admin)->graphQL('
            query {
                companyRequests {
                    id
                    status
                }
            }
        ');

        // Assert
        $requests = $response->json('data.companyRequests');
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
        CompanyRequest::factory()->count(20)->create(['status' => 'pending']);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            query CompanyRequests($first: Int) {
                companyRequests(first: $first) {
                    id
                }
            }
        ', [
            'first' => 10
        ]);

        // Assert
        $requests = $response->json('data.companyRequests');
        $this->assertCount(10, $requests);
    }

    /** @test */
    public function returns_all_fields_of_company_request()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Test Company',
            'admin_email' => 'admin@test.com',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            query {
                companyRequests {
                    id
                    requestCode
                    companyName
                    legalName
                    adminEmail
                    businessDescription
                    website
                    industryType
                    estimatedUsers
                    contactAddress
                    contactCity
                    contactCountry
                    status
                    reviewedById
                    reviewedByName
                    reviewedAt
                    rejectionReason
                    createdCompanyId
                    createdCompanyName
                    createdAt
                    updatedAt
                }
            }
        ');

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'companyRequests' => [
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
            ],
        ]);

        $request = $response->json('data.companyRequests.0');
        $this->assertEquals('Test Company', $request['companyName']);
        $this->assertEquals('admin@test.com', $request['adminEmail']);
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        CompanyRequest::factory()->create();

        // Act - Sin autenticación
        $response = $this->graphQL('
            query {
                companyRequests {
                    id
                }
            }
        ');

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }
}
