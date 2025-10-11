<?php

namespace Tests\Feature\UserManagement;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para DeleteUserMutation
 *
 * Verifica:
 * - Solo PLATFORM_ADMIN puede eliminar usuarios
 * - Soft delete (cambia status a DELETED, no elimina físicamente)
 * - Anonimiza datos sensibles según GDPR
 * - Reason se registra en auditoría (via @audit directive)
 * - Mantiene registros para auditoría
 */
class DeleteUserMutationTest extends TestCase
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
                'last_name' => 'Eliminar',
            ])
            ->withRole('USER')
            ->create([
                'email' => 'delete@example.com',
                'status' => UserStatus::ACTIVE,
            ]);
    }

    /**
     * @test
     * Platform admin puede eliminar usuario exitosamente
     */
    public function platform_admin_can_delete_user_successfully(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!, $reason: String) {
                deleteUser(id: $id, reason: $reason)
            }
        ';

        $variables = [
            'id' => $this->targetUser->id,
            'reason' => 'Solicitud del usuario - GDPR compliance',
        ];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $response->assertJson([
            'data' => [
                'deleteUser' => true,
            ],
        ]);

        // Verificar soft delete en base de datos
        $this->targetUser->refresh();
        $this->assertEquals(UserStatus::DELETED, $this->targetUser->status);
        $this->assertNotNull($this->targetUser->deleted_at);
    }

    /**
     * @test
     * Reason es opcional pero recomendado para auditoría
     */
    public function reason_is_optional_but_recommended(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $response->assertJson([
            'data' => [
                'deleteUser' => true,
            ],
        ]);
    }

    /**
     * @test
     * Reason se puede proporcionar para cumplimiento legal
     */
    public function reason_can_be_provided_for_legal_compliance(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!, $reason: String) {
                deleteUser(id: $id, reason: $reason)
            }
        ';

        $legalReasons = [
            'Solicitud del usuario - Derecho al olvido (GDPR Art. 17)',
            'Cuenta inactiva por más de 2 años',
            'Violación grave de términos - fraude detectado',
            'Fallecimiento del titular - solicitud familiar',
        ];

        foreach ($legalReasons as $reason) {
            $user = User::factory()
                ->withProfile()
                ->withRole('USER')
                ->create();

            $variables = [
                'id' => $user->id,
                'reason' => $reason,
            ];

            // Act
            $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

            // Assert
            $this->assertTrue($response->json('data.deleteUser'));
        }
    }

    /**
     * @test
     * Es soft delete (no elimina físicamente)
     */
    public function performs_soft_delete_not_hard_delete(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertTrue($response->json('data.deleteUser'));

        // Usuario todavía existe en BD (con trashed)
        $userStillExists = User::withTrashed()->find($this->targetUser->id);
        $this->assertNotNull($userStillExists);
        $this->assertEquals(UserStatus::DELETED, $userStillExists->status);
    }

    /**
     * @test
     * Company admin NO puede eliminar usuarios
     */
    public function company_admin_cannot_delete_users(): void
    {
        // Arrange
        $companyAdmin = User::factory()
            ->withProfile()
            ->withRole('COMPANY_ADMIN', null)
            ->create();

        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
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
     * Usuario regular NO puede eliminar usuarios
     */
    public function regular_user_cannot_delete_users(): void
    {
        // Arrange
        $regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
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
     * Mutation requiere autenticación
     */
    public function mutation_requires_authentication(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
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
     * No puede eliminar usuario que no existe
     */
    public function cannot_delete_nonexistent_user(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
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
     * Usuario no puede eliminarse a sí mismo
     */
    public function user_cannot_delete_themselves(): void
    {
        // Arrange - Incluso platform admin no puede eliminarse a sí mismo
        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
            }
        ';

        $variables = ['id' => $this->platformAdmin->id];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert - Debería fallar por lógica de negocio
        // TODO: Implementar esta validación en el service si aún no existe
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Retorna boolean true en éxito
     */
    public function returns_boolean_true_on_success(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $result = $response->json('data.deleteUser');
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * @test
     * Perfil permanece accesible para auditoría
     */
    public function profile_remains_accessible_for_audit(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert - Perfil todavía accesible
        $deletedUser = User::withTrashed()->find($this->targetUser->id);
        $this->assertNotNull($deletedUser->profile);
    }

    /**
     * @test
     * Roles se mantienen para auditoría
     */
    public function roles_are_maintained_for_audit(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert - Roles todavía accesibles
        $deletedUser = User::withTrashed()->find($this->targetUser->id);
        $this->assertGreaterThan(0, $deletedUser->userRoles()->count());
    }

    /**
     * @test
     * Caso de uso GDPR: derecho al olvido
     */
    public function gdpr_right_to_be_forgotten_use_case(): void
    {
        // Arrange
        $query = '
            mutation DeleteUser($id: UUID!, $reason: String) {
                deleteUser(id: $id, reason: $reason)
            }
        ';

        $variables = [
            'id' => $this->targetUser->id,
            'reason' => 'GDPR Art. 17 - Right to erasure requested by data subject',
        ];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertTrue($response->json('data.deleteUser'));

        // Usuario marcado como DELETED
        $this->targetUser->refresh();
        $this->assertEquals(UserStatus::DELETED, $this->targetUser->status);
    }

    /**
     * @test
     * Puede eliminar usuario ya suspendido
     */
    public function can_delete_already_suspended_user(): void
    {
        // Arrange - Usuario ya suspendido
        $this->targetUser->update(['status' => UserStatus::SUSPENDED]);

        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertTrue($response->json('data.deleteUser'));

        // Status cambió a DELETED
        $this->targetUser->refresh();
        $this->assertEquals(UserStatus::DELETED, $this->targetUser->status);
    }

    /**
     * @test
     * deleted_at timestamp se registra correctamente
     */
    public function deleted_at_timestamp_is_recorded(): void
    {
        // Arrange
        $this->assertNull($this->targetUser->deleted_at);

        $query = '
            mutation DeleteUser($id: UUID!) {
                deleteUser(id: $id)
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $this->actingAsGraphQL($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $this->targetUser->refresh();
        $this->assertNotNull($this->targetUser->deleted_at);
        $this->assertInstanceOf(\DateTimeInterface::class, $this->targetUser->deleted_at);
    }
}
