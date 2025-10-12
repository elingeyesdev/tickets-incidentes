<?php

namespace Tests\Feature\UserManagement;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para AvailableRolesQuery
 *
 * Verifica:
 * - Retorna lista de roles del sistema
 * - Incluye metadata de cada rol (requiresCompany, defaultDashboard, etc.)
 * - Solo accesible por admins
 * - Cache de 1 hora (definido en schema)
 * - Útil para selectores y validaciones en frontend
 */
class AvailableRolesQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin con permisos
        $this->adminUser = User::factory()
            ->withProfile()
            ->withRole('PLATFORM_ADMIN')
            ->create();
    }

    /**
     * @test
     * Admin puede obtener lista de roles disponibles
     */
    public function admin_can_get_available_roles_list(): void
    {
        // Arrange
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                    name
                    description
                    requiresCompany
                    defaultDashboard
                    isSystemRole
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'availableRoles' => [
                    '*' => [
                        'code',
                        'name',
                        'description',
                        'requiresCompany',
                        'defaultDashboard',
                        'isSystemRole',
                    ],
                ],
            ],
        ]);

        $roles = $response->json('data.availableRoles');
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
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                    name
                    requiresCompany
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert
        $roles = $response->json('data.availableRoles');
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
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                    requiresCompany
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert
        $roles = collect($response->json('data.availableRoles'));

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
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                    requiresCompany
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert
        $roles = collect($response->json('data.availableRoles'));

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
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                    defaultDashboard
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert
        $roles = $response->json('data.availableRoles');

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
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                    isSystemRole
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert
        $roles = collect($response->json('data.availableRoles'));

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
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                    name
                    description
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert
        $roles = $response->json('data.availableRoles');

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
        $company = \App\Features\CompanyManagement\Models\Company::factory()->create();

        $companyAdmin = User::factory()
            ->withProfile()
            ->withRole('COMPANY_ADMIN', $company->id)
            ->create();

        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($companyAdmin)->graphQL($query);

        // Assert
        $roles = $response->json('data.availableRoles');
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

        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($regularUser)->graphQL($query);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Query requiere autenticación
     */
    public function query_requires_authentication(): void
    {
        // Arrange
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                }
            }
        ';

        // Act
        $response = $this->graphQL($query);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Query es útil para selectores de roles en frontend
     */
    public function query_is_useful_for_role_selectors(): void
    {
        // Arrange - Simular caso de uso: llenar selector de roles
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                    name
                    requiresCompany
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert - Campos necesarios para selector
        $roles = $response->json('data.availableRoles');

        foreach ($roles as $role) {
            $this->assertArrayHasKey('code', $role); // value del option
            $this->assertArrayHasKey('name', $role); // label del option
            $this->assertArrayHasKey('requiresCompany', $role); // para validación condicional
        }
    }

    /**
     * @test
     * Query es útil para validación de asignación de roles
     */
    public function query_is_useful_for_role_assignment_validation(): void
    {
        // Arrange - Simular validación: ¿Este rol requiere empresa?
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                    requiresCompany
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert - Puede determinar qué roles necesitan companyId
        $roles = collect($response->json('data.availableRoles'));

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
        $query = '
            query AvailableRoles {
                availableRoles {
                    code
                }
            }
        ';

        // Act - Llamar dos veces
        $response1 = $this->actingAsGraphQL($this->adminUser)->graphQL($query);
        $response2 = $this->actingAsGraphQL($this->adminUser)->graphQL($query);

        // Assert - Mismo orden
        $roles1 = collect($response1->json('data.availableRoles'))->pluck('code')->toArray();
        $roles2 = collect($response2->json('data.availableRoles'))->pluck('code')->toArray();

        $this->assertEquals($roles1, $roles2);
    }
}
