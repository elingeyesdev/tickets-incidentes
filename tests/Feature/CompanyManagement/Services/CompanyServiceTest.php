<?php

namespace Tests\Feature\CompanyManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para CompanyService
 *
 * Tests unitarios para cada método:
 * - create() - Crea empresa con company_code único
 * - update() - Actualiza campos correctamente
 * - suspend() - Cambia status a suspended y desactiva roles
 * - activate() - Cambia status a active
 * - getStats() - Retorna estadísticas correctas
 * - findById() - Encuentra empresa por ID
 * - findByCode() - Encuentra empresa por company_code
 * - getActive() - Retorna solo empresas activas
 * - isAdmin() - Retorna true si usuario es admin de empresa
 */
class CompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompanyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CompanyService::class);
    }

    /** @test */
    public function create_creates_company_with_unique_company_code()
    {
        // Arrange
        $adminUser = User::factory()->create();
        $data = [
            'name' => 'Test Company',
            'legal_name' => 'Test Company SRL',
        ];

        // Act
        $company = $this->service->create($data, $adminUser);

        // Assert
        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('Test Company', $company->name);
        $this->assertEquals($adminUser->id, $company->admin_user_id);
        $this->assertNotEmpty($company->company_code);
        $this->assertMatchesRegularExpression('/^CMP-\d{4}-\d{5}$/', $company->company_code);
        $this->assertEquals('active', $company->status);

        $this->assertDatabaseHas('business.companies', [
            'id' => $company->id,
            'name' => 'Test Company',
            'admin_user_id' => $adminUser->id,
        ]);
    }

    /** @test */
    public function update_updates_fields_correctly()
    {
        // Arrange
        $company = Company::factory()->create(['name' => 'Old Name']);
        $data = [
            'name' => 'New Name',
            'support_email' => 'new@support.com',
            'website' => 'https://newwebsite.com',
        ];

        // Act
        $updatedCompany = $this->service->update($company, $data);

        // Assert
        $this->assertEquals('New Name', $updatedCompany->name);
        $this->assertEquals('new@support.com', $updatedCompany->support_email);
        $this->assertEquals('https://newwebsite.com', $updatedCompany->website);

        $this->assertDatabaseHas('business.companies', [
            'id' => $company->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function suspend_changes_status_to_suspended_and_deactivates_roles()
    {
        // Arrange
        $company = Company::factory()->create(['status' => 'active']);
        $agent = User::factory()->create();
        $companyAdmin = User::factory()->create();

        // Asignar roles
        $agent->assignRole('AGENT', $company->id);
        $companyAdmin->assignRole('COMPANY_ADMIN', $company->id);

        // Verificar que roles están activos
        $this->assertDatabaseHas('auth.user_roles', [
            'user_id' => $agent->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Act
        $suspendedCompany = $this->service->suspend($company, 'Violation of terms');

        // Assert
        $this->assertEquals('suspended', $suspendedCompany->status);

        // Verificar que roles fueron desactivados
        $this->assertDatabaseHas('auth.user_roles', [
            'user_id' => $agent->id,
            'company_id' => $company->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('auth.user_roles', [
            'user_id' => $companyAdmin->id,
            'company_id' => $company->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function activate_changes_status_to_active()
    {
        // Arrange
        $company = Company::factory()->suspended()->create();

        // Act
        $activatedCompany = $this->service->activate($company);

        // Assert
        $this->assertEquals('active', $activatedCompany->status);

        $this->assertDatabaseHas('business.companies', [
            'id' => $company->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function get_stats_returns_correct_statistics()
    {
        // Arrange
        $company = Company::factory()->create();
        $agent1 = User::factory()->create();
        $agent2 = User::factory()->create();
        $user1 = User::factory()->create();

        // Asignar roles
        $agent1->assignRole('AGENT', $company->id);
        $agent2->assignRole('AGENT', $company->id);
        $user1->assignRole('USER', $company->id);

        // Act
        $stats = $this->service->getStats($company);

        // Assert
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('active_agents_count', $stats);
        $this->assertArrayHasKey('total_users_count', $stats);
        $this->assertArrayHasKey('followers_count', $stats);

        $this->assertEquals(2, $stats['active_agents_count']);
        $this->assertGreaterThanOrEqual(2, $stats['total_users_count']);
    }

    /** @test */
    public function find_by_id_returns_company()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $found = $this->service->findById($company->id);

        // Assert
        $this->assertInstanceOf(Company::class, $found);
        $this->assertEquals($company->id, $found->id);
    }

    /** @test */
    public function find_by_id_returns_null_for_nonexistent()
    {
        // Act
        $found = $this->service->findById('550e8400-e29b-41d4-a716-446655440999');

        // Assert
        $this->assertNull($found);
    }

    /** @test */
    public function find_by_code_returns_company()
    {
        // Arrange
        $company = Company::factory()->create(['company_code' => 'CMP-2025-00123']);

        // Act
        $found = $this->service->findByCode('CMP-2025-00123');

        // Assert
        $this->assertInstanceOf(Company::class, $found);
        $this->assertEquals($company->id, $found->id);
        $this->assertEquals('CMP-2025-00123', $found->company_code);
    }

    /** @test */
    public function find_by_code_returns_null_for_nonexistent()
    {
        // Act
        $found = $this->service->findByCode('CMP-2025-99999');

        // Assert
        $this->assertNull($found);
    }

    /** @test */
    public function get_active_returns_only_active_companies()
    {
        // Arrange
        Company::factory()->count(3)->create(['status' => 'active']);
        Company::factory()->count(2)->suspended()->create();

        // Act
        $active = $this->service->getActive();

        // Assert
        $this->assertCount(3, $active);

        foreach ($active as $company) {
            $this->assertEquals('active', $company->status);
        }
    }

    /** @test */
    public function get_active_respects_limit()
    {
        // Arrange
        Company::factory()->count(10)->create(['status' => 'active']);

        // Act
        $active = $this->service->getActive(5);

        // Assert
        $this->assertCount(5, $active);
    }

    /** @test */
    public function is_admin_returns_true_when_user_is_admin()
    {
        // Arrange
        $adminUser = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $adminUser->id]);

        // Act
        $result = $this->service->isAdmin($company, $adminUser);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function is_admin_returns_false_when_user_is_not_admin()
    {
        // Arrange
        $adminUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $company = Company::factory()->create(['admin_user_id' => $adminUser->id]);

        // Act
        $result = $this->service->isAdmin($company, $otherUser);

        // Assert
        $this->assertFalse($result);
    }
}
