<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Features\Authentication\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Active Role System - Edge Cases and Advanced Scenarios
 * 
 * Tests adicionales para casos límite y scenarios avanzados del sistema de active_role.
 * Complementa ActiveRoleSystemTest.php con casos más complejos.
 */
class ActiveRoleEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    // ==========================================
    // GRUPO 1: Casos Límite de Roles
    // ==========================================

    /**
     * Test 1: Usuario sin roles asignados obtiene USER por defecto
     * 
     * El sistema garantiza que todo usuario tenga al menos el rol USER.
     * Esto es por diseño: cualquier usuario autenticado es al menos USER.
     */
    public function test_user_with_no_assigned_roles_gets_default_user_role(): void
    {
        // Arrange: Usuario sin roles explícitos asignados
        $user = User::factory()->create();
        // No asignar ningún rol explícitamente

        // Act: Generar token
        $token = $this->tokenService->generateAccessToken($user);

        // Assert: El sistema asigna USER por defecto y lo auto-selecciona
        $payload = $this->decodeToken($token);

        // Debe tener exactamente 1 rol: USER (el default del sistema)
        $this->assertIsArray($payload->roles, 'roles debe ser un array');
        $this->assertCount(1, $payload->roles, 'Debe tener exactamente 1 rol (USER por defecto)');
        $this->assertEquals('USER', $payload->roles[0]->code, 'El rol por defecto debe ser USER');
        $this->assertNull($payload->roles[0]->company_id, 'USER no tiene company_id');

        // Como solo tiene 1 rol, debe auto-seleccionarse
        $this->assertNotNull($payload->active_role, 'Con 1 rol, debe auto-seleccionarse');
        $this->assertEquals('USER', $payload->active_role->code);
    }

    /**
     * Test 2: Usuario con mismo rol en múltiples empresas
     * 
     * Un AGENT en Company A y Company B debe poder seleccionar cada uno.
     */
    public function test_user_with_same_role_in_multiple_companies(): void
    {
        // Arrange: Usuario AGENT en 3 empresas diferentes
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $companyC = Company::factory()->create(['name' => 'Company C']);

        $user = User::factory()->create();
        $user->assignRole('AGENT', $companyA->id);
        $user->assignRole('AGENT', $companyB->id);
        $user->assignRole('AGENT', $companyC->id);

        // Act 1: Seleccionar AGENT en Company A
        $responseA = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'AGENT',
                'company_id' => $companyA->id,
            ]);

        // Assert 1: Debe permitir seleccionar Company A
        $responseA->assertStatus(200);
        $tokenA = $responseA->json('data.access_token');
        $payloadA = $this->decodeToken($tokenA);
        $this->assertEquals($companyA->id, $payloadA->active_role->company_id);

        // Act 2: Seleccionar AGENT en Company B
        $responseB = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'AGENT',
                'company_id' => $companyB->id,
            ]);

        // Assert 2: Debe permitir seleccionar Company B
        $responseB->assertStatus(200);
        $tokenB = $responseB->json('data.access_token');
        $payloadB = $this->decodeToken($tokenB);
        $this->assertEquals($companyB->id, $payloadB->active_role->company_id);

        // Los tokens deben ser diferentes
        $this->assertNotEquals($tokenA, $tokenB, 'Tokens deben ser únicos por rol activo');
    }

    /**
     * Test 3: PLATFORM_ADMIN con otros roles
     * 
     * Usuario que es PLATFORM_ADMIN + COMPANY_ADMIN debe poder seleccionar ambos.
     */
    public function test_platform_admin_can_switch_to_other_roles(): void
    {
        // Arrange: Usuario con PLATFORM_ADMIN y COMPANY_ADMIN
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('PLATFORM_ADMIN');
        $user->assignRole('COMPANY_ADMIN', $company->id);

        // Act 1: Seleccionar PLATFORM_ADMIN
        $response1 = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'PLATFORM_ADMIN',
            ]);

        $response1->assertStatus(200);
        $payload1 = $this->decodeToken($response1->json('data.access_token'));
        $this->assertEquals('PLATFORM_ADMIN', $payload1->active_role->code);
        $this->assertNull($payload1->active_role->company_id);

        // Act 2: Seleccionar COMPANY_ADMIN
        $response2 = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'COMPANY_ADMIN',
                'company_id' => $company->id,
            ]);

        $response2->assertStatus(200);
        $payload2 = $this->decodeToken($response2->json('data.access_token'));
        $this->assertEquals('COMPANY_ADMIN', $payload2->active_role->code);
        $this->assertEquals($company->id, $payload2->active_role->company_id);
    }

    // ==========================================
    // GRUPO 2: Validaciones de Seguridad
    // ==========================================

    /**
     * Test 4: No se puede seleccionar rol de otra empresa
     * 
     * Usuario AGENT en Company A NO puede seleccionar Company B.
     */
    public function test_cannot_select_role_from_different_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('AGENT', $companyA->id);

        // Act: Intentar seleccionar AGENT en Company B (no asignado)
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'AGENT',
                'company_id' => $companyB->id,
            ]);

        // Assert: 403 Forbidden
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'You do not have the requested role']);
    }

    /**
     * Test 5: Validación - AGENT sin company_id es inválido
     * 
     * AGENT requiere company_id obligatorio.
     */
    public function test_agent_role_requires_company_id(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('AGENT', $company->id);

        // Act: Intentar seleccionar AGENT sin company_id
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'AGENT',
                // NO enviar company_id
            ]);

        // Assert: 422 Validation Error
        $response->assertStatus(422)
            ->assertJsonValidationErrors('company_id');
    }

    /**
     * Test 6: Validación - PLATFORM_ADMIN no debe tener company_id
     * 
     * PLATFORM_ADMIN es global, no requiere empresa.
     */
    public function test_platform_admin_should_not_have_company_id(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('PLATFORM_ADMIN');

        // Act: Intentar seleccionar PLATFORM_ADMIN con company_id (incorrecto)
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'PLATFORM_ADMIN',
                'company_id' => $company->id, // No debería enviarse
            ]);

        // Assert: 422 Validation Error
        $response->assertStatus(422)
            ->assertJsonValidationErrors('company_id');
    }

    /**
     * Test 7: No se puede seleccionar rol inactivo
     * 
     * Roles con is_active = false no deben poder seleccionarse.
     */
    public function test_cannot_select_inactive_role(): void
    {
        // Arrange: Usuario con rol AGENT activo
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('AGENT', $company->id);

        // Desactivar el rol
        $userRole = $user->userRoles()->where('role_code', 'AGENT')->first();
        $userRole->update(['is_active' => false]);

        // Act: Intentar seleccionar el rol inactivo
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'AGENT',
                'company_id' => $company->id,
            ]);

        // Assert: 403 Forbidden (el usuario ya no tiene este rol activo)
        $response->assertStatus(403);
    }

    // ==========================================
    // GRUPO 3: Comportamiento de Endpoints
    // ==========================================

    /**
     * Test 8: Crear ticket usa company especificada (rol USER)
     * 
     * Solo usuarios con rol USER pueden crear tickets (lógica de negocio).
     * El ticket se crea en la empresa especificada en el request.
     */
    public function test_create_ticket_uses_specified_company(): void
    {
        // Arrange: Usuario con rol USER (quien puede crear tickets)
        $companyA = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('USER'); // USER puede crear tickets

        $categoryA = \App\Features\TicketManagement\Models\Category::factory()
            ->create(['company_id' => $companyA->id]);

        // Generar token con rol USER activo
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'USER', 'company_id' => null]
        );

        // Act: Crear ticket especificando la company
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/tickets', [
                'company_id' => $companyA->id,
                'category_id' => $categoryA->id,
                'title' => 'Test Ticket Created by User',
                'description' => 'This is a test ticket description with enough characters.',
                'priority' => 'medium',
            ]);

        // Assert: Ticket creado exitosamente
        $response->assertStatus(201);
        $ticketId = $response->json('data.id');
        $ticket = \App\Features\TicketManagement\Models\Ticket::find($ticketId);
        $this->assertNotNull($ticket, 'El ticket debe existir');
        $this->assertEquals($companyA->id, $ticket->company_id, 'Ticket debe estar en la empresa especificada');
    }

    /**
     * Test 9: available-roles incluye active_role actual
     * 
     * La respuesta debe indicar cuál es el rol activo actual.
     */
    public function test_available_roles_shows_current_active_role(): void
    {
        // Arrange: Usuario con múltiples roles, seleccionar uno
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('AGENT', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'AGENT', 'company_id' => $companyA->id]
        );

        // Act: Llamar available-roles
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/auth/available-roles');

        // Assert: Debe mostrar AGENT como activo
        $response->assertStatus(200);
        $activeRole = $response->json('active_role');
        
        $this->assertNotNull($activeRole, 'active_role debe estar presente');
        $this->assertEquals('AGENT', $activeRole['code']);
        $this->assertEquals($companyA->id, $activeRole['company_id']);
    }

    /**
     * Test 10: Switching roles múltiples veces
     * 
     * Usuario debe poder cambiar de rol varias veces seguidas.
     */
    public function test_can_switch_roles_multiple_times(): void
    {
        // Arrange: Usuario con 3 roles
        $companyA = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('USER');
        $user->assignRole('AGENT', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyA->id);

        // Act 1: Seleccionar USER
        $response1 = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', ['role_code' => 'USER']);
        $response1->assertStatus(200);
        $payload1 = $this->decodeToken($response1->json('data.access_token'));
        $this->assertEquals('USER', $payload1->active_role->code);

        // Act 2: Cambiar a AGENT
        $response2 = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'AGENT',
                'company_id' => $companyA->id,
            ]);
        $response2->assertStatus(200);
        $payload2 = $this->decodeToken($response2->json('data.access_token'));
        $this->assertEquals('AGENT', $payload2->active_role->code);

        // Act 3: Cambiar a COMPANY_ADMIN
        $response3 = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'COMPANY_ADMIN',
                'company_id' => $companyA->id,
            ]);
        $response3->assertStatus(200);
        $payload3 = $this->decodeToken($response3->json('data.access_token'));
        $this->assertEquals('COMPANY_ADMIN', $payload3->active_role->code);

        // Act 4: Volver a USER
        $response4 = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', ['role_code' => 'USER']);
        $response4->assertStatus(200);
        $payload4 = $this->decodeToken($response4->json('data.access_token'));
        $this->assertEquals('USER', $payload4->active_role->code);
    }

    // ==========================================
    // GRUPO 4: Migración y Backward Compatibility
    // ==========================================

    /**
     * Test 11: JWT viejo sin active_role sigue funcionando (fallback)
     * 
     * Simula un JWT generado ANTES del feature de active_role.
     */
    public function test_old_jwt_without_active_role_uses_fallback(): void
    {
        // Este test verifica el fallback en JWTHelper::getActiveRole()
        // Si active_role es null, debe usar el primer rol del array
        
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('AGENT', $company->id);
        $user->assignRole('USER');

        // Generar token sin active_role (simula token viejo)
        $token = $this->tokenService->generateAccessToken($user, null, null);
        // Como tiene 2 roles, active_role será null
        
        $payload = $this->decodeToken($token);
        $this->assertNull($payload->active_role, 'Token multi-rol sin selección tiene active_role null');

        // El endpoint debe usar el fallback (primer rol)
        // En este caso, JWTHelper::getActiveRole() retornará el primer rol del array
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/users/me');

        // El endpoint debe funcionar (no dar 500)
        $response->assertStatus(200);
    }

    /**
     * Test 12: available-roles con usuario sin active_role seleccionado
     * 
     * Usuario multi-rol que aún no ha seleccionado debe ver null en active_role.
     */
    public function test_available_roles_shows_null_when_no_selection(): void
    {
        // Arrange: Usuario con 2 roles, sin selección
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('USER');
        $user->assignRole('AGENT', $company->id);

        // Generar token sin active_role
        $token = $this->tokenService->generateAccessToken($user, null, null);

        // Act: Llamar available-roles
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/auth/available-roles');

        // Assert: active_role debe ser null
        $response->assertStatus(200);
        $this->assertNull($response->json('active_role'), 'active_role debe ser null sin selección');
        $this->assertCount(2, $response->json('data'), 'Debe mostrar 2 roles disponibles');
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Decodificar JWT sin validar (solo para tests)
     */
    private function decodeToken(string $token): object
    {
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]));
        
        return $payload;
    }
}
