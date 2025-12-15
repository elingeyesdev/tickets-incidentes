<?php

declare(strict_types=1);

namespace Tests\Feature\ActiveRole;

use App\Features\Authentication\Services\TokenService;
use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests para verificar que las Policies usan el sistema de active_role
 * 
 * Políticas a probar:
 * - AreaPolicy: create, update, delete
 * - CategoryPolicy: create, update, delete  
 * - TicketResponsePolicy: create, update
 * - TicketAttachmentPolicy: create, delete
 * - TicketRatingPolicy: create
 */
class PolicyActiveRoleTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        Storage::fake('local');
    }

    // ==========================================
    // GRUPO 1: AreaPolicy con Active Role
    // ==========================================

    /**
     * Test: AreaPolicy.update verifica company_id del rol ACTIVO, no de cualquier rol
     */
    public function test_area_policy_update_uses_active_role_company(): void
    {
        // Arrange: Usuario es COMPANY_ADMIN en 2 empresas
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        // Área pertenece a Company A
        $areaA = Area::factory()->create(['company_id' => $companyA->id]);
        // Área pertenece a Company B
        $areaB = Area::factory()->create(['company_id' => $companyB->id]);

        // Token con Company A activa
        $tokenA = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        // Act & Assert: Puede actualizar área de Company A
        $responseA = $this->withHeaders(['Authorization' => "Bearer $tokenA"])
            ->putJson("/api/areas/{$areaA->id}", ['name' => 'Updated A']);
        $responseA->assertStatus(200);

        // Act & Assert: NO puede actualizar área de Company B (aunque tiene rol ahí)
        $responseB = $this->withHeaders(['Authorization' => "Bearer $tokenA"])
            ->putJson("/api/areas/{$areaB->id}", ['name' => 'Hacked B']);
        $responseB->assertStatus(403);
    }

    /**
     * Test: AreaPolicy.delete verifica company_id del rol ACTIVO
     */
    public function test_area_policy_delete_uses_active_role_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        $areaB = Area::factory()->create(['company_id' => $companyB->id]);

        // Token con Company A activa
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        // Act: Intentar eliminar área de Company B
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/areas/{$areaB->id}");

        // Assert: Debe fallar
        $response->assertStatus(403);
    }

    // ==========================================
    // GRUPO 2: CategoryPolicy con Active Role
    // ==========================================

    /**
     * Test: CategoryPolicy.update verifica company_id del rol ACTIVO
     */
    public function test_category_policy_update_uses_active_role_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        $categoryA = Category::factory()->create(['company_id' => $companyA->id]);
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // Token con Company A activa
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        // Puede actualizar categoría de Company A
        $responseA = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/tickets/categories/{$categoryA->id}", ['name' => 'Updated']);
        $responseA->assertStatus(200);

        // NO puede actualizar categoría de Company B
        $responseB = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/tickets/categories/{$categoryB->id}", ['name' => 'Hacked']);
        $responseB->assertStatus(403);
    }

    /**
     * Test: CategoryPolicy.delete verifica company_id del rol ACTIVO
     */
    public function test_category_policy_delete_uses_active_role_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $companyA->id);
        $user->assignRole('COMPANY_ADMIN', $companyB->id);

        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);

        // Token con Company A activa
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $companyA->id]
        );

        // Act: Intentar eliminar categoría de Company B
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/tickets/categories/{$categoryB->id}");

        // Assert
        $response->assertStatus(403);
    }

    // ==========================================
    // GRUPO 3: TicketResponsePolicy con Active Role
    // ==========================================

    /**
     * Test: AGENT solo puede responder tickets de su empresa ACTIVA
     */
    public function test_agent_can_only_respond_to_tickets_in_active_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);
        
        $user = User::factory()->create();
        $user->assignRole('AGENT', $companyA->id);
        $user->assignRole('AGENT', $companyB->id);

        // Crear usuario creador del ticket
        $ticketCreator = User::factory()->create();
        $ticketCreator->assignRole('USER');

        // Ticket en Company B
        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'created_by_user_id' => $ticketCreator->id,
            'category_id' => $categoryB->id,
        ]);

        // Token con Company A activa
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'AGENT', 'company_id' => $companyA->id]
        );

        // Act: Intentar responder ticket de Company B (usando ticket_code)
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/tickets/{$ticketB->ticket_code}/responses", [
                'content' => 'Esta respuesta no debería permitirse',
            ]);

        // Assert: Debe fallar (403 o 404 si el ticket no es visible)
        $this->assertTrue(
            in_array($response->status(), [403, 404]),
            'Expected 403 or 404, got: ' . $response->status()
        );
    }

    /**
     * Test: AGENT puede responder tickets de su empresa ACTIVA
     */
    public function test_agent_can_respond_to_tickets_in_active_company(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        $ticketCreator = User::factory()->create();
        $ticketCreator->assignRole('USER');

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'created_by_user_id' => $ticketCreator->id,
            'category_id' => $category->id,
        ]);

        $token = $this->tokenService->generateAccessToken(
            $agent,
            null,
            ['code' => 'AGENT', 'company_id' => $company->id]
        );

        // Act - Use ticket_code instead of id (Route model binding uses ticket_code)
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/tickets/{$ticket->ticket_code}/responses", [
                'content' => 'Respuesta válida del agente',
            ]);

        // Assert
        $this->assertTrue(
            in_array($response->status(), [200, 201]),
            'Expected 200 or 201, got: ' . $response->status() . ' - ' . $response->content()
        );
    }

    // ==========================================
    // GRUPO 4: TicketAttachmentPolicy con Active Role
    // ==========================================

    /**
     * Test: AGENT solo puede agregar adjuntos a tickets de su empresa ACTIVA
     */
    public function test_agent_can_only_attach_to_tickets_in_active_company(): void
    {
        // Arrange
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $categoryB = Category::factory()->create(['company_id' => $companyB->id]);
        
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $companyA->id);
        $agent->assignRole('AGENT', $companyB->id);

        $ticketCreator = User::factory()->create();
        $ticketCreator->assignRole('USER');

        // Ticket en Company B
        $ticketB = Ticket::factory()->create([
            'company_id' => $companyB->id,
            'created_by_user_id' => $ticketCreator->id,
            'category_id' => $categoryB->id,
        ]);

        // Token con Company A activa
        $token = $this->tokenService->generateAccessToken(
            $agent,
            null,
            ['code' => 'AGENT', 'company_id' => $companyA->id]
        );

        // Act: Intentar adjuntar archivo a ticket de Company B (usando ticket_code)
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/tickets/{$ticketB->ticket_code}/attachments", [
                'file' => $file,
            ]);

        // Assert: Debe fallar (403 o 404 si el ticket no es visible)
        $this->assertTrue(
            in_array($response->status(), [403, 404]),
            'Expected 403 or 404, got: ' . $response->status()
        );
    }

    // ==========================================
    // GRUPO 5: Escenarios de Cambio de Rol - MIDDLEWARE TEST
    // ==========================================

    /**
     * Test: Usuario que es COMPANY_ADMIN y AGENT - con AGENT activo no puede crear áreas
     * 
     * ESTE TEST VERIFICA EL BUG CORREGIDO: El middleware ahora verifica el ROL ACTIVO
     * en lugar de solo verificar si el usuario TIENE el rol.
     */
    public function test_user_with_admin_and_agent_roles_cannot_create_area_as_agent(): void
    {
        // Arrange
        $company = Company::factory()->create();
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $company->id);
        $user->assignRole('AGENT', $company->id);

        // Token con AGENT activo (aunque también tiene COMPANY_ADMIN)
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'AGENT', 'company_id' => $company->id]
        );

        // Act: Intentar crear área con AGENT activo
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/areas', [
                'name' => 'Nueva Área',
            ]);

        // Assert: Debe fallar porque AGENT no puede crear áreas
        // El middleware debe verificar el rol ACTIVO, no todos los roles del usuario
        $response->assertStatus(403);
    }

    /**
     * Test: Mismo usuario, cambiando a COMPANY_ADMIN activo SÍ puede crear áreas
     */
    public function test_user_with_admin_and_agent_roles_can_create_area_as_admin(): void
    {
        // Arrange
        $company = Company::factory()->create();
        
        $user = User::factory()->create();
        $user->assignRole('COMPANY_ADMIN', $company->id);
        $user->assignRole('AGENT', $company->id);

        // Token con COMPANY_ADMIN activo
        $token = $this->tokenService->generateAccessToken(
            $user,
            null,
            ['code' => 'COMPANY_ADMIN', 'company_id' => $company->id]
        );

        // Act: Crear área con COMPANY_ADMIN activo
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/areas', [
                'name' => 'Nueva Área',
            ]);

        // Assert: Debe funcionar
        $response->assertStatus(201);
    }
}
