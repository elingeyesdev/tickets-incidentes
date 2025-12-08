<?php

namespace Tests\Feature\CompanyManagement;

use App\Features\Authentication\Services\TokenService;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\Role;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MultiRoleCompanyAccessTest
 * 
 * Tests para verificar que usuarios con múltiples roles acceden correctamente
 * a las empresas según su ROL ACTIVO, no según todos sus roles.
 * 
 * Bug encontrado: CompanyController::index() usaba $user->hasRole('COMPANY_ADMIN')
 * en lugar de JWTHelper::getActiveRoleCode() === 'COMPANY_ADMIN', causando que
 * usuarios multi-rol con PLATFORM_ADMIN + COMPANY_ADMIN vieran filtrado incorrecto.
 */
class MultiRoleCompanyAccessTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $tokenService;
    private User $multiRoleUser;
    private Company $companyA;
    private Company $companyB;
    private Company $companyC;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        $this->seedRoles();
        $this->createTestData();
    }

    private function seedRoles(): void
    {
        $roles = [
            ['role_code' => 'PLATFORM_ADMIN', 'role_name' => 'Platform Administrator', 'description' => 'Full system access', 'is_system' => true],
            ['role_code' => 'COMPANY_ADMIN', 'role_name' => 'Company Administrator', 'description' => 'Company management', 'is_system' => true],
            ['role_code' => 'AGENT', 'role_name' => 'Support Agent', 'description' => 'Ticket handling', 'is_system' => true],
            ['role_code' => 'USER', 'role_name' => 'End User', 'description' => 'Basic user', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            // Use firstOrCreate to avoid duplicate key errors when roles already exist
            Role::firstOrCreate(
                ['role_code' => $role['role_code']],
                $role
            );
        }
    }

    private function createTestData(): void
    {
        // Create 3 companies using factory (handles industry_id and other required fields)
        $this->companyA = Company::factory()->create([
            'name' => 'Company Alpha',
            'company_code' => 'CMP-ALPHA',
        ]);

        $this->companyB = Company::factory()->create([
            'name' => 'Company Beta',
            'company_code' => 'CMP-BETA',
        ]);

        $this->companyC = Company::factory()->create([
            'name' => 'Company Gamma',
            'company_code' => 'CMP-GAMMA',
        ]);

        // Create multi-role user with PLATFORM_ADMIN + COMPANY_ADMIN (for Company Alpha)
        $this->multiRoleUser = User::factory()->create([
            'email' => 'multirole@test.com',
        ]);

        // Assign PLATFORM_ADMIN (no company)
        UserRole::create([
            'user_id' => $this->multiRoleUser->id,
            'role_code' => 'PLATFORM_ADMIN',
            'company_id' => null,
            'is_active' => true,
        ]);

        // Assign COMPANY_ADMIN for Company Alpha
        UserRole::create([
            'user_id' => $this->multiRoleUser->id,
            'role_code' => 'COMPANY_ADMIN',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);
    }

    /**
     * Helper to generate JWT with specific active_role
     */
    private function getTokenWithActiveRole(User $user, string $roleCode, ?string $companyId = null): string
    {
        return $this->tokenService->generateAccessToken($user, null, [
            'code' => $roleCode,
            'company_id' => $companyId,
        ]);
    }

    // ==========================================
    // PLATFORM_ADMIN Active Role Tests
    // ==========================================

    /** @test */
    public function platform_admin_active_role_sees_all_companies(): void
    {
        $token = $this->getTokenWithActiveRole(
            $this->multiRoleUser,
            'PLATFORM_ADMIN',
            null
        );

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $response->assertOk();

        $data = $response->json('data');
        // PLATFORM_ADMIN sees ALL companies (at least our 3 created ones)
        $this->assertGreaterThanOrEqual(3, count($data), 'PLATFORM_ADMIN should see all companies');

        $companyNames = collect($data)->pluck('name')->toArray();
        $this->assertContains('Company Alpha', $companyNames);
        $this->assertContains('Company Beta', $companyNames);
        $this->assertContains('Company Gamma', $companyNames);
    }

    /** @test */
    public function platform_admin_active_role_total_count_is_correct(): void
    {
        $token = $this->getTokenWithActiveRole(
            $this->multiRoleUser,
            'PLATFORM_ADMIN',
            null
        );

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $response->assertOk();
        // Total should include at least our 3 test companies
        $total = $response->json('meta.total');
        $this->assertGreaterThanOrEqual(3, $total, 'PLATFORM_ADMIN total should include all companies');
    }

    // ==========================================
    // COMPANY_ADMIN Active Role Tests
    // ==========================================

    /** @test */
    public function company_admin_active_role_sees_only_their_company(): void
    {
        $token = $this->getTokenWithActiveRole(
            $this->multiRoleUser,
            'COMPANY_ADMIN',
            $this->companyA->id
        );

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data, 'COMPANY_ADMIN should see only their company');
        $this->assertEquals('Company Alpha', $data[0]['name']);
    }

    /** @test */
    public function company_admin_active_role_total_count_is_one(): void
    {
        $token = $this->getTokenWithActiveRole(
            $this->multiRoleUser,
            'COMPANY_ADMIN',
            $this->companyA->id
        );

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
    }

    /** @test */
    public function company_admin_cannot_see_other_companies(): void
    {
        $token = $this->getTokenWithActiveRole(
            $this->multiRoleUser,
            'COMPANY_ADMIN',
            $this->companyA->id
        );

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $response->assertOk();

        $data = $response->json('data');
        $companyNames = collect($data)->pluck('name')->toArray();
        
        $this->assertNotContains('Company Beta', $companyNames);
        $this->assertNotContains('Company Gamma', $companyNames);
    }

    // ==========================================
    // Role Switching Tests (Same User, Different Active Role)
    // ==========================================

    /** @test */
    public function same_user_sees_different_companies_based_on_active_role(): void
    {
        // First request as PLATFORM_ADMIN
        $platformAdminToken = $this->getTokenWithActiveRole(
            $this->multiRoleUser,
            'PLATFORM_ADMIN',
            null
        );

        $platformAdminResponse = $this->withHeaders([
            'Authorization' => "Bearer {$platformAdminToken}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $platformAdminResponse->assertOk();
        $platformAdminCount = $platformAdminResponse->json('meta.total');

        // Second request as COMPANY_ADMIN (same user!)
        $companyAdminToken = $this->getTokenWithActiveRole(
            $this->multiRoleUser,
            'COMPANY_ADMIN',
            $this->companyA->id
        );

        $companyAdminResponse = $this->withHeaders([
            'Authorization' => "Bearer {$companyAdminToken}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $companyAdminResponse->assertOk();
        $companyAdminCount = $companyAdminResponse->json('meta.total');

        // The key assertion: same user, different results based on active role
        $this->assertGreaterThanOrEqual(3, $platformAdminCount, 'As PLATFORM_ADMIN should see all companies');
        $this->assertEquals(1, $companyAdminCount, 'As COMPANY_ADMIN should see only 1 company');
        $this->assertGreaterThan($companyAdminCount, $platformAdminCount, 'PLATFORM_ADMIN should see more than COMPANY_ADMIN');
    }

    // ==========================================
    // Edge Cases
    // ==========================================

    /** @test */
    public function user_with_only_platform_admin_sees_all_companies(): void
    {
        // Create user with ONLY PLATFORM_ADMIN
        $pureAdmin = User::factory()->create([
            'email' => 'pureadmin@test.com',
        ]);

        UserRole::create([
            'user_id' => $pureAdmin->id,
            'role_code' => 'PLATFORM_ADMIN',
            'company_id' => null,
            'is_active' => true,
        ]);

        $token = $this->getTokenWithActiveRole($pureAdmin, 'PLATFORM_ADMIN', null);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')), 'PLATFORM_ADMIN should see all companies');
    }

    /** @test */
    public function user_with_only_company_admin_sees_only_their_company(): void
    {
        // Create user with ONLY COMPANY_ADMIN for Company Beta
        $pureCompanyAdmin = User::factory()->create([
            'email' => 'purecompanyadmin@test.com',
        ]);

        UserRole::create([
            'user_id' => $pureCompanyAdmin->id,
            'role_code' => 'COMPANY_ADMIN',
            'company_id' => $this->companyB->id,
            'is_active' => true,
        ]);

        $token = $this->getTokenWithActiveRole(
            $pureCompanyAdmin,
            'COMPANY_ADMIN',
            $this->companyB->id
        );

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Company Beta', $data[0]['name']);
    }

    /** @test */
    public function user_with_multiple_company_admin_roles_sees_active_company_only(): void
    {
        // Create user who is COMPANY_ADMIN of TWO companies
        $dualCompanyAdmin = User::factory()->create([
            'email' => 'dualadmin@test.com',
        ]);

        // Admin of Company Alpha
        UserRole::create([
            'user_id' => $dualCompanyAdmin->id,
            'role_code' => 'COMPANY_ADMIN',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);

        // Admin of Company Beta
        UserRole::create([
            'user_id' => $dualCompanyAdmin->id,
            'role_code' => 'COMPANY_ADMIN',
            'company_id' => $this->companyB->id,
            'is_active' => true,
        ]);

        // Request with Alpha as active company
        $tokenAlpha = $this->getTokenWithActiveRole(
            $dualCompanyAdmin,
            'COMPANY_ADMIN',
            $this->companyA->id
        );

        $responseAlpha = $this->withHeaders([
            'Authorization' => "Bearer {$tokenAlpha}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $responseAlpha->assertOk();
        $this->assertCount(1, $responseAlpha->json('data'));
        $this->assertEquals('Company Alpha', $responseAlpha->json('data.0.name'));

        // Request with Beta as active company
        $tokenBeta = $this->getTokenWithActiveRole(
            $dualCompanyAdmin,
            'COMPANY_ADMIN',
            $this->companyB->id
        );

        $responseBeta = $this->withHeaders([
            'Authorization' => "Bearer {$tokenBeta}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $responseBeta->assertOk();
        $this->assertCount(1, $responseBeta->json('data'));
        $this->assertEquals('Company Beta', $responseBeta->json('data.0.name'));
    }

    // ==========================================
    // Regression Test: Original Bug Scenario
    // ==========================================

    /** @test */
    public function regression_multi_role_user_platform_admin_not_filtered_by_company_admin_role(): void
    {
        /**
         * This test specifically validates the bug fix where:
         * 
         * BEFORE: CompanyController::index() used $user->hasRole('COMPANY_ADMIN')
         *         which returned true for users who HAD the role anywhere,
         *         incorrectly filtering even when active role was PLATFORM_ADMIN
         * 
         * AFTER:  CompanyController::index() uses JWTHelper::getActiveRoleCode()
         *         which checks the ACTIVE role from JWT, not all user roles
         */
        
        // Verify our test user has BOTH roles
        $this->assertTrue(
            $this->multiRoleUser->hasRole('PLATFORM_ADMIN'),
            'Test user should have PLATFORM_ADMIN role'
        );
        $this->assertTrue(
            $this->multiRoleUser->hasRole('COMPANY_ADMIN'),
            'Test user should have COMPANY_ADMIN role'
        );

        // Generate token with PLATFORM_ADMIN as active role
        $token = $this->getTokenWithActiveRole(
            $this->multiRoleUser,
            'PLATFORM_ADMIN',
            null
        );

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $response->assertOk();

        // THE KEY ASSERTION: Even though user hasRole('COMPANY_ADMIN') returns true,
        // when their ACTIVE role is PLATFORM_ADMIN, they should see ALL companies
        $total = $response->json('meta.total');
        $this->assertGreaterThanOrEqual(
            3,
            $total,
            'User with active PLATFORM_ADMIN role should see all companies, ' .
            'regardless of also having COMPANY_ADMIN role elsewhere'
        );
    }

    /** @test */
    public function regression_multi_role_user_company_admin_is_filtered_correctly(): void
    {
        /**
         * Complementary regression test: when the same multi-role user
         * switches to COMPANY_ADMIN active role, they SHOULD be filtered
         */
        
        $token = $this->getTokenWithActiveRole(
            $this->multiRoleUser,
            'COMPANY_ADMIN',
            $this->companyA->id
        );

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->getJson('/api/companies');

        $response->assertOk();

        // When active role IS COMPANY_ADMIN, filtering should apply
        $this->assertEquals(
            1,
            $response->json('meta.total'),
            'User with active COMPANY_ADMIN role should see only their company'
        );
    }
}
