<?php

namespace Tests\Feature\UserManagement;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para RemoveRoleMutation V10.1
 *
 * Verifica:
 * - Soft delete de roles (reversible con assignRole)
 * - Solo PLATFORM_ADMIN o COMPANY_ADMIN pueden remover roles
 * - Establece isActive = false, registra revokedAt y reason
 * - Reason se registra en auditoría (via @audit directive)
 * - Puede reactivarse usando assignRole (lógica inteligente)
 */
class RemoveRoleMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;
    private User $companyAdmin;
    private User $targetUser;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        // Platform admin
        $this->platformAdmin = User::factory()
            ->withProfile()
            ->withRole('PLATFORM_ADMIN')
            ->create();

        // Company admin
        $this->companyAdmin = User::factory()
            ->withProfile()
            ->withRole('COMPANY_ADMIN', $this->company->id)
            ->create();

        // Usuario objetivo con rol AGENT
        $this->targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->withRole('AGENT', $this->company->id)
            ->create();
    }

    /**
     * @test
     * Platform admin puede remover rol exitosamente
     */
    public function platform_admin_can_remove_role_successfully(): void
    {
        // Arrange - Obtener roleId del rol AGENT
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $query = '
            mutation RemoveRole($roleId: UUID!, $reason: String) {
                removeRole(roleId: $roleId, reason: $reason)
            }
        ';

        $variables = [
            'roleId' => $agentRole->id,
            'reason' => 'Usuario dejó de trabajar en la empresa',
        ];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $response->assertJson([
            'data' => [
                'removeRole' => true,
            ],
        ]);

        // Verificar en base de datos (soft delete)
        $agentRole->refresh();
        $this->assertFalse($agentRole->is_active);
        $this->assertNotNull($agentRole->revoked_at);
    }

    /**
     * @test
     * Es soft delete (reversible, no elimina físicamente)
     */
    public function performs_soft_delete_not_hard_delete(): void
    {
        // Arrange
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $variables = ['roleId' => $agentRole->id];

        // Act
        $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert - Rol todavía existe en BD
        $roleStillExists = \App\Features\UserManagement\Models\UserRole::find($agentRole->id);
        $this->assertNotNull($roleStillExists);
        $this->assertFalse($roleStillExists->is_active);
    }

    /**
     * @test
     * Reason es opcional pero recomendado
     */
    public function reason_is_optional_but_recommended(): void
    {
        // Arrange
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $variables = ['roleId' => $agentRole->id];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertTrue($response->json('data.removeRole'));
    }

    /**
     * @test
     * Reason se registra para auditoría
     */
    public function reason_is_recorded_for_audit(): void
    {
        // Arrange
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $query = '
            mutation RemoveRole($roleId: UUID!, $reason: String) {
                removeRole(roleId: $roleId, reason: $reason)
            }
        ';

        $reason = 'Cambio de departamento - ya no trabaja en soporte';

        $variables = [
            'roleId' => $agentRole->id,
            'reason' => $reason,
        ];

        // Act
        $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $agentRole->refresh();
        $this->assertEquals($reason, $agentRole->revocation_reason);
    }

    /**
     * @test
     * Company admin puede remover roles en su empresa
     */
    public function company_admin_can_remove_roles_in_their_company(): void
    {
        // Arrange
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->where('company_id', $this->company->id)
            ->first();

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $variables = ['roleId' => $agentRole->id];

        // Act
        $response = $this->authenticateWithJWT($this->companyAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertTrue($response->json('data.removeRole'));

        $agentRole->refresh();
        $this->assertFalse($agentRole->is_active);
    }

    /**
     * @test
     * Company admin NO puede remover roles de otras empresas
     */
    public function company_admin_cannot_remove_roles_from_other_companies(): void
    {
        // Arrange - Otra empresa
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()
            ->withProfile()
            ->withRole('AGENT', $otherCompany->id)
            ->create();

        $otherRole = $otherUser->userRoles()->first();

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $variables = ['roleId' => $otherRole->id];

        // Act
        $response = $this->authenticateWithJWT($this->companyAdmin)->graphQL($query, $variables);

        // Assert - Debería fallar por permisos
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Usuario regular NO puede remover roles
     */
    public function regular_user_cannot_remove_roles(): void
    {
        // Arrange
        $regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $variables = ['roleId' => $agentRole->id];

        // Act
        $response = $this->authenticateWithJWT($regularUser)->graphQL($query, $variables);

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
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $variables = ['roleId' => $agentRole->id];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * No puede remover rol que no existe
     */
    public function cannot_remove_nonexistent_role(): void
    {
        // Arrange
        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $fakeId = '550e8400-e29b-41d4-a716-446655440000';
        $variables = ['roleId' => $fakeId];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * revokedAt timestamp se registra
     */
    public function revoked_at_timestamp_is_recorded(): void
    {
        // Arrange
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $this->assertNull($agentRole->revoked_at);

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $variables = ['roleId' => $agentRole->id];

        // Act
        $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $agentRole->refresh();
        $this->assertNotNull($agentRole->revoked_at);
        $this->assertInstanceOf(\DateTimeInterface::class, $agentRole->revoked_at);
    }

    /**
     * @test
     * Rol removido puede reactivarse con assignRole
     */
    public function removed_role_can_be_reactivated_with_assign_role(): void
    {
        // Arrange - Remover rol
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $removeQuery = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $this->authenticateWithJWT($this->platformAdmin)
            ->graphQL($removeQuery, ['roleId' => $agentRole->id]);

        // Assert - Rol inactivo
        $agentRole->refresh();
        $this->assertFalse($agentRole->is_active);

        // Act - Reactivar con assignRole (lógica inteligente)
        $assignQuery = '
            mutation AssignRole($input: AssignRoleInput!) {
                assignRole(input: $input) {
                    success
                    message
                    role {
                        isActive
                    }
                }
            }
        ';

        $assignVariables = [
            'input' => [
                'userId' => $this->targetUser->id,
                'roleCode' => 'AGENT',
                'companyId' => $this->company->id,
            ],
        ];

        $assignResponse = $this->authenticateWithJWT($this->platformAdmin)
            ->graphQL($assignQuery, $assignVariables);

        // Assert - Rol reactivado
        $result = $assignResponse->json('data.assignRole');
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('reactivado', strtolower($result['message']));
        $this->assertTrue($result['role']['isActive']);
    }

    /**
     * @test
     * Usuario con rol removido pierde acceso
     */
    public function user_with_removed_role_loses_access(): void
    {
        // Arrange - Remover rol AGENT
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $variables = ['roleId' => $agentRole->id];

        // Act
        $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert - Solo tiene rol USER activo
        $activeRoles = $this->targetUser->userRoles()
            ->where('is_active', true)
            ->get();

        $this->assertCount(1, $activeRoles);
        $this->assertEquals('USER', $activeRoles->first()->role_code);
    }

    /**
     * @test
     * Puede remover múltiples roles del mismo usuario
     */
    public function can_remove_multiple_roles_from_same_user(): void
    {
        // Arrange - Usuario con múltiples roles
        $this->targetUser->assignRole('PLATFORM_ADMIN');

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        // Act - Remover rol AGENT
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $response1 = $this->authenticateWithJWT($this->platformAdmin)
            ->graphQL($query, ['roleId' => $agentRole->id]);

        $this->assertTrue($response1->json('data.removeRole'));

        // Act - Remover rol PLATFORM_ADMIN
        $adminRole = $this->targetUser->userRoles()
            ->where('role_code', 'PLATFORM_ADMIN')
            ->first();

        $response2 = $this->authenticateWithJWT($this->platformAdmin)
            ->graphQL($query, ['roleId' => $adminRole->id]);

        // Assert
        $this->assertTrue($response2->json('data.removeRole'));

        // Solo USER activo
        $activeRoles = $this->targetUser->userRoles()
            ->where('is_active', true)
            ->pluck('role_code')
            ->toArray();

        $this->assertEquals(['USER'], $activeRoles);
    }

    /**
     * @test
     * Casos de uso comunes con reasons descriptivos
     */
    public function common_use_cases_with_descriptive_reasons(): void
    {
        // Arrange
        $query = '
            mutation RemoveRole($roleId: UUID!, $reason: String) {
                removeRole(roleId: $roleId, reason: $reason)
            }
        ';

        $useCases = [
            'Finalización de contrato laboral',
            'Cambio de área - transferido a ventas',
            'Licencia extendida - más de 6 meses',
            'Renuncia presentada y aceptada',
            'Desempeño insuficiente - evaluación trimestral',
        ];

        foreach ($useCases as $reason) {
            $user = User::factory()
                ->withProfile()
                ->withRole('AGENT', $this->company->id)
                ->create();

            $role = $user->userRoles()->first();

            $variables = [
                'roleId' => $role->id,
                'reason' => $reason,
            ];

            // Act
            $response = $this->authenticateWithJWT($this->platformAdmin)
                ->graphQL($query, $variables);

            // Assert
            $this->assertTrue($response->json('data.removeRole'));

            $role->refresh();
            $this->assertEquals($reason, $role->revocation_reason);
        }
    }

    /**
     * @test
     * Retorna boolean true en éxito
     */
    public function returns_boolean_true_on_success(): void
    {
        // Arrange
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        $query = '
            mutation RemoveRole($roleId: UUID!) {
                removeRole(roleId: $roleId)
            }
        ';

        $variables = ['roleId' => $agentRole->id];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $result = $response->json('data.removeRole');
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }
}
