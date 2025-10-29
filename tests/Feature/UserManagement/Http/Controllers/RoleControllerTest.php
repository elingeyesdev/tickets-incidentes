<?php

namespace Tests\Feature\UserManagement\Http\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para RoleController REST API
 *
 * Migrado desde GraphQL (AvailableRolesQuery, AssignRoleMutation, RemoveRoleMutation)
 *
 * Endpoints:
 * - GET /api/roles - Lista de roles disponibles (availableRoles)
 * - POST /api/users/{userId}/roles - Asignar rol (assignRole)
 * - DELETE /api/users/roles/{roleId} - Remover rol (removeRole)
 *
 * Verifica:
 * - Lista de roles con metadata completa
 * - Lógica inteligente: crea nuevo O reactiva rol inactivo
 * - Validación de roles que requieren empresa
 * - Validación de permisos (PLATFORM_ADMIN o COMPANY_ADMIN)
 * - Soft delete reversible
 * - Rate limiting (100/hour en assign)
 */
class RoleControllerTest extends TestCase
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

        // Usuario objetivo
        $this->targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();
    }

    // ========== GET /api/roles Tests (AvailableRolesQuery) ==========

    /**
     * @test
     * Admin puede obtener lista de roles disponibles
     */
    public function admin_can_get_available_roles_list(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'code',
                        'name',
                        'description',
                        'requiresCompany',
                        'defaultDashboard',
                        'isSystemRole',
                    ],
                ],
            ]);

        $roles = $response->json('data');
        $this->assertNotEmpty($roles);
        $this->assertGreaterThanOrEqual(4, count($roles)); // Al menos los 4 roles básicos
    }

    /**
     * @test
     * Retorna los 4 roles básicos del sistema
     */
    public function returns_four_basic_system_roles(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();
        $roles = $response->json('data');
        $roleCodes = collect($roles)->pluck('code')->toArray();

        $this->assertContains('USER', $roleCodes);
        $this->assertContains('AGENT', $roleCodes);
        $this->assertContains('COMPANY_ADMIN', $roleCodes);
        $this->assertContains('PLATFORM_ADMIN', $roleCodes);
    }

    /**
     * @test
     * USER y PLATFORM_ADMIN no requieren empresa
     */
    public function user_and_platform_admin_do_not_require_company(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();
        $roles = collect($response->json('data'));

        $userRole = $roles->firstWhere('code', 'USER');
        $this->assertFalse($userRole['requiresCompany']);

        $platformAdminRole = $roles->firstWhere('code', 'PLATFORM_ADMIN');
        $this->assertFalse($platformAdminRole['requiresCompany']);
    }

    /**
     * @test
     * AGENT y COMPANY_ADMIN requieren empresa
     */
    public function agent_and_company_admin_require_company(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();
        $roles = collect($response->json('data'));

        $agentRole = $roles->firstWhere('code', 'AGENT');
        $this->assertTrue($agentRole['requiresCompany']);

        $companyAdminRole = $roles->firstWhere('code', 'COMPANY_ADMIN');
        $this->assertTrue($companyAdminRole['requiresCompany']);
    }

    /**
     * @test
     * Cada rol tiene un defaultDashboard configurado
     */
    public function each_role_has_default_dashboard(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();
        $roles = $response->json('data');

        foreach ($roles as $role) {
            $this->assertNotEmpty($role['defaultDashboard']);
            $this->assertStringStartsWith('/', $role['defaultDashboard']); // Path válido
        }
    }

    /**
     * @test
     * Roles básicos son marcados como isSystemRole
     */
    public function basic_roles_are_marked_as_system_roles(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();
        $roles = collect($response->json('data'));

        $systemRoleCodes = ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'];

        foreach ($systemRoleCodes as $roleCode) {
            $role = $roles->firstWhere('code', $roleCode);
            $this->assertTrue($role['isSystemRole'], "Role {$roleCode} should be system role");
        }
    }

    /**
     * @test
     * Cada rol tiene una descripción legible
     */
    public function each_role_has_readable_description(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();
        $roles = $response->json('data');

        foreach ($roles as $role) {
            $this->assertNotEmpty($role['name']);
            $this->assertNotEmpty($role['description']);
            $this->assertIsString($role['description']);
            $this->assertGreaterThan(10, strlen($role['description'])); // Descripción significativa
        }
    }

    /**
     * @test
     * Company admin también puede acceder a la lista
     */
    public function company_admin_can_also_access_list(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->companyAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();
        $roles = $response->json('data');
        $this->assertNotEmpty($roles);
    }

    /**
     * @test
     * Usuario regular NO puede acceder a la lista
     */
    public function regular_user_cannot_access_list(): void
    {
        // Arrange
        $regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($regularUser);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertForbidden();
    }

    /**
     * @test
     * Endpoint requiere autenticación
     */
    public function index_requires_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/roles');

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * @test
     * Lista es útil para selectores de roles en frontend
     */
    public function list_is_useful_for_role_selectors(): void
    {
        // Arrange - Simular caso de uso: llenar selector de roles
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert - Campos necesarios para selector
        $response->assertOk();
        $roles = $response->json('data');

        foreach ($roles as $role) {
            $this->assertArrayHasKey('code', $role); // value del option
            $this->assertArrayHasKey('name', $role); // label del option
            $this->assertArrayHasKey('requiresCompany', $role); // para validación condicional
        }
    }

    /**
     * @test
     * Lista es útil para validación de asignación de roles
     */
    public function list_is_useful_for_role_assignment_validation(): void
    {
        // Arrange - Simular validación: ¿Este rol requiere empresa?
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert - Puede determinar qué roles necesitan companyId
        $response->assertOk();
        $roles = collect($response->json('data'));

        $rolesRequiringCompany = $roles->where('requiresCompany', true)->pluck('code')->toArray();
        $rolesNotRequiringCompany = $roles->where('requiresCompany', false)->pluck('code')->toArray();

        $this->assertContains('AGENT', $rolesRequiringCompany);
        $this->assertContains('COMPANY_ADMIN', $rolesRequiringCompany);
        $this->assertContains('USER', $rolesNotRequiringCompany);
        $this->assertContains('PLATFORM_ADMIN', $rolesNotRequiringCompany);
    }

    /**
     * @test
     * Roles están ordenados de forma consistente
     */
    public function roles_are_consistently_ordered(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act - Llamar dos veces
        $response1 = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);
        $response2 = $this->getJson('/api/roles', [
            'Authorization' => "Bearer $token",
        ]);

        // Assert - Mismo orden
        $response1->assertOk();
        $response2->assertOk();

        $roles1 = collect($response1->json('data'))->pluck('code')->toArray();
        $roles2 = collect($response2->json('data'))->pluck('code')->toArray();

        $this->assertEquals($roles1, $roles2);
    }

    // ========== POST /api/users/{userId}/roles Tests (AssignRoleMutation) ==========

    /**
     * @test
     * Platform admin puede asignar rol exitosamente (status 201)
     */
    public function platform_admin_can_assign_role_successfully(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        $data = [
            'roleCode' => 'AGENT',
            'companyId' => $this->company->id,
        ];

        // Act
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Rol AGENT asignado exitosamente',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'roleCode',
                    'roleName',
                    'company' => ['id', 'name'],
                    'isActive',
                    'assignedAt',
                ],
            ]);

        $result = $response->json('data');
        $this->assertEquals('AGENT', $result['roleCode']);
        $this->assertTrue($result['isActive']);
        $this->assertEquals($this->company->id, $result['company']['id']);

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
        $token = $this->generateAccessToken($this->platformAdmin);

        $data = [
            'roleCode' => 'AGENT',
            // Sin companyId
        ];

        // Act
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['roleCode' => 'requires company context']);
    }

    /**
     * @test
     * USER no debe tener empresa asociada
     */
    public function user_role_should_not_have_company(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        $data = [
            'roleCode' => 'PLATFORM_ADMIN',
            'companyId' => $this->company->id, // No debe tener empresa
        ];

        // Act
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['roleCode' => 'cannot have company context']);
    }

    /**
     * @test
     * Lógica inteligente: reactiva rol inactivo (status 200)
     */
    public function reactivates_inactive_role_intelligently(): void
    {
        // Arrange - Crear rol inactivo
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $role = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();
        $role->update(['is_active' => false, 'revoked_at' => now()]);

        $token = $this->generateAccessToken($this->platformAdmin);

        $data = [
            'roleCode' => 'AGENT',
            'companyId' => $this->company->id,
        ];

        // Act
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert - Status 200 para reactivación
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Rol AGENT reactivado exitosamente',
            ]);

        $result = $response->json('data');
        $this->assertTrue($result['isActive']);

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

        $token = $this->generateAccessToken($this->platformAdmin);

        $data = [
            'roleCode' => 'AGENT',
            'companyId' => $this->company->id,
        ];

        // Act
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['roleCode' => 'ya tiene este rol asignado']);
    }

    /**
     * @test
     * Endpoint requiere ser PLATFORM_ADMIN o COMPANY_ADMIN
     */
    public function assign_requires_admin_role(): void
    {
        // Arrange - Usuario sin permisos de admin
        $regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($regularUser);

        $data = [
            'roleCode' => 'AGENT',
            'companyId' => $this->company->id,
        ];

        // Act
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertForbidden();
    }

    /**
     * @test
     * Endpoint requiere autenticación
     */
    public function assign_requires_authentication(): void
    {
        // Arrange
        $data = [
            'roleCode' => 'AGENT',
            'companyId' => $this->company->id,
        ];

        // Act
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", $data);

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * @test
     * Company admin puede asignar roles en su empresa
     */
    public function company_admin_can_assign_roles_in_their_company(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->companyAdmin);

        $data = [
            'roleCode' => 'AGENT',
            'companyId' => $this->company->id,
        ];

        // Act
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'success' => true,
            ]);

        $result = $response->json('data');
        $this->assertEquals('AGENT', $result['roleCode']);
        $this->assertEquals($this->company->id, $result['company']['id']);
    }

    /**
     * @test
     * Rate limiting: 100 requests por hora
     */
    public function assign_has_rate_limiting(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act - Hacer 101 requests
        for ($i = 0; $i < 101; $i++) {
            $user = User::factory()->withProfile()->create();

            $data = [
                'roleCode' => 'AGENT',
                'companyId' => $this->company->id,
            ];

            $response = $this->postJson("/api/users/{$user->id}/roles", $data, [
                'Authorization' => "Bearer $token",
            ]);

            if ($i < 100) {
                $response->assertCreated();
            } else {
                // Request 101 debe ser bloqueado
                $response->assertStatus(429); // Too Many Requests
            }
        }
    }

    /**
     * @test
     * Validación de campos requeridos
     */
    public function assign_validates_required_fields(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act - Sin datos
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['roleCode']);
    }

    /**
     * @test
     * Validación de roleCode inválido
     */
    public function assign_validates_invalid_role_code(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        $data = [
            'roleCode' => 'INVALID_ROLE',
        ];

        // Act
        $response = $this->postJson("/api/users/{$this->targetUser->id}/roles", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['roleCode']);
    }

    // ========== DELETE /api/users/roles/{roleId} Tests (RemoveRoleMutation) ==========

    /**
     * @test
     * Platform admin puede remover rol exitosamente
     */
    public function platform_admin_can_remove_role_successfully(): void
    {
        // Arrange - Usuario con rol AGENT
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();

        $token = $this->generateAccessToken($this->platformAdmin);

        $data = [
            'reason' => 'Usuario dejó de trabajar en la empresa',
        ];

        // Act
        $response = $this->deleteJson("/api/users/roles/{$agentRole->id}", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Rol removido exitosamente',
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
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson("/api/users/roles/{$agentRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();

        // Rol todavía existe en BD
        $roleStillExists = UserRole::find($agentRole->id);
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
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act - Sin reason
        $response = $this->deleteJson("/api/users/roles/{$agentRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /**
     * @test
     * Reason se registra para auditoría
     */
    public function reason_is_recorded_for_audit(): void
    {
        // Arrange
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();

        $token = $this->generateAccessToken($this->platformAdmin);

        $reason = 'Cambio de departamento - ya no trabaja en soporte';

        $data = ['reason' => $reason];

        // Act
        $response = $this->deleteJson("/api/users/roles/{$agentRole->id}", $data, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();

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
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()
            ->where('role_code', 'AGENT')
            ->where('company_id', $this->company->id)
            ->first();

        $token = $this->generateAccessToken($this->companyAdmin);

        // Act
        $response = $this->deleteJson("/api/users/roles/{$agentRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk()
            ->assertJson(['success' => true]);

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

        $token = $this->generateAccessToken($this->companyAdmin);

        // Act
        $response = $this->deleteJson("/api/users/roles/{$otherRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert - Debería fallar por permisos
        $response->assertForbidden();
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

        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();

        $token = $this->generateAccessToken($regularUser);

        // Act
        $response = $this->deleteJson("/api/users/roles/{$agentRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertForbidden();
    }

    /**
     * @test
     * Endpoint requiere autenticación
     */
    public function remove_requires_authentication(): void
    {
        // Arrange
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();

        // Act
        $response = $this->deleteJson("/api/users/roles/{$agentRole->id}");

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * @test
     * No puede remover rol que no existe
     */
    public function cannot_remove_nonexistent_role(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);
        $fakeId = '550e8400-e29b-41d4-a716-446655440000';

        // Act
        $response = $this->deleteJson("/api/users/roles/{$fakeId}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertNotFound();
    }

    /**
     * @test
     * revokedAt timestamp se registra
     */
    public function revoked_at_timestamp_is_recorded(): void
    {
        // Arrange
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();

        $this->assertNull($agentRole->revoked_at);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson("/api/users/roles/{$agentRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();

        $agentRole->refresh();
        $this->assertNotNull($agentRole->revoked_at);
        $this->assertInstanceOf(\DateTimeInterface::class, $agentRole->revoked_at);
    }

    /**
     * @test
     * Rol removido puede reactivarse con assign
     */
    public function removed_role_can_be_reactivated_with_assign(): void
    {
        // Arrange - Asignar y remover rol
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Remover rol
        $this->deleteJson("/api/users/roles/{$agentRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert - Rol inactivo
        $agentRole->refresh();
        $this->assertFalse($agentRole->is_active);

        // Act - Reactivar con assign (lógica inteligente)
        $assignData = [
            'roleCode' => 'AGENT',
            'companyId' => $this->company->id,
        ];

        $assignResponse = $this->postJson("/api/users/{$this->targetUser->id}/roles", $assignData, [
            'Authorization' => "Bearer $token",
        ]);

        // Assert - Rol reactivado (status 200)
        $assignResponse->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Rol AGENT reactivado exitosamente',
            ]);

        $result = $assignResponse->json('data');
        $this->assertTrue($result['isActive']);
    }

    /**
     * @test
     * Usuario con rol removido pierde acceso
     */
    public function user_with_removed_role_loses_access(): void
    {
        // Arrange - Remover rol AGENT
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson("/api/users/roles/{$agentRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $response->assertOk();

        // Solo tiene rol USER activo
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
        $this->targetUser->assignRole('AGENT', $this->company->id);
        $this->targetUser->assignRole('PLATFORM_ADMIN');

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act - Remover rol AGENT
        $agentRole = $this->targetUser->userRoles()->where('role_code', 'AGENT')->first();
        $response1 = $this->deleteJson("/api/users/roles/{$agentRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        $this->assertOk($response1->status());

        // Act - Remover rol PLATFORM_ADMIN
        $adminRole = $this->targetUser->userRoles()->where('role_code', 'PLATFORM_ADMIN')->first();
        $response2 = $this->deleteJson("/api/users/roles/{$adminRole->id}", [], [
            'Authorization' => "Bearer $token",
        ]);

        // Assert
        $this->assertOk($response2->status());

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
        $token = $this->generateAccessToken($this->platformAdmin);

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

            $data = ['reason' => $reason];

            // Act
            $response = $this->deleteJson("/api/users/roles/{$role->id}", $data, [
                'Authorization' => "Bearer $token",
            ]);

            // Assert
            $response->assertOk()
                ->assertJson(['success' => true]);

            $role->refresh();
            $this->assertEquals($reason, $role->revocation_reason);
        }
    }
}
