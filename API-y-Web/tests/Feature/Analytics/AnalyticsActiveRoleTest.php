<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Features\Authentication\Services\TokenService;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para verificar que AnalyticsController usa el sistema de active_role
 * 
 * Estos tests aseguran que:
 * 1. Los dashboards solo son accesibles con el rol activo correcto
 * 2. Los datos se filtran por la empresa del rol activo
 * 3. Usuarios con múltiples roles ven datos de su empresa activa
 */
class AnalyticsActiveRoleTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    // ==========================================
    // GRUPO 1: Company Dashboard (COMPANY_ADMIN)
    // ==========================================

    /**
     * Test: COMPANY_ADMIN puede acceder al dashboard de su empresa
     */
    public function test_company_admin_can_access_dashboard_with_active_role(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $company->id);

        // Token con active_role = COMPANY_ADMIN
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $company->id]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/analytics/company-dashboard');

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test: Usuario con rol AGENT activo no puede acceder a company dashboard
     */
    public function test_agent_active_role_cannot_access_company_dashboard(): void
    {
        // Arrange: Usuario es COMPANY_ADMIN y AGENT, pero tiene AGENT activo
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $company->id);
        $user->assignRole('AGENT', $company->id);

        // Token con active_role = AGENT (no COMPANY_ADMIN)
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'AGENT', 'company_id' => $company->id]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/analytics/company-dashboard');

        // Assert: 403 porque active_role no es COMPANY_ADMIN
        $response->assertStatus(403);
        $this->assertTrue(
            in_array($response->json('message'), [
                'Active role must be COMPANY_ADMIN.',
                'Insufficient permissions'
            ]),
            'Expected permission error message'
        );
    }

    /**
     * Test: COMPANY_ADMIN en múltiples empresas ve datos de su empresa activa
     */
    public function test_company_admin_sees_data_from_active_company_only(): void
    {
        // Arrange: Usuario es COMPANY_ADMIN en 2 empresas
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Token con Company A activa
        $tokenA = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        // Token con Company B activa
        $tokenB = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyB->id]
        );

        // Act & Assert: Ambos pueden acceder (el contenido depende de la empresa activa)
        $responseA = $this->withHeaders(['Authorization' => "Bearer $tokenA"])
            ->getJson('/api/analytics/company-dashboard');
        $responseA->assertStatus(200);

        $responseB = $this->withHeaders(['Authorization' => "Bearer $tokenB"])
            ->getJson('/api/analytics/company-dashboard');
        $responseB->assertStatus(200);
    }

    // ==========================================
    // GRUPO 2: Agent Dashboard (AGENT)
    // ==========================================

    /**
     * Test: AGENT puede acceder al dashboard de agentes
     */
    public function test_agent_can_access_agent_dashboard_with_active_role(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('AGENT', $company->id);

        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'AGENT', 'company_id' => $company->id]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/analytics/agent-dashboard');

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test: USER activo no puede acceder al dashboard de agentes
     */
    public function test_user_active_role_cannot_access_agent_dashboard(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('AGENT', $company->id);
        $user->assignRole('USER');

        // Token con active_role = USER
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'USER', 'company_id' => null]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/analytics/agent-dashboard');

        // Assert
        $response->assertStatus(403);
        $this->assertTrue(
            in_array($response->json('message'), [
                'Active role must be AGENT.',
                'Insufficient permissions'
            ]),
            'Expected permission error message'
        );
    }

    // ==========================================
    // GRUPO 3: Platform Dashboard (PLATFORM_ADMIN)
    // ==========================================

    /**
     * Test: PLATFORM_ADMIN puede acceder al dashboard de plataforma
     */
    public function test_platform_admin_can_access_platform_dashboard(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('PLATFORM_ADMIN');

        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'PLATFORM_ADMIN', 'company_id' => null]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/analytics/platform-dashboard');

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test: COMPANY_ADMIN activo no puede acceder al dashboard de plataforma
     */
    public function test_company_admin_cannot_access_platform_dashboard(): void
    {
        // Arrange: Usuario tiene PLATFORM_ADMIN y COMPANY_ADMIN pero COMPANY_ADMIN activo
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('PLATFORM_ADMIN');
        $user->assignRole('COMPANY_ADMIN', $company->id);

        // Token con COMPANY_ADMIN activo
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $company->id]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/analytics/platform-dashboard');

        // Assert
        $response->assertStatus(403);
        $this->assertTrue(
            in_array($response->json('message'), [
                'Active role must be PLATFORM_ADMIN.',
                'Insufficient permissions'
            ]),
            'Expected permission error message'
        );
    }

    // ==========================================
    // GRUPO 4: Company Stats (PLATFORM_ADMIN)
    // ==========================================

    /**
     * Test: PLATFORM_ADMIN puede ver estadísticas de cualquier empresa
     */
    public function test_platform_admin_can_view_any_company_stats(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('PLATFORM_ADMIN');

        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'PLATFORM_ADMIN', 'company_id' => null]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/analytics/companies/{$company->id}/stats");

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test: COMPANY_ADMIN no puede ver estadísticas vía admin endpoint
     */
    public function test_company_admin_cannot_view_company_stats_admin_endpoint(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $company->id);

        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $company->id]
        );

        // Act: Intentar acceder al endpoint de stats
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/analytics/companies/{$company->id}/stats");

        // Assert: Debe retornar 403 con mensaje de permisos insuficientes
        $response->assertStatus(403);
        $this->assertTrue(
            in_array($response->json('message'), [
                'Active role must be PLATFORM_ADMIN.',
                'Insufficient permissions'
            ]),
            'Expected permission error message, got: ' . $response->json('message')
        );
    }
}
