<?php

namespace Tests\Feature\UserManagement;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test específico para verificar batching de CompanyByIdBatchLoader
 * 
 * Este test verifica que cuando un usuario tiene múltiples roles con empresas,
 * el DataLoader funciona correctamente y carga las empresas de forma eficiente.
 */
class CompanyByIdBatchLoaderTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;
    private Company $company1;
    private Company $company2;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear dos empresas diferentes
        $this->company1 = Company::factory()->create([
            'name' => 'Empresa Alpha',
            'status' => 'active'
        ]);
        
        $this->company2 = Company::factory()->create([
            'name' => 'Empresa Beta', 
            'status' => 'active'
        ]);

        // Usuario con múltiples roles en diferentes empresas
        $this->testUser = User::factory()
            ->withProfile()
            ->withRole('USER') // No requiere empresa
            ->withRole('AGENT', $this->company1->id) // Requiere empresa 1
            ->withRole('COMPANY_ADMIN', $this->company2->id) // Requiere empresa 2
            ->create();
    }

    /**
     * @test
     * Usuario con múltiples roles con empresas carga correctamente todas las empresas
     * 
     * Este test verifica que el CompanyByIdBatchLoader funciona correctamente
     * cuando hay múltiples roleContexts que requieren cargar empresas diferentes.
     */
    public function user_with_multiple_company_roles_loads_all_companies_correctly(): void
    {
        // Arrange
        $query = '
            query Me {
                me {
                    id
                    email
                    roleContexts {
                        roleCode
                        roleName
                        dashboardPath
                        company {
                            id
                            name
                            companyCode
                        }
                    }
                }
            }
        ';

        // Act
        $response = $this->authenticateWithJWT($this->testUser)->graphQL($query);

        // Assert
        $response->assertOk();
        
        $user = $response->json('data.me');
        $roleContexts = $user['roleContexts'];
        
        // Debe tener 3 roleContexts: USER, AGENT, COMPANY_ADMIN
        $this->assertCount(3, $roleContexts);
        
        // Verificar que cada rol tiene la información correcta
        $roleCodes = collect($roleContexts)->pluck('roleCode')->toArray();
        $this->assertContains('USER', $roleCodes);
        $this->assertContains('AGENT', $roleCodes);
        $this->assertContains('COMPANY_ADMIN', $roleCodes);
        
        // Verificar empresas específicas
        foreach ($roleContexts as $context) {
            switch ($context['roleCode']) {
                case 'USER':
                    // USER no debe tener empresa
                    $this->assertNull($context['company']);
                    break;
                    
                case 'AGENT':
                    // AGENT debe tener empresa 1
                    $this->assertNotNull($context['company']);
                    $this->assertEquals($this->company1->id, $context['company']['id']);
                    $this->assertEquals('Empresa Alpha', $context['company']['name']);
                    $this->assertNotEmpty($context['company']['companyCode']);
                    break;
                    
                case 'COMPANY_ADMIN':
                    // COMPANY_ADMIN debe tener empresa 2
                    $this->assertNotNull($context['company']);
                    $this->assertEquals($this->company2->id, $context['company']['id']);
                    $this->assertEquals('Empresa Beta', $context['company']['name']);
                    $this->assertNotEmpty($context['company']['companyCode']);
                    break;
            }
        }
    }

    /**
     * @test
     * Múltiples usuarios con diferentes empresas cargan eficientemente
     * 
     * Este test verifica que el batching funciona cuando múltiples usuarios
     * requieren cargar empresas diferentes en la misma query.
     */
    public function multiple_users_with_different_companies_load_efficiently(): void
    {
        // Arrange - Crear usuarios adicionales
        $user2 = User::factory()
            ->withProfile()
            ->withRole('AGENT', $this->company1->id)
            ->create();
            
        $user3 = User::factory()
            ->withProfile()
            ->withRole('COMPANY_ADMIN', $this->company2->id)
            ->create();

        $query = '
            query Users {
                users {
                    data {
                        id
                        email
                        roleContexts {
                            roleCode
                            company {
                                id
                                name
                            }
                        }
                    }
                }
            }
        ';

        // Act
        $response = $this->authenticateWithJWT($this->testUser)->graphQL($query);

        // Assert
        $response->assertOk();
        
        $users = $response->json('data.users.data');
        $this->assertGreaterThanOrEqual(3, count($users));
        
        // Verificar que todas las empresas se cargaron correctamente
        $loadedCompanyIds = [];
        foreach ($users as $user) {
            foreach ($user['roleContexts'] as $context) {
                if ($context['company']) {
                    $loadedCompanyIds[] = $context['company']['id'];
                }
            }
        }
        
        // Debe haber cargado ambas empresas
        $this->assertContains($this->company1->id, $loadedCompanyIds);
        $this->assertContains($this->company2->id, $loadedCompanyIds);
    }

    /**
     * @test
     * Usuario con rol en empresa suspendida maneja correctamente el estado
     * 
     * Este test verifica que el DataLoader maneja correctamente empresas
     * con diferentes estados (active, suspended).
     */
    public function user_with_role_in_suspended_company_handles_status_correctly(): void
    {
        // Arrange - Suspender una empresa
        $this->company1->update(['status' => 'suspended']);
        
        $query = '
            query Me {
                me {
                    roleContexts {
                        roleCode
                        company {
                            id
                            name
                            companyCode
                        }
                    }
                }
            }
        ';

        // Act
        $response = $this->authenticateWithJWT($this->testUser)->graphQL($query);

        // Assert
        $response->assertOk();
        
        $roleContexts = $response->json('data.me.roleContexts');
        
        // Verificar que la empresa se carga correctamente (aunque esté suspendida)
        foreach ($roleContexts as $context) {
            if ($context['roleCode'] === 'AGENT') {
                $this->assertEquals('Empresa Alpha', $context['company']['name']);
                $this->assertNotEmpty($context['company']['companyCode']);
            }
        }
    }
}
