<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement;

use App\Features\Authentication\Services\TokenService;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para verificar que Area y Category controllers usan el sistema de active_role
 * 
 * Estos tests aseguran que:
 * 1. store() usa getActiveCompanyId() para determinar la empresa
 * 2. Solo COMPANY_ADMIN puede crear áreas/categorías
 * 3. Las áreas/categorías se crean en la empresa del rol activo
 */
class AreaCategoryActiveRoleTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    // ==========================================
    // GRUPO 1: Crear Áreas con Active Role
    // ==========================================

    /**
     * Test: COMPANY_ADMIN puede crear área en su empresa activa
     */
    public function test_company_admin_can_create_area_in_active_company(): void
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

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/areas', [
                'name' => 'Área de Soporte',
                'description' => 'Área para tickets de soporte técnico',
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Area created successfully',
            ]);

        // Verificar que se creó en la empresa correcta
        $areaId = $response->json('data.id');
        $this->assertDatabaseHas('business.areas', [
            'id' => $areaId,
            'company_id' => $company->id,
            'name' => 'Área de Soporte',
        ]);
    }

    /**
     * Test: COMPANY_ADMIN en múltiples empresas crea área en la empresa ACTIVA
     */
    public function test_company_admin_creates_area_in_active_company_not_other(): void
    {
        // Arrange: Usuario es COMPANY_ADMIN en 2 empresas
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Token con Company B activa - DEBUG: decodificar para verificar
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyB->id]
        );

        // DEBUG: Verificar que el token contiene el active_role correcto
        $decoded = \Firebase\JWT\JWT::decode(
            $token,
            new \Firebase\JWT\Key(config('jwt.secret'), config('jwt.algo'))
        );
        $this->assertEquals($companyB->id, $decoded->active_role->company_id, 
            'DEBUG: Token debe contener company_id de Company B');

        // Act: Crear área (debe ir a Company B, no A)
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/areas', [
                'name' => 'Área de Ventas',
            ]);

        // Assert
        $response->assertStatus(201);

        $areaId = $response->json('data.id');
        
        // Debe estar en Company B (la activa)
        $this->assertDatabaseHas('business.areas', [
            'id' => $areaId,
            'company_id' => $companyB->id,
        ]);

        // NO debe estar en Company A
        $this->assertDatabaseMissing('business.areas', [
            'id' => $areaId,
            'company_id' => $companyA->id,
        ]);
    }

    /**
     * Test: AGENT no puede crear áreas (solo COMPANY_ADMIN)
     */
    public function test_agent_cannot_create_area(): void
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
            ->postJson('/api/areas', [
                'name' => 'Área no autorizada',
            ]);

        // Assert: Debe fallar (403 por policy)
        $response->assertStatus(403);
    }

    // ==========================================
    // GRUPO 2: Crear Categorías con Active Role
    // ==========================================

    /**
     * Test: COMPANY_ADMIN puede crear categoría en su empresa activa
     */
    public function test_company_admin_can_create_category_in_active_company(): void
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

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/tickets/categories', [
                'name' => 'Categoría de Soporte',
                'description' => 'Categoría para tickets de soporte',
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Category created successfully',
            ]);

        // Verificar que se creó en la empresa correcta
        $categoryId = $response->json('data.id');
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'company_id' => $company->id,
            'name' => 'Categoría de Soporte',
        ]);
    }

    /**
     * Test: COMPANY_ADMIN en múltiples empresas crea categoría en la empresa ACTIVA
     */
    public function test_company_admin_creates_category_in_active_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Token con Company A activa
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/tickets/categories', [
                'name' => 'Categoría Test',
            ]);

        // Assert
        $response->assertStatus(201);

        $categoryId = $response->json('data.id');

        // Debe estar en Company A (la activa)
        $this->assertDatabaseHas('ticketing.categories', [
            'id' => $categoryId,
            'company_id' => $companyA->id,
        ]);
    }

    /**
     * Test: AGENT no puede crear categorías
     */
    public function test_agent_cannot_create_category(): void
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
            ->postJson('/api/tickets/categories', [
                'name' => 'Categoría no autorizada',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test: Usuario sin company_id en active_role recibe 403
     */
    public function test_user_without_company_id_cannot_create_area(): void
    {
        // Arrange: PLATFORM_ADMIN no tiene company_id
        $user = User::factory()->create();
        $user->assignRole('PLATFORM_ADMIN');

        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'PLATFORM_ADMIN', 'company_id' => null]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/areas', [
                'name' => 'Área sin empresa',
            ]);

        // Assert: 403 porque no hay company context
        $response->assertStatus(403);
    }

    // ==========================================
    // GRUPO 3: Cambio de Rol Activo
    // ==========================================

    /**
     * Test: Al cambiar rol activo, se crea en la nueva empresa
     */
    public function test_switching_active_role_changes_target_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Crear área en Company A
        $tokenA = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        $responseA = $this->withHeaders(['Authorization' => "Bearer $tokenA"])
            ->postJson('/api/areas', ['name' => 'Área en Company A']);
        $responseA->assertStatus(201);
        $areaA = $responseA->json('data.id');

        // Cambiar a Company B y crear otra área
        $tokenB = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyB->id]
        );

        $responseB = $this->withHeaders(['Authorization' => "Bearer $tokenB"])
            ->postJson('/api/areas', ['name' => 'Área en Company B']);
        $responseB->assertStatus(201);
        $areaB = $responseB->json('data.id');

        // Assert: Cada área está en su empresa correspondiente
        $this->assertDatabaseHas('business.areas', [
            'id' => $areaA,
            'company_id' => $companyA->id,
        ]);

        $this->assertDatabaseHas('business.areas', [
            'id' => $areaB,
            'company_id' => $companyB->id,
        ]);

        // Verificar que son empresas diferentes
        $this->assertNotEquals($companyA->id, $companyB->id);
    }
}
