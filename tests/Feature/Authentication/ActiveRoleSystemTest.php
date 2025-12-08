<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Features\Authentication\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Active Role System Feature Tests
 *
 * Tests para verificar el sistema de rol activo (active_role) en JWT.
 * 
 * Cobertura:
 * 1. Generación de tokens con active_role
 * 2. Backward compatibility (usuarios con 1 rol auto-seleccionan)
 * 3. Endpoints de selección de rol (/auth/select-role, /auth/available-roles)
 * 4. Validación de permisos según rol activo
 * 5. Multi-company scenarios (usuarios con múltiples roles en diferentes empresas)
 * 
 * PROPÓSITO: Proteger contra regresiones en la nueva implementación de active_role
 * y asegurar que los tests viejos sigan pasando gracias a backward compatibility.
 */
class ActiveRoleSystemTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
    }

    // ==========================================
    // GRUPO 1: Token Generation & Claims
    // ==========================================

    /**
     * Test 1: Usuario con 1 solo rol - active_role se auto-selecciona
     * 
     * BACKWARD COMPATIBILITY TEST
     * Los tests viejos usan User::factory()->withRole('USER') que crea usuarios
     * con un solo rol. Este test verifica que esos usuarios automáticamente
     * tengan active_role en su JWT.
     */
    public function test_single_role_user_auto_selects_active_role(): void
    {
        // Arrange: Usuario con UN solo rol (patrón usado en tests viejos)
        $user = User::factory()->withRole('USER')->create();

        // Act: Generar token (sin especificar active_role)
        $token = $this->tokenService->generateAccessToken($user);

        // Assert: Decodificar y verificar que active_role se auto-seleccionó
        $payload = $this->decodeToken($token);

        $this->assertNotNull($payload->active_role, 'active_role debe estar presente en el token');
        $this->assertEquals('USER', $payload->active_role->code);
        $this->assertNull($payload->active_role->company_id, 'USER role no tiene company_id');
    }

    /**
     * Test 2: Usuario con múltiples roles - active_role es null sin selección explícita
     * 
     * Usuarios con múltiples roles NO tienen active_role auto-seleccionado.
     * El frontend debe redirigir a /role-selector.
     */
    public function test_multi_role_user_has_null_active_role_without_selection(): void
    {
        // Arrange: Usuario con 2 roles
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('USER');
        $user->assignRole('AGENT', $company->id);

        // Act: Generar token SIN especificar active_role
        $token = $this->tokenService->generateAccessToken($user);

        // Assert: active_role debe ser null
        $payload = $this->decodeToken($token);

        $this->assertNull($payload->active_role, 'active_role debe ser null para usuarios multi-rol sin selección');
        $this->assertCount(2, $payload->roles, 'Debe tener 2 roles en el payload');
    }

    /**
     * Test 3: Usuario multi-rol con active_role explícito
     * 
     * Cuando se llama a generateAccessToken con $activeRole, debe incluirse en el JWT.
     */
    public function test_multi_role_user_with_explicit_active_role(): void
    {
        // Arrange: Usuario con AGENT y COMPANY_ADMIN en diferentes empresas
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $user = User::factory()->create();
        $user->assignRole('AGENT', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Act: Generar token especificando active_role = AGENT en Company A
        $activeRole = ['code' => 'AGENT', 'company_id' => $companyA->id];
        $token = $this->tokenService->generateAccessToken($user, null, $activeRole);

        // Assert: active_role debe ser AGENT con company A
        $payload = $this->decodeToken($token);

        $this->assertNotNull($payload->active_role);
        $this->assertEquals('AGENT', $payload->active_role->code);
        $this->assertEquals($companyA->id, $payload->active_role->company_id);
    }

    // ==========================================
    // GRUPO 2: Role Selection Endpoints
    // ==========================================

    /**
     * Test 4: GET /api/auth/available-roles retorna roles disponibles
     * 
     * El endpoint debe retornar todos los roles del usuario con información
     * enriquecida (company name, dashboard path).
     */
    public function test_available_roles_endpoint_returns_user_roles(): void
    {
        // Arrange: Usuario con 3 roles
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $user = User::factory()->create();
        $user->assignRole('USER');
        $user->assignRole('AGENT', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Act: Llamar endpoint con JWT válido
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/auth/available-roles');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'code',
                        'company_id',
                        'dashboard_path',
                    ]
                ],
                'active_role',
            ]);

        $roles = $response->json('data');
        $this->assertCount(3, $roles, 'Debe retornar 3 roles');

        // Verificar que incluye company_name para roles con empresa
        $agentRole = collect($roles)->firstWhere('code', 'AGENT');
        $this->assertEquals('Company A', $agentRole['company_name']);
        $this->assertEquals('/app/agent/dashboard', $agentRole['dashboard_path']);
    }

    /**
     * Test 5: POST /api/auth/select-role cambia el rol activo
     * 
     * El endpoint debe generar un nuevo JWT con el active_role seleccionado.
     */
    public function test_select_role_endpoint_changes_active_role(): void
    {
        // Arrange: Usuario con AGENT y COMPANY_ADMIN
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('AGENT', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Act: Seleccionar COMPANY_ADMIN en Company B
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'COMPANY_ADMIN',
                'company_id' => $companyB->id,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'expires_at',
                    'active_role' => ['code', 'company_id'],
                ]
            ]);

        $newToken = $response->json('data.access_token');
        $payload = $this->decodeToken($newToken);

        // Verificar que active_role cambió
        $this->assertEquals('COMPANY_ADMIN', $payload->active_role->code);
        $this->assertEquals($companyB->id, $payload->active_role->company_id);
    }

    /**
     * Test 6: POST /api/auth/select-role - Validación de rol no asignado
     * 
     * No se puede seleccionar un rol que el usuario no tiene.
     */
    public function test_select_role_rejects_unassigned_role(): void
    {
        // Arrange: Usuario solo con USER role
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Act: Intentar seleccionar AGENT (no asignado)
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/auth/select-role', [
                'role_code' => 'AGENT',
                'company_id' => $company->id,
            ]);

        // Assert: 403 Forbidden
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'You do not have the requested role']);
    }

    /**
     * Test 7: POST /api/auth/select-role - Validación de company_id incorrecto
     * 
     * Si el usuario tiene AGENT en Company A, no puede seleccionar AGENT en Company B.
     */
    public function test_select_role_rejects_wrong_company_id(): void
    {
        // Arrange: Usuario con AGENT en Company A
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
        $response->assertStatus(403);
    }

    // ==========================================
    // GRUPO 3: Endpoint Filtering by Active Role
    // ==========================================

    /**
     * Test 8: Tickets - Usuario multi-rol ve solo tickets según active_role
     * 
     * Un usuario AGENT en Company A y AGENT en Company B debe ver:
     * - Solo tickets de Company A cuando active_role = AGENT (Company A)
     * - Solo tickets de Company B cuando active_role = AGENT (Company B)
     */
    public function test_tickets_filtered_by_active_role_company(): void
    {
        // Arrange: 2 empresas con tickets
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $categoryA = \App\Features\TicketManagement\Models\Category::factory()
            ->create(['company_id' => $companyA->id]);
        $categoryB = \App\Features\TicketManagement\Models\Category::factory()
            ->create(['company_id' => $companyB->id]);

        $ticketA = \App\Features\TicketManagement\Models\Ticket::factory()
            ->create(['company_id' => $companyA->id, 'category_id' => $categoryA->id]);
        $ticketB = \App\Features\TicketManagement\Models\Ticket::factory()
            ->create(['company_id' => $companyB->id, 'category_id' => $categoryB->id]);

        // Usuario es AGENT en ambas empresas
        $user = User::factory()->create();
        $user->assignRole('AGENT', $companyA->id);
        $user->assignRole('AGENT', $companyB->id);

        // Act 1: Seleccionar AGENT en Company A
        $tokenA = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'AGENT', 'company_id' => $companyA->id]
        );

        $responseA = $this->withHeaders(['Authorization' => "Bearer $tokenA"])
            ->getJson('/api/tickets');

        // Assert 1: Solo debe ver tickets de Company A
        $responseA->assertStatus(200);
        $ticketsA = $responseA->json('data');
        $this->assertCount(1, $ticketsA);
        $this->assertEquals($ticketA->id, $ticketsA[0]['id']);

        // Act 2: Seleccionar AGENT en Company B
        $tokenB = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'AGENT', 'company_id' => $companyB->id]
        );

        $responseB = $this->withHeaders(['Authorization' => "Bearer $tokenB"])
            ->getJson('/api/tickets');

        // Assert 2: Solo debe ver tickets de Company B
        $responseB->assertStatus(200);
        $ticketsB = $responseB->json('data');
        $this->assertCount(1, $ticketsB);
        $this->assertEquals($ticketB->id, $ticketsB[0]['id']);
    }

    /**
     * Test 9: Articles - COMPANY_ADMIN solo ve artículos de su empresa ACTIVA
     * 
     * COMPANY_ADMIN en Company A no puede ver DRAFT de Company B.
     */
    public function test_articles_filtered_by_active_company(): void
    {
        // Arrange: 2 empresas con artículos
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $category = \App\Features\ContentManagement\Models\ArticleCategory::factory()->create();

        $draftA = \App\Features\ContentManagement\Models\HelpCenterArticle::factory()
            ->create(['company_id' => $companyA->id, 'category_id' => $category->id, 'status' => 'DRAFT']);
        $draftB = \App\Features\ContentManagement\Models\HelpCenterArticle::factory()
            ->create(['company_id' => $companyB->id, 'category_id' => $category->id, 'status' => 'DRAFT']);

        // Usuario es COMPANY_ADMIN en ambas empresas
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Act: Seleccionar COMPANY_ADMIN en Company A
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/help-center/articles');

        // Assert: Solo debe ver DRAFT de Company A
        $response->assertStatus(200);
        $articles = $response->json('data');
        $this->assertCount(1, $articles);
        $this->assertEquals($draftA->id, $articles[0]['id']);
    }

    /**
     * Test 10: Users - COMPANY_ADMIN solo ve usuarios de su empresa ACTIVA
     * 
     * COMPANY_ADMIN en Company A no puede listar usuarios de Company B.
     */
    public function test_users_filtered_by_active_company(): void
    {
        // Arrange: 2 empresas con usuarios
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->withRole('AGENT', $companyA->id)->create();
        $userB = User::factory()->withRole('AGENT', $companyB->id)->create();

        // Admin de ambas empresas
        $admin = User::factory()->create();
        $admin->assignRole('COMPANY_ADMIN', $companyA->id);
        $admin->assignRole('COMPANY_ADMIN', $companyB->id);

        // Act: Seleccionar COMPANY_ADMIN en Company A
        $token = $this->tokenService->generateAccessToken(
            $admin,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/users');

        // Assert: Solo debe ver usuarios de Company A (userA + admin mismo)
        $response->assertStatus(200);
        $users = $response->json('data');
        
        $userIds = collect($users)->pluck('id')->toArray();
        $this->assertContains($userA->id, $userIds, 'Debe incluir userA (AGENT Company A)');
        $this->assertNotContains($userB->id, $userIds, 'NO debe incluir userB (AGENT Company B)');
    }

    // ==========================================
    // GRUPO 4: Backward Compatibility Tests
    // ==========================================

    /**
     * Test 11: Tests viejos siguen pasando - Usuario con 1 rol
     * 
     * Simula un test viejo que crea usuario con withRole('AGENT').
     * Debe funcionar sin cambios gracias a auto-selección de active_role.
     */
    public function test_old_tests_backward_compatible_single_role(): void
    {
        // Arrange: Patrón de test viejo
        $company = Company::factory()->create();
        $user = User::factory()->withRole('AGENT', $company->id)->create();
        $category = \App\Features\TicketManagement\Models\Category::factory()
            ->create(['company_id' => $company->id]);

        // Crear ticket para el agente
        $ticket = \App\Features\TicketManagement\Models\Ticket::factory()
            ->create([
                'company_id' => $company->id,
                'category_id' => $category->id,
                'owner_agent_id' => $user->id,
            ]);

        // Act: Usar authenticateWithJWT (igual que tests viejos)
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/tickets');

        // Assert: Debe funcionar igual que antes
        $response->assertStatus(200);
        $tickets = $response->json('data');
        $this->assertNotEmpty($tickets, 'Debe retornar tickets del agente');
    }

    /**
     * Test 12: Refresh token preserva active_role
     * 
     * Cuando se usa refresh token, el nuevo access token debe mantener
     * el mismo active_role que tenía el token anterior.
     */
    public function test_refresh_token_preserves_active_role(): void
    {
        // Arrange: Usuario con múltiples roles
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $user = User::factory()->create();
        $user->assignRole('AGENT', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Crear refresh token en DB para el usuario
        $refreshToken = \App\Features\Authentication\Models\RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', 'test-refresh-token-plain'),
            'device_name' => 'Test Device',
            'ip_address' => '127.0.0.1',
            'expires_at' => now()->addDays(30),
        ]);

        // Generar access token con active_role específico (COMPANY_ADMIN en Company B)
        $selectedActiveRole = ['code' => 'COMPANY_ADMIN', 'company_id' => $companyB->id];
        $accessToken = $this->tokenService->generateAccessToken($user, null, $selectedActiveRole);

        // Act: Hacer refresh enviando el access token expirado
        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'X-Refresh-Token' => 'test-refresh-token-plain',
        ])->postJson('/api/auth/refresh');

        // Assert: El nuevo token debe preservar el active_role
        $response->assertStatus(200);
        $newAccessToken = $response->json('accessToken');
        $this->assertNotNull($newAccessToken);

        // Decodificar nuevo token para verificar active_role
        $decoded = $this->decodeToken($newAccessToken);
        $this->assertNotNull($decoded->active_role, 'El nuevo token debe tener active_role');
        $this->assertEquals('COMPANY_ADMIN', $decoded->active_role->code);
        $this->assertEquals($companyB->id, $decoded->active_role->company_id);
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
