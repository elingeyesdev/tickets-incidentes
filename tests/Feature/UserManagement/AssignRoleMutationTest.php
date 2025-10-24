<?php

namespace Tests\Feature\UserManagement;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para AssignRoleMutation V10.1
 *
 * Verifica:
 * - Lógica inteligente: crea nuevo O reactiva rol inactivo
 * - Validación de roles que requieren empresa
 * - Validación de permisos (solo PLATFORM_ADMIN o COMPANY_ADMIN)
 * - Retorna UserRoleResult con mensaje descriptivo
 */
class AssignRoleMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $targetUser;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin con rol PLATFORM_ADMIN
        $this->adminUser = User::factory()
            ->withProfile()
            ->withRole('PLATFORM_ADMIN')
            ->create();

        // Usuario objetivo
        $this->targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        // Empresa para roles que la requieren
        $this->company = Company::factory()->create();
    }

    /**
     * @test
     * Platform admin puede asignar rol exitosamente
     */
    public function platform_admin_can_assign_role_successfully(): void
    {
        // Arrange
        $query = '
            mutation AssignRole($input: AssignRoleInput!) {
                assignRole(input: $input) {
                    success
                    message
                    role {
                        id
                        roleCode
                        roleName
                        company {
                            id
                            name
                        }
                        isActive
                        assignedAt
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'userId' => $this->targetUser->id,
                'roleCode' => 'AGENT',
                'companyId' => $this->company->id,
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($this->adminUser)->graphQL($query, $variables);

        // Assert
        $response->assertJson([
            'data' => [
                'assignRole' => [
                    'success' => true,
                    'message' => 'Rol AGENT asignado exitosamente',
                ],
            ],
        ]);

        $result = $response->json('data.assignRole');
        $this->assertTrue($result['success']);
        $this->assertEquals('AGENT', $result['role']['roleCode']);
        $this->assertTrue($result['role']['isActive']);
        $this->assertEquals($this->company->id, $result['role']['company']['id']);

        // Verificar en base de datos
        $this->assertTrue(
            $this->targetUser->userRoles()
                ->where('role_code', 'AGENT')
                ->where('company_id', $this->company->id)
                ->where('is_active', true)
                ->exists()
        );
    }

    /**
     * @test
     * AGENT requiere empresa asociada
     */
    public function agent_role_requires_company(): void
    {
        // Arrange
        $query = '
            mutation AssignRole($input: AssignRoleInput!) {
                assignRole(input: $input) {
                    success
                }
            }
        ';

        $variables = [
            'input' => [
                'userId' => $this->targetUser->id,
                'roleCode' => 'AGENT',
                // Sin companyId
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($this->adminUser)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
        $errors = $response->json('errors');
        $this->assertStringContainsString('requires company context', $errors[0]['message']);
    }

    /**
     * @test
     * USER no debe tener empresa asociada
     */
    public function user_role_should_not_have_company(): void
    {
        // Arrange
        $query = '
            mutation AssignRole($input: AssignRoleInput!) {
                assignRole(input: $input) {
                    success
                }
            }
        ';

        $variables = [
            'input' => [
                'userId' => $this->targetUser->id,
                'roleCode' => 'USER',
                'companyId' => $this->company->id, // No debe tener empresa
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($this->adminUser)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
        $errors = $response->json('errors');
        $this->assertStringContainsString('cannot have company context', $errors[0]['message']);
    }

    /**
     * @test
     * Lógica inteligente: reactiva rol inactivo
     */
    public function reactivates_inactive_role_intelligently(): void
    {
        // Arrange - Crear rol inactivo
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $role = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();
        $role->update(['is_active' => false, 'revoked_at' => now()]);

        $query = '
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

        $variables = [
            'input' => [
                'userId' => $this->targetUser->id,
                'roleCode' => 'AGENT',
                'companyId' => $this->company->id,
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($this->adminUser)->graphQL($query, $variables);

        // Assert
        $response->assertJson([
            'data' => [
                'assignRole' => [
                    'success' => true,
                    'message' => 'Rol AGENT reactivado exitosamente',
                ],
            ],
        ]);

        $result = $response->json('data.assignRole');
        $this->assertTrue($result['role']['isActive']);

        // Verificar en base de datos
        $role->refresh();
        $this->assertTrue($role->is_active);
        $this->assertNull($role->revoked_at);
    }

    /**
     * @test
     * No puede asignar rol ya activo
     */
    public function cannot_assign_already_active_role(): void
    {
        // Arrange - Ya tiene el rol AGENT activo
        $this->targetUser->assignRole('AGENT', $this->company->id);

        $query = '
            mutation AssignRole($input: AssignRoleInput!) {
                assignRole(input: $input) {
                    success
                }
            }
        ';

        $variables = [
            'input' => [
                'userId' => $this->targetUser->id,
                'roleCode' => 'AGENT',
                'companyId' => $this->company->id,
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($this->adminUser)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
        $errors = $response->json('errors');
        $this->assertStringContainsString('ya tiene este rol asignado', $errors[0]['message']);
    }

    /**
     * @test
     * Mutation requiere ser PLATFORM_ADMIN o COMPANY_ADMIN
     */
    public function mutation_requires_admin_role(): void
    {
        // Arrange - Usuario sin permisos de admin
        $regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $query = '
            mutation AssignRole($input: AssignRoleInput!) {
                assignRole(input: $input) {
                    success
                }
            }
        ';

        $variables = [
            'input' => [
                'userId' => $this->targetUser->id,
                'roleCode' => 'AGENT',
                'companyId' => $this->company->id,
            ],
        ];

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
        $query = '
            mutation AssignRole($input: AssignRoleInput!) {
                assignRole(input: $input) {
                    success
                }
            }
        ';

        $variables = [
            'input' => [
                'userId' => $this->targetUser->id,
                'roleCode' => 'AGENT',
                'companyId' => $this->company->id,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Company admin puede asignar roles en su empresa
     */
    public function company_admin_can_assign_roles_in_their_company(): void
    {
        // Arrange - Usuario con rol COMPANY_ADMIN
        $companyAdmin = User::factory()
            ->withProfile()
            ->withRole('COMPANY_ADMIN', $this->company->id)
            ->create();

        $query = '
            mutation AssignRole($input: AssignRoleInput!) {
                assignRole(input: $input) {
                    success
                    role {
                        roleCode
                        company {
                            id
                        }
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'userId' => $this->targetUser->id,
                'roleCode' => 'AGENT',
                'companyId' => $this->company->id,
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)->graphQL($query, $variables);

        // Assert
        $response->assertJson([
            'data' => [
                'assignRole' => [
                    'success' => true,
                ],
            ],
        ]);

        $result = $response->json('data.assignRole');
        $this->assertEquals('AGENT', $result['role']['roleCode']);
        $this->assertEquals($this->company->id, $result['role']['company']['id']);
    }
}
