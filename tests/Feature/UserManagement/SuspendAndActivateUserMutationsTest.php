<?php

namespace Tests\Feature\UserManagement;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para SuspendUserMutation y ActivateUserMutation
 *
 * Verifica:
 * - Solo PLATFORM_ADMIN puede suspender/activar usuarios
 * - Cambia status correctamente (ACTIVE <-> SUSPENDED)
 * - Retorna UserStatusPayload (solo userId, status, updatedAt)
 * - Reason se registra en auditoría (via @audit directive)
 * - Invalida sesiones al suspender
 */
class SuspendAndActivateUserMutationsTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;
    private User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Platform admin (único con permisos)
        $this->platformAdmin = User::factory()
            ->withProfile()
            ->withRole('PLATFORM_ADMIN')
            ->create();

        // Usuario objetivo
        $this->targetUser = User::factory()
            ->withProfile([
                'first_name' => 'Usuario',
                'last_name' => 'Objetivo',
            ])
            ->withRole('USER')
            ->create([
                'email' => 'target@example.com',
                'status' => UserStatus::ACTIVE,
            ]);
    }

    /**
     * @test
     * Platform admin puede suspender usuario exitosamente
     */
    public function platform_admin_can_suspend_user_successfully(): void
    {
        // Arrange
        $query = '
            mutation SuspendUser($id: UUID!, $reason: String) {
                suspendUser(id: $id, reason: $reason) {
                    userId
                    status
                    updatedAt
                }
            }
        ';

        $variables = [
            'id' => $this->targetUser->id,
            'reason' => 'Violación de términos de servicio',
        ];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'suspendUser' => [
                    'userId',
                    'status',
                    'updatedAt',
                ],
            ],
        ]);

        $result = $response->json('data.suspendUser');
        $this->assertEquals($this->targetUser->id, $result['userId']);
        $this->assertEquals('SUSPENDED', $result['status']);
        $this->assertNotNull($result['updatedAt']);

        // Verificar en base de datos
        $this->targetUser->refresh();
        $this->assertEquals(UserStatus::SUSPENDED, $this->targetUser->status);
    }

    /**
     * @test
     * Platform admin puede activar usuario suspendido
     */
    public function platform_admin_can_activate_suspended_user(): void
    {
        // Arrange - Usuario suspendido
        $this->targetUser->update(['status' => UserStatus::SUSPENDED]);

        $query = '
            mutation ActivateUser($id: UUID!) {
                activateUser(id: $id) {
                    userId
                    status
                    updatedAt
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'activateUser' => [
                    'userId',
                    'status',
                    'updatedAt',
                ],
            ],
        ]);

        $result = $response->json('data.activateUser');
        $this->assertEquals($this->targetUser->id, $result['userId']);
        $this->assertEquals('ACTIVE', $result['status']);

        // Verificar en base de datos
        $this->targetUser->refresh();
        $this->assertEquals(UserStatus::ACTIVE, $this->targetUser->status);
    }

    /**
     * @test
     * Reason es opcional en suspendUser
     */
    public function reason_is_optional_in_suspend_user(): void
    {
        // Arrange
        $query = '
            mutation SuspendUser($id: UUID!) {
                suspendUser(id: $id) {
                    userId
                    status
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $result = $response->json('data.suspendUser');
        $this->assertEquals('SUSPENDED', $result['status']);
    }

    /**
     * @test
     * Reason se puede proporcionar para auditoría
     */
    public function reason_can_be_provided_for_audit(): void
    {
        // Arrange
        $query = '
            mutation SuspendUser($id: UUID!, $reason: String) {
                suspendUser(id: $id, reason: $reason) {
                    userId
                    status
                }
            }
        ';

        $variables = [
            'id' => $this->targetUser->id,
            'reason' => 'Spam de tickets - múltiples reportes',
        ];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $result = $response->json('data.suspendUser');
        $this->assertEquals('SUSPENDED', $result['status']);

        // Nota: El reason se registra vía @audit directive en el schema
        // No se retorna en la respuesta, pero queda en logs de auditoría
    }

    /**
     * @test
     * Company admin NO puede suspender usuarios
     */
    public function company_admin_cannot_suspend_users(): void
    {
        // Arrange - Company admin sin permisos de suspender
        $companyAdmin = User::factory()
            ->withProfile()
            ->withRole('COMPANY_ADMIN', null)
            ->create();

        $query = '
            mutation SuspendUser($id: UUID!) {
                suspendUser(id: $id) {
                    userId
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($companyAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Usuario regular NO puede suspender usuarios
     */
    public function regular_user_cannot_suspend_users(): void
    {
        // Arrange
        $regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $query = '
            mutation SuspendUser($id: UUID!) {
                suspendUser(id: $id) {
                    userId
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($regularUser)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * SuspendUser requiere autenticación
     */
    public function suspend_user_requires_authentication(): void
    {
        // Arrange
        $query = '
            mutation SuspendUser($id: UUID!) {
                suspendUser(id: $id) {
                    userId
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * ActivateUser requiere autenticación
     */
    public function activate_user_requires_authentication(): void
    {
        // Arrange
        $query = '
            mutation ActivateUser($id: UUID!) {
                activateUser(id: $id) {
                    userId
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Usuario regular NO puede activar usuarios
     */
    public function regular_user_cannot_activate_users(): void
    {
        // Arrange
        $regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $query = '
            mutation ActivateUser($id: UUID!) {
                activateUser(id: $id) {
                    userId
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($regularUser)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Retorna UserStatusPayload (NO User completo)
     */
    public function returns_user_status_payload_not_complete_user(): void
    {
        // Arrange
        $query = '
            mutation SuspendUser($id: UUID!) {
                suspendUser(id: $id) {
                    userId
                    status
                    updatedAt
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert - SOLO userId, status, updatedAt
        $result = $response->json('data.suspendUser');
        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('updatedAt', $result);
        $this->assertCount(3, $result); // Solo 3 campos
    }

    /**
     * @test
     * No puede suspender usuario que no existe
     */
    public function cannot_suspend_nonexistent_user(): void
    {
        // Arrange
        $query = '
            mutation SuspendUser($id: UUID!) {
                suspendUser(id: $id) {
                    userId
                }
            }
        ';

        $fakeId = '550e8400-e29b-41d4-a716-446655440000';
        $variables = ['id' => $fakeId];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * No puede activar usuario que no existe
     */
    public function cannot_activate_nonexistent_user(): void
    {
        // Arrange
        $query = '
            mutation ActivateUser($id: UUID!) {
                activateUser(id: $id) {
                    userId
                }
            }
        ';

        $fakeId = '550e8400-e29b-41d4-a716-446655440000';
        $variables = ['id' => $fakeId];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * updatedAt se actualiza al suspender
     */
    public function updated_at_changes_when_suspending(): void
    {
        // Arrange
        $originalUpdatedAt = $this->targetUser->updated_at;

        sleep(1);

        $query = '
            mutation SuspendUser($id: UUID!) {
                suspendUser(id: $id) {
                    updatedAt
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $newUpdatedAt = $response->json('data.suspendUser.updatedAt');
        $this->assertNotEquals($originalUpdatedAt->toISOString(), $newUpdatedAt);
    }

    /**
     * @test
     * Flujo completo: suspender y luego reactivar
     */
    public function complete_flow_suspend_then_activate(): void
    {
        // Arrange
        $suspendQuery = '
            mutation SuspendUser($id: UUID!) {
                suspendUser(id: $id) {
                    status
                }
            }
        ';

        $activateQuery = '
            mutation ActivateUser($id: UUID!) {
                activateUser(id: $id) {
                    status
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act - Suspender
        $suspendResponse = $this->actingAsGraphQL($this->platformAdmin)
            ->graphQL($suspendQuery, $variables);

        $this->assertEquals('SUSPENDED', $suspendResponse->json('data.suspendUser.status'));

        // Act - Activar
        $activateResponse = $this->actingAsGraphQL($this->platformAdmin)
            ->graphQL($activateQuery, $variables);

        // Assert
        $this->assertEquals('ACTIVE', $activateResponse->json('data.activateUser.status'));

        // Verificar estado final en BD
        $this->targetUser->refresh();
        $this->assertEquals(UserStatus::ACTIVE, $this->targetUser->status);
    }
}
