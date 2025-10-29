<?php

namespace Tests\Feature\UserManagement\Http\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Shared\Enums\UserStatus;

/**
 * Test suite completo para UserController (REST API)
 *
 * Migrado desde tests GraphQL:
 * - MeQueryTest → me()
 * - UsersQueryTest → index()
 * - UserQueryTest → show()
 * - SuspendAndActivateUserMutationsTest → updateStatus()
 * - DeleteUserMutationTest → destroy()
 *
 * Verifica:
 * - Autenticación JWT requerida en todos los endpoints
 * - Autorización según roles (PLATFORM_ADMIN, COMPANY_ADMIN, USER)
 * - Validación de datos de entrada
 * - Estructura correcta de respuestas JSON
 * - Comportamiento de negocio según especificaciones
 */
class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    // ========== SETUP Y HELPERS ==========

    private User $platformAdmin;
    private User $companyAdmin;
    private User $regularUser;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        // Platform admin - tiene todos los permisos
        $this->platformAdmin = User::factory()
            ->withProfile()
            ->withRole('PLATFORM_ADMIN')
            ->create();

        // Company admin - permisos sobre su empresa
        $this->companyAdmin = User::factory()
            ->withProfile()
            ->withRole('COMPANY_ADMIN', $this->company->id)
            ->create();

        // Usuario regular - permisos limitados
        $this->regularUser = User::factory()
            ->withProfile([
                'first_name' => 'Regular',
                'last_name' => 'User',
            ])
            ->withRole('USER')
            ->create();
    }

    // ========== GET /api/users/me Tests ==========

    /**
     * @test
     * Usuario autenticado puede obtener su información completa
     */
    public function test_me_returns_authenticated_user_complete_information(): void
    {
        // Arrange
        $user = User::factory()
            ->withProfile([
                'first_name' => 'María',
                'last_name' => 'García',
                'theme' => 'dark',
                'language' => 'es',
            ])
            ->withRole('USER')
            ->create([
                'email' => 'maria@example.com',
                'email_verified' => true,
                'status' => UserStatus::ACTIVE,
            ]);

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson('/api/users/me', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Estructura completa
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'userCode',
                    'email',
                    'emailVerified',
                    'status',
                    'authProvider',
                    'profile' => [
                        'firstName',
                        'lastName',
                        'displayName',
                        'theme',
                        'language',
                        'timezone',
                        'pushWebNotifications',
                        'notificationsTickets',
                    ],
                    'roleContexts',
                    'ticketsCount',
                    'resolvedTicketsCount',
                    'createdAt',
                    'updatedAt',
                ],
            ]);

        // Verificar datos
        $data = $response->json('data');
        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals($user->email, $data['email']);
        $this->assertTrue($data['emailVerified']);
        $this->assertEquals('ACTIVE', $data['status']);
        $this->assertEquals('María', $data['profile']['firstName']);
        $this->assertEquals('García', $data['profile']['lastName']);
        $this->assertEquals('dark', $data['profile']['theme']);
        $this->assertEquals('es', $data['profile']['language']);
        $this->assertCount(1, $data['roleContexts']);
        $this->assertEquals('USER', $data['roleContexts'][0]['roleCode']);
    }

    /**
     * @test
     * me() requiere autenticación
     */
    public function test_me_requires_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/users/me');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     * Usuario con múltiples roles retorna todos los roleContexts
     */
    public function test_me_with_multiple_roles_returns_all_contexts(): void
    {
        // Arrange - Usuario con múltiples roles
        $user = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $user->assignRole('PLATFORM_ADMIN');

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson('/api/users/me', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $roleContexts = $response->json('data.roleContexts');
        $this->assertCount(2, $roleContexts);

        $roleCodes = collect($roleContexts)->pluck('roleCode')->toArray();
        $this->assertContains('USER', $roleCodes);
        $this->assertContains('PLATFORM_ADMIN', $roleCodes);
    }

    /**
     * @test
     * averageRating es null para usuarios sin tickets resueltos
     */
    public function test_me_average_rating_null_without_resolved_tickets(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->regularUser);

        // Act
        $response = $this->getJson('/api/users/me', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $data = $response->json('data');
        $this->assertNull($data['averageRating']);
        $this->assertEquals(0, $data['resolvedTicketsCount']);
    }

    // ========== GET /api/users Tests ==========

    /**
     * @test
     * Platform admin puede listar todos los usuarios
     */
    public function test_index_platform_admin_can_list_all_users(): void
    {
        // Arrange - Crear varios usuarios
        User::factory()
            ->count(5)
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/users?page=1&per_page=15', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'userCode',
                        'email',
                        'status',
                        'profile',
                        'roleContexts',
                    ],
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, $response->json('meta.total'));
    }

    /**
     * @test
     * Puede filtrar por búsqueda de texto (email y nombre)
     */
    public function test_index_can_filter_by_search_text(): void
    {
        // Arrange
        User::factory()
            ->withProfile(['first_name' => 'María', 'last_name' => 'García'])
            ->withRole('USER')
            ->create(['email' => 'maria@example.com']);

        User::factory()
            ->withProfile(['first_name' => 'Juan', 'last_name' => 'Pérez'])
            ->withRole('USER')
            ->create(['email' => 'juan@example.com']);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/users?search=María', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $users = $response->json('data');
        $this->assertCount(1, $users);
        $this->assertEquals('maria@example.com', strtolower($users[0]['email']));
    }

    /**
     * @test
     * Puede filtrar por estado
     */
    public function test_index_can_filter_by_status(): void
    {
        // Arrange
        User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['status' => UserStatus::ACTIVE]);

        User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['status' => UserStatus::SUSPENDED]);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/users?status=SUSPENDED', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $users = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($users));
        foreach ($users as $user) {
            $this->assertEquals('SUSPENDED', $user['status']);
        }
    }

    /**
     * @test
     * Puede filtrar por rol
     */
    public function test_index_can_filter_by_role(): void
    {
        // Arrange
        User::factory()
            ->withProfile()
            ->withRole('AGENT', $this->company->id)
            ->create();

        User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/users?role=AGENT', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $users = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($users));
        foreach ($users as $user) {
            $roleCodes = collect($user['roleContexts'])->pluck('roleCode')->toArray();
            $this->assertContains('AGENT', $roleCodes);
        }
    }

    /**
     * @test
     * Company admin solo ve usuarios de su empresa
     */
    public function test_index_company_admin_only_sees_their_company_users(): void
    {
        // Arrange
        $otherCompany = Company::factory()->create();

        // Usuario en la empresa del admin
        User::factory()
            ->withProfile()
            ->withRole('AGENT', $this->company->id)
            ->create(['email' => 'agent1@company.com']);

        // Usuario en otra empresa
        User::factory()
            ->withProfile()
            ->withRole('AGENT', $otherCompany->id)
            ->create(['email' => 'agent2@othercompany.com']);

        $token = $this->generateAccessToken($this->companyAdmin);

        // Act
        $response = $this->getJson('/api/users', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $users = $response->json('data');
        $emails = collect($users)->pluck('email')->toArray();

        $this->assertContains('agent1@company.com', $emails);
        $this->assertNotContains('agent2@othercompany.com', $emails);
    }

    /**
     * @test
     * Puede ordenar por diferentes campos
     */
    public function test_index_can_order_by_different_fields(): void
    {
        // Arrange
        User::factory()
            ->count(3)
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson('/api/users?order_by=created_at&order_direction=asc', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $users = $response->json('data');
        $this->assertGreaterThanOrEqual(3, count($users));

        // Verificar que están ordenados ascendentemente
        $timestamps = collect($users)->pluck('createdAt')->toArray();
        $this->assertEquals($timestamps, collect($timestamps)->sort()->values()->toArray());
    }

    /**
     * @test
     * Respeta límite máximo de 50 por página
     */
    public function test_index_respects_maximum_limit_of_50_per_page(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act - Intentar más de 50
        $response = $this->getJson('/api/users?per_page=100', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $perPage = $response->json('meta.per_page');
        $this->assertLessThanOrEqual(50, $perPage);
    }

    /**
     * @test
     * index() requiere ser admin (PLATFORM_ADMIN o COMPANY_ADMIN)
     */
    public function test_index_requires_admin_role(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->regularUser);

        // Act
        $response = $this->getJson('/api/users', [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * index() requiere autenticación
     */
    public function test_index_requires_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/users');

        // Assert
        $response->assertStatus(401);
    }

    // ========== GET /api/users/{id} Tests ==========

    /**
     * @test
     * Platform admin puede ver información completa de cualquier usuario
     */
    public function test_show_platform_admin_can_view_complete_user_information(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile([
                'first_name' => 'Carlos',
                'last_name' => 'Rodríguez',
                'phone_number' => '+591 75987654',
            ])
            ->withRole('AGENT', $this->company->id)
            ->create([
                'email' => 'carlos@example.com',
                'email_verified' => true,
                'status' => UserStatus::ACTIVE,
            ]);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson("/api/users/{$targetUser->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'userCode',
                    'email',
                    'emailVerified',
                    'status',
                    'authProvider',
                    'profile',
                    'roleContexts',
                    'ticketsCount',
                    'resolvedTicketsCount',
                    'createdAt',
                    'updatedAt',
                ],
            ]);

        $user = $response->json('data');
        $this->assertEquals($targetUser->id, $user['id']);
        $this->assertEquals($targetUser->email, $user['email']);
        $this->assertEquals('Carlos', $user['profile']['firstName']);
        $this->assertEquals('Rodríguez', $user['profile']['lastName']);
        $this->assertCount(1, $user['roleContexts']);
        $this->assertEquals('AGENT', $user['roleContexts'][0]['roleCode']);
    }

    /**
     * @test
     * Estructura es idéntica a endpoint 'me' para consistencia
     */
    public function test_show_structure_is_identical_to_me_endpoint(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson("/api/users/{$targetUser->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Misma estructura que 'me'
        $user = $response->json('data');
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('userCode', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('profile', $user);
        $this->assertArrayHasKey('roleContexts', $user);
        $this->assertArrayHasKey('ticketsCount', $user);
    }

    /**
     * @test
     * Company admin puede ver usuarios de su empresa
     */
    public function test_show_company_admin_can_view_users_from_their_company(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('AGENT', $this->company->id)
            ->create();

        $token = $this->generateAccessToken($this->companyAdmin);

        // Act
        $response = $this->getJson("/api/users/{$targetUser->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);
        $user = $response->json('data');
        $this->assertEquals($targetUser->id, $user['id']);
        $this->assertEquals($this->company->id, $user['roleContexts'][0]['company']['id']);
    }

    /**
     * @test
     * Company admin NO puede ver usuarios de otras empresas
     */
    public function test_show_company_admin_cannot_view_users_from_other_companies(): void
    {
        // Arrange
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()
            ->withProfile()
            ->withRole('AGENT', $otherCompany->id)
            ->create();

        $token = $this->generateAccessToken($this->companyAdmin);

        // Act
        $response = $this->getJson("/api/users/{$otherUser->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * Usuario regular NO puede ver otros usuarios
     */
    public function test_show_regular_user_cannot_view_other_users(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->regularUser);

        // Act
        $response = $this->getJson("/api/users/{$targetUser->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * show() requiere autenticación
     */
    public function test_show_requires_authentication(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        // Act
        $response = $this->getJson("/api/users/{$targetUser->id}");

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     * Retorna 404 si usuario no existe
     */
    public function test_show_returns_404_if_user_not_found(): void
    {
        // Arrange
        $fakeId = '550e8400-e29b-41d4-a716-446655440000';
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson("/api/users/{$fakeId}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(404);
    }

    /**
     * @test
     * Usuario puede verse a sí mismo (equivalente a 'me')
     */
    public function test_show_user_can_view_themselves(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->regularUser);

        // Act
        $response = $this->getJson("/api/users/{$this->regularUser->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);
        $user = $response->json('data');
        $this->assertEquals($this->regularUser->id, $user['id']);
        $this->assertEquals($this->regularUser->email, $user['email']);
    }

    /**
     * @test
     * Usuario con múltiples roles retorna todos los roleContexts
     */
    public function test_show_user_with_multiple_roles_returns_all_contexts(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('AGENT', $this->company->id)
            ->create();

        $targetUser->assignRole('USER');

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson("/api/users/{$targetUser->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $roleContexts = $response->json('data.roleContexts');
        $this->assertCount(2, $roleContexts);

        $roleCodes = collect($roleContexts)->pluck('roleCode')->toArray();
        $this->assertContains('AGENT', $roleCodes);
        $this->assertContains('USER', $roleCodes);
    }

    /**
     * @test
     * averageRating es null para usuarios sin tickets resueltos
     */
    public function test_show_average_rating_null_without_resolved_tickets(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->getJson("/api/users/{$targetUser->id}", [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $user = $response->json('data');
        $this->assertNull($user['averageRating']);
        $this->assertEquals(0, $user['resolvedTicketsCount']);
    }

    // ========== PUT /api/users/{id}/status Tests ==========

    /**
     * @test
     * Platform admin puede suspender usuario exitosamente
     */
    public function test_update_status_platform_admin_can_suspend_user(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['status' => UserStatus::ACTIVE]);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'suspended',
            'reason' => 'Violación de términos de servicio',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'userId',
                    'status',
                    'updatedAt',
                ],
            ]);

        $result = $response->json('data');
        $this->assertEquals($targetUser->id, $result['userId']);
        $this->assertEquals('suspended', $result['status']);
        $this->assertNotNull($result['updatedAt']);

        // Verificar en base de datos
        $targetUser->refresh();
        $this->assertEquals(UserStatus::SUSPENDED, $targetUser->status);
    }

    /**
     * @test
     * Platform admin puede activar usuario suspendido
     */
    public function test_update_status_platform_admin_can_activate_user(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['status' => UserStatus::SUSPENDED]);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'active',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);
        $result = $response->json('data');
        $this->assertEquals($targetUser->id, $result['userId']);
        $this->assertEquals('active', $result['status']);

        // Verificar en base de datos
        $targetUser->refresh();
        $this->assertEquals(UserStatus::ACTIVE, $targetUser->status);
    }

    /**
     * @test
     * Reason es opcional al suspender
     */
    public function test_update_status_reason_is_optional(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['status' => UserStatus::ACTIVE]);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'suspended',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $result = $response->json('data');
        $this->assertEquals('suspended', $result['status']);
    }

    /**
     * @test
     * Company admin NO puede cambiar status de usuarios
     */
    public function test_update_status_company_admin_cannot_change_status(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->companyAdmin);

        // Act
        $response = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'suspended',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * Usuario regular NO puede cambiar status
     */
    public function test_update_status_regular_user_cannot_change_status(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->regularUser);

        // Act
        $response = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'suspended',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * updateStatus() requiere autenticación
     */
    public function test_update_status_requires_authentication(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        // Act
        $response = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'suspended',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     * Validación: status es requerido
     */
    public function test_update_status_validates_status_required(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->putJson("/api/users/{$targetUser->id}/status", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * @test
     * Validación: status debe ser válido
     */
    public function test_update_status_validates_status_valid(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'INVALID_STATUS',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * @test
     * Retorna UserStatusPayload (NO User completo)
     */
    public function test_update_status_returns_status_payload_not_complete_user(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'suspended',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - SOLO userId, status, updatedAt
        $result = $response->json('data');
        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('updatedAt', $result);
        $this->assertCount(3, $result);
    }

    /**
     * @test
     * Flujo completo: suspender y luego reactivar
     */
    public function test_update_status_complete_flow_suspend_then_activate(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['status' => UserStatus::ACTIVE]);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act - Suspender
        $suspendResponse = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'suspended',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $this->assertEquals('suspended', $suspendResponse->json('data.status'));

        // Act - Activar
        $activateResponse = $this->putJson("/api/users/{$targetUser->id}/status", [
            'status' => 'active',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $this->assertEquals('active', $activateResponse->json('data.status'));

        // Verificar estado final en BD
        $targetUser->refresh();
        $this->assertEquals(UserStatus::ACTIVE, $targetUser->status);
    }

    // ========== DELETE /api/users/{id} Tests ==========

    /**
     * @test
     * Platform admin puede eliminar usuario exitosamente
     */
    public function test_destroy_platform_admin_can_delete_user(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['status' => UserStatus::ACTIVE]);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson("/api/users/{$targetUser->id}?reason=GDPR+compliance", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'success' => true,
                ],
            ]);

        // Verificar soft delete en base de datos
        $targetUser->refresh();
        $this->assertEquals(UserStatus::DELETED, $targetUser->status);
        $this->assertNotNull($targetUser->deleted_at);
    }

    /**
     * @test
     * Reason es opcional en DELETE
     */
    public function test_destroy_reason_is_optional(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson("/api/users/{$targetUser->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'success' => true,
                ],
            ]);
    }

    /**
     * @test
     * Es soft delete (no elimina físicamente)
     */
    public function test_destroy_performs_soft_delete_not_hard_delete(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson("/api/users/{$targetUser->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $this->assertTrue($response->json('data.success'));

        // Usuario todavía existe en BD (con trashed)
        $userStillExists = User::withTrashed()->find($targetUser->id);
        $this->assertNotNull($userStillExists);
        $this->assertEquals(UserStatus::DELETED, $userStillExists->status);
    }

    /**
     * @test
     * Company admin NO puede eliminar usuarios
     */
    public function test_destroy_company_admin_cannot_delete_users(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->companyAdmin);

        // Act
        $response = $this->deleteJson("/api/users/{$targetUser->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * Usuario regular NO puede eliminar usuarios
     */
    public function test_destroy_regular_user_cannot_delete_users(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->regularUser);

        // Act
        $response = $this->deleteJson("/api/users/{$targetUser->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * destroy() requiere autenticación
     */
    public function test_destroy_requires_authentication(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        // Act
        $response = $this->deleteJson("/api/users/{$targetUser->id}");

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     * Retorna 404 si usuario no existe
     */
    public function test_destroy_returns_404_if_user_not_found(): void
    {
        // Arrange
        $fakeId = '550e8400-e29b-41d4-a716-446655440000';
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson("/api/users/{$fakeId}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(404);
    }

    /**
     * @test
     * Usuario no puede eliminarse a sí mismo
     */
    public function test_destroy_user_cannot_delete_themselves(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson("/api/users/{$this->platformAdmin->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     * @test
     * Perfil permanece accesible para auditoría
     */
    public function test_destroy_profile_remains_accessible_for_audit(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $this->deleteJson("/api/users/{$targetUser->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Perfil todavía accesible
        $deletedUser = User::withTrashed()->find($targetUser->id);
        $this->assertNotNull($deletedUser->profile);
    }

    /**
     * @test
     * Roles se mantienen para auditoría
     */
    public function test_destroy_roles_are_maintained_for_audit(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $this->deleteJson("/api/users/{$targetUser->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Roles todavía accesibles
        $deletedUser = User::withTrashed()->find($targetUser->id);
        $this->assertGreaterThan(0, $deletedUser->userRoles()->count());
    }

    /**
     * @test
     * Puede eliminar usuario ya suspendido
     */
    public function test_destroy_can_delete_already_suspended_user(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['status' => UserStatus::SUSPENDED]);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson("/api/users/{$targetUser->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $this->assertTrue($response->json('data.success'));

        // Status cambió a DELETED
        $targetUser->refresh();
        $this->assertEquals(UserStatus::DELETED, $targetUser->status);
    }

    /**
     * @test
     * deleted_at timestamp se registra correctamente
     */
    public function test_destroy_deleted_at_timestamp_is_recorded(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $this->assertNull($targetUser->deleted_at);

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $this->deleteJson("/api/users/{$targetUser->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $targetUser->refresh();
        $this->assertNotNull($targetUser->deleted_at);
        $this->assertInstanceOf(\DateTimeInterface::class, $targetUser->deleted_at);
    }

    /**
     * @test
     * Caso de uso GDPR: derecho al olvido
     */
    public function test_destroy_gdpr_right_to_be_forgotten(): void
    {
        // Arrange
        $targetUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->deleteJson(
            "/api/users/{$targetUser->id}?reason=" . urlencode('GDPR Art. 17 - Right to erasure'),
            [],
            [
                'Authorization' => "Bearer $token"
            ]
        );

        // Assert
        $this->assertTrue($response->json('data.success'));

        // Usuario marcado como DELETED
        $targetUser->refresh();
        $this->assertEquals(UserStatus::DELETED, $targetUser->status);
    }
}
