<?php

namespace Tests\Feature\CompanyManagement\Mutations;

use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para rejectCompanyRequest mutation
 *
 * Verifica:
 * - PLATFORM_ADMIN puede rechazar solicitud
 * - Marca solicitud como REJECTED
 * - Guarda razón de rechazo
 * - Retorna true
 * - Request inexistente lanza error
 * - Request no PENDING lanza error
 * - COMPANY_ADMIN no puede rechazar (permisos)
 * - USER no puede rechazar (permisos)
 * - Razón es obligatoria (validación)
 */
class RejectCompanyRequestMutationTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function platform_admin_can_reject_request()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);
        $reason = 'Insufficient business information provided';

        // Act
        $response = $this->actingAs($admin)->graphQL('
            mutation RejectRequest($requestId: UUID!, $reason: String!) {
                rejectCompanyRequest(requestId: $requestId, reason: $reason)
            }
        ', [
            'requestId' => $request->id,
            'reason' => $reason
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'rejectCompanyRequest' => true,
            ],
        ]);
    }

    /** @test */
    public function marks_request_as_rejected()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);
        $reason = 'Business description is too vague';

        // Act
        $this->actingAs($admin)->graphQL('
            mutation RejectRequest($requestId: UUID!, $reason: String!) {
                rejectCompanyRequest(requestId: $requestId, reason: $reason)
            }
        ', [
            'requestId' => $request->id,
            'reason' => $reason
        ]);

        // Assert
        $this->assertDatabaseHas('business.company_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reviewed_by_id' => $admin->id,
        ]);

        $request->refresh();
        $this->assertEquals('rejected', $request->status);
        $this->assertNotNull($request->reviewed_at);
    }

    /** @test */
    public function saves_rejection_reason()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);
        $reason = 'The company does not meet our minimum requirements for business verification';

        // Act
        $this->actingAs($admin)->graphQL('
            mutation RejectRequest($requestId: UUID!, $reason: String!) {
                rejectCompanyRequest(requestId: $requestId, reason: $reason)
            }
        ', [
            'requestId' => $request->id,
            'reason' => $reason
        ]);

        // Assert
        $this->assertDatabaseHas('business.company_requests', [
            'id' => $request->id,
            'rejection_reason' => $reason,
        ]);

        $request->refresh();
        $this->assertEquals($reason, $request->rejection_reason);
    }

    /** @test */
    public function returns_true_on_success()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->actingAs($admin)->graphQL('
            mutation RejectRequest($requestId: UUID!, $reason: String!) {
                rejectCompanyRequest(requestId: $requestId, reason: $reason)
            }
        ', [
            'requestId' => $request->id,
            'reason' => 'Test reason'
        ]);

        // Assert
        $this->assertTrue($response->json('data.rejectCompanyRequest'));
    }

    /** @test */
    public function nonexistent_request_throws_error()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        // Act
        $response = $this->actingAs($admin)->graphQL('
            mutation RejectRequest($requestId: UUID!, $reason: String!) {
                rejectCompanyRequest(requestId: $requestId, reason: $reason)
            }
        ', [
            'requestId' => $fakeId,
            'reason' => 'Test reason'
        ]);

        // Assert
        $response->assertGraphQLError([
            'message' => 'Request not found',
        ]);
    }

    /** @test */
    public function non_pending_request_throws_error()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'approved']);

        // Act
        $response = $this->actingAs($admin)->graphQL('
            mutation RejectRequest($requestId: UUID!, $reason: String!) {
                rejectCompanyRequest(requestId: $requestId, reason: $reason)
            }
        ', [
            'requestId' => $request->id,
            'reason' => 'Test reason'
        ]);

        // Assert
        $response->assertGraphQLError([
            'message' => 'Only pending requests can be rejected',
        ]);
    }

    /** @test */
    public function company_admin_cannot_reject()
    {
        // Arrange
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->actingAs($companyAdmin)->graphQL('
            mutation RejectRequest($requestId: UUID!, $reason: String!) {
                rejectCompanyRequest(requestId: $requestId, reason: $reason)
            }
        ', [
            'requestId' => $request->id,
            'reason' => 'Test reason'
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function user_cannot_reject()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->actingAs($user)->graphQL('
            mutation RejectRequest($requestId: UUID!, $reason: String!) {
                rejectCompanyRequest(requestId: $requestId, reason: $reason)
            }
        ', [
            'requestId' => $request->id,
            'reason' => 'Test reason'
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function reason_is_required()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act - Sin razón (GraphQL schema lo requiere)
        $response = $this->actingAs($admin)->graphQL('
            mutation RejectRequest($requestId: UUID!) {
                rejectCompanyRequest(requestId: $requestId, reason: "")
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert - Error de validación
        $this->assertNotEmpty($response->json('errors'));
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->graphQL('
            mutation RejectRequest($requestId: UUID!, $reason: String!) {
                rejectCompanyRequest(requestId: $requestId, reason: $reason)
            }
        ', [
            'requestId' => $request->id,
            'reason' => 'Test reason'
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }
}
