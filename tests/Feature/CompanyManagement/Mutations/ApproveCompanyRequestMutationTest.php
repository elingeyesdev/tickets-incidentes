<?php

namespace Tests\Feature\CompanyManagement\Mutations;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para approveCompanyRequest mutation
 *
 * Verifica:
 * - PLATFORM_ADMIN puede aprobar solicitud
 * - Crea empresa correctamente
 * - Crea usuario admin si no existe
 * - Asigna rol COMPANY_ADMIN al usuario
 * - Marca solicitud como APPROVED
 * - Request inexistente lanza error (REQUEST_NOT_FOUND)
 * - Request no PENDING lanza error (REQUEST_NOT_PENDING)
 * - Permisos: Solo PLATFORM_ADMIN
 * - Retorna Company creada con todos los campos
 */
class ApproveCompanyRequestMutationTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function platform_admin_can_approve_request()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Test Company',
            'admin_email' => 'newadmin@testcompany.com',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    companyCode
                    name
                    status
                    adminId
                    adminName
                    adminEmail
                    createdAt
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'approveCompanyRequest' => [
                    'id',
                    'companyCode',
                    'name',
                    'status',
                    'adminId',
                    'adminName',
                    'adminEmail',
                    'createdAt',
                ],
            ],
        ]);

        $company = $response->json('data.approveCompanyRequest');
        $this->assertEquals('Test Company', $company['name']);
        $this->assertEquals('ACTIVE', $company['status']);
        $this->assertNotEmpty($company['companyCode']);
    }

    /** @test */
    public function creates_company_correctly()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'New Company',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    name
                    companyCode
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $companyId = $response->json('data.approveCompanyRequest.id');

        $this->assertDatabaseHas('business.companies', [
            'id' => $companyId,
            'name' => 'New Company',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function creates_admin_user_if_not_exists()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'newuser@example.com',
        ]);

        // Verificar que el usuario no existe
        $this->assertDatabaseMissing('auth.users', [
            'email' => 'newuser@example.com',
        ]);

        // Act
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    adminEmail
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert - Usuario fue creado
        $this->assertDatabaseHas('auth.users', [
            'email' => 'newuser@example.com',
        ]);
    }

    /** @test */
    public function assigns_company_admin_role_to_user()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'companyadmin@example.com',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    adminId
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $adminUserId = $response->json('data.approveCompanyRequest.adminId');
        $companyId = $response->json('data.approveCompanyRequest.id');

        $this->assertDatabaseHas('auth.user_roles', [
            'user_id' => $adminUserId,
            'role_code' => 'COMPANY_ADMIN',
            'company_id' => $companyId,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function marks_request_as_approved()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $this->assertDatabaseHas('business.company_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by_id' => $admin->id,
        ]);

        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertNotNull($request->reviewed_at);
    }

    /** @test */
    public function nonexistent_request_throws_request_not_found_error()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $fakeId
        ]);

        // Assert
        $response->assertGraphQLError([
            'message' => 'Request not found',
        ]);

        $errors = $response->json('errors');
        $this->assertEquals('REQUEST_NOT_FOUND', $errors[0]['extensions']['code']);
    }

    /** @test */
    public function non_pending_request_throws_request_not_pending_error()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'approved']);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertGraphQLError([
            'message' => 'Only pending requests can be approved',
        ]);

        $errors = $response->json('errors');
        $this->assertEquals('REQUEST_NOT_PENDING', $errors[0]['extensions']['code']);
    }

    /** @test */
    public function company_admin_cannot_approve()
    {
        // Arrange
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function user_cannot_approve()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function returns_created_company_with_all_fields()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    companyCode
                    name
                    legalName
                    status
                    supportEmail
                    adminId
                    adminName
                    adminEmail
                    createdAt
                    updatedAt
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'approveCompanyRequest' => [
                    'id',
                    'companyCode',
                    'name',
                    'status',
                    'adminId',
                    'adminName',
                    'adminEmail',
                    'createdAt',
                ],
            ],
        ]);

        $company = $response->json('data.approveCompanyRequest');
        $this->assertNotEmpty($company['id']);
        $this->assertNotEmpty($company['companyCode']);
        $this->assertEquals('ACTIVE', $company['status']);
    }

    /** @test */
    public function uses_existing_user_if_email_already_exists()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'existing@example.com',
        ]);

        $initialUserCount = User::count();

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    adminId
                    adminEmail
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert - No se creó nuevo usuario
        $this->assertEquals($initialUserCount, User::count());

        // Se usó el usuario existente
        $this->assertEquals($existingUser->id, $response->json('data.approveCompanyRequest.adminId'));
    }
}
