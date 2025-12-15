<?php

declare(strict_types=1);

namespace Tests\Feature\ActiveRole;

use App\Features\Authentication\Services\TokenService;
use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para verificar que usuarios con múltiples roles
 * solo ven/modifican datos correspondientes al rol ACTIVO
 * 
 * NOTA: La API de áreas/categorías requiere company_id como query param por diseño
 * Por lo tanto, los tests de filtrado se enfocan en validar que:
 * - El usuario no pueda modificar recursos de otras empresas aunque tenga rol ahí
 * - El sistema de active_role funcione correctamente para autorización
 */
class MultiRoleDataFilteringTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    // ==========================================
    // GRUPO 1: Listar Áreas con company_id explícito
    // (API requiere company_id como query param)
    // ==========================================

    /**
     * Test: COMPANY_ADMIN puede listar áreas de su empresa activa pasando company_id
     */
    public function test_company_admin_lists_areas_with_company_id_param(): void
    {
        // Arrange: Usuario es COMPANY_ADMIN en 2 empresas
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        
        // Crear áreas en ambas empresas
        $areaA1 = Area::factory()->create(['company_id' => $companyA->id, 'name' => 'Area A1']);
        $areaA2 = Area::factory()->create(['company_id' => $companyA->id, 'name' => 'Area A2']);
        $areaB1 = Area::factory()->create(['company_id' => $companyB->id, 'name' => 'Area B1']);
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Token con Company A activa
        $tokenA = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        // Act: Listar áreas pasando company_id de Company A
        $response = $this->withHeaders(['Authorization' => "Bearer $tokenA"])
            ->getJson("/api/areas?company_id={$companyA->id}");

        // Assert: Ve áreas de Company A
        $response->assertStatus(200);
        $areas = collect($response->json('data'));
        
        // Debe contener las áreas de Company A
        $this->assertTrue($areas->contains('id', $areaA1->id), 'Should contain Area A1');
        $this->assertTrue($areas->contains('id', $areaA2->id), 'Should contain Area A2');
        
        // NO debe contener áreas de Company B
        $this->assertFalse($areas->contains('id', $areaB1->id), 'Should NOT contain Area B1');
    }

    /**
     * Test: API requiere company_id para listar áreas
     */
    public function test_listing_areas_requires_company_id(): void
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

        // Act: Intentar listar sin company_id
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/areas');

        // Assert: Debe retornar error de validación (422)
        $response->assertStatus(422);
    }

    // ==========================================
    // GRUPO 2: Políticas de UPDATE/DELETE con Active Role
    // ==========================================

    /**
     * Test: COMPANY_ADMIN puede actualizar área de su empresa activa
     */
    public function test_company_admin_can_update_area_in_active_company(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $area = Area::factory()->create(['company_id' => $company->id, 'name' => 'Original Name']);
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $company->id);

        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $company->id]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/areas/{$area->id}", [
                'name' => 'Updated Name',
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test: COMPANY_ADMIN NO puede actualizar área de OTRA empresa (aunque tenga rol ahí)
     */
    public function test_company_admin_cannot_update_area_from_other_company(): void
    {
        // Arrange: Usuario es COMPANY_ADMIN en 2 empresas
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        
        // Área pertenece a Company B
        $areaB = Area::factory()->create(['company_id' => $companyB->id, 'name' => 'Area B']);
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Token con Company A activa (NO Company B)
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        // Act: Intentar actualizar área de Company B con Company A activa
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/areas/{$areaB->id}", [
                'name' => 'Hacked Name',
            ]);

        // Assert: Debe fallar (403)
        $response->assertStatus(403);
        
        // El nombre NO debe haber cambiado
        $this->assertDatabaseHas('business.areas', [
            'id' => $areaB->id,
            'name' => 'Area B', // Original name
        ]);
    }

    /**
     * Test: COMPANY_ADMIN puede eliminar área de su empresa activa
     * NOTA: Area no usa SoftDeletes, por lo que verificamos que se elimine completamente
     */
    public function test_company_admin_can_delete_area_in_active_company(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $area = Area::factory()->create(['company_id' => $company->id]);
        $areaId = $area->id;
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $company->id);

        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $company->id]
        );

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/areas/{$areaId}");

        // Assert: Debe retornar 200 o 204
        $this->assertTrue(
            in_array($response->status(), [200, 204]),
            "Expected 200 or 204, got: {$response->status()}"
        );
        
        // Area debe estar eliminada o soft-deleted
        // Verificamos que no sea accesible
        $this->assertNull(Area::find($areaId));
    }

    /**
     * Test: COMPANY_ADMIN NO puede eliminar área de OTRA empresa
     */
    public function test_company_admin_cannot_delete_area_from_other_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        
        $areaB = Area::factory()->create(['company_id' => $companyB->id]);
        $areaBId = $areaB->id;
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Token con Company A activa
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        // Act: Intentar eliminar área de Company B
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/areas/{$areaBId}");

        // Assert: Debe fallar
        $response->assertStatus(403);
        
        // El área NO debe estar eliminada
        $this->assertNotNull(Area::find($areaBId), 'Area should still exist');
    }

    // ==========================================
    // GRUPO 3: Edge Cases
    // ==========================================

    // NOTE: GET /api/areas/{id} endpoint does not exist (returns 405)
    // Areas are listed by company_id, not accessed individually

    /**
     * Test: Token con active_role fallback funciona correctamente
     */
    public function test_token_without_explicit_active_role_uses_fallback(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $area = Area::factory()->create(['company_id' => $company->id]);
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $company->id);

        // Token SIN active_role explícito (backward compatibility)
        $token = $this->tokenService->generateAccessToken($user);

        // Act: Listar áreas con company_id
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/areas?company_id={$company->id}");

        // Assert: Debe funcionar
        $response->assertStatus(200);
    }
}
