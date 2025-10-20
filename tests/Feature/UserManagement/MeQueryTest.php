<?php

namespace Tests\Feature\UserManagement;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para MeQuery
 *
 * Verifica:
 * - Retorna información completa del usuario autenticado
 * - Incluye perfil, roles activos y estadísticas
 * - Requiere autenticación
 * - Estructura consistente con Authentication feature
 */
class MeQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario de prueba con perfil y rol
        $this->testUser = User::factory()
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
    }

    /**
     * @test
     * Usuario autenticado puede obtener su información completa
     */
    public function authenticated_user_can_get_their_information(): void
    {
        // Arrange
        $query = '
            query Me {
                me {
                    id
                    userCode
                    email
                    emailVerified
                    status
                    authProvider
                    profile {
                        firstName
                        lastName
                        displayName
                        phoneNumber
                        avatarUrl
                        theme
                        language
                        timezone
                        pushWebNotifications
                        notificationsTickets
                    }
                    roleContexts {
                        roleCode
                        roleName
                        dashboardPath
                        company {
                            id
                            name
                        }
                    }
                    ticketsCount
                    resolvedTicketsCount
                    averageRating
                    lastLoginAt
                    createdAt
                    updatedAt
                }
            }
        ';

        // Act
        $response = $this->authenticateWithJWT($this->testUser)->graphQL($query);

        // Assert - Estructura completa
        $response->assertJsonStructure([
            'data' => [
                'me' => [
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
            ],
        ]);

        // Verificar datos
        $me = $response->json('data.me');
        $this->assertEquals($this->testUser->id, $me['id']);
        $this->assertEquals($this->testUser->email, $me['email']);
        $this->assertTrue($me['emailVerified']);
        $this->assertEquals('ACTIVE', $me['status']);

        // Verificar perfil
        $this->assertEquals('María', $me['profile']['firstName']);
        $this->assertEquals('García', $me['profile']['lastName']);
        $this->assertEquals('dark', $me['profile']['theme']);
        $this->assertEquals('es', $me['profile']['language']);

        // Verificar roleContexts
        $this->assertCount(1, $me['roleContexts']);
        $this->assertEquals('USER', $me['roleContexts'][0]['roleCode']);
    }

    /**
     * @test
     * Query requiere autenticación
     */
    public function query_requires_authentication(): void
    {
        // Arrange
        $query = '
            query Me {
                me {
                    id
                    email
                }
            }
        ';

        // Act
        $response = $this->graphQL($query);

        // Assert
        $this->assertNotNull($response->json('errors'));
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    /**
     * @test
     * Usuario con múltiples roles retorna todos los roleContexts
     */
    public function user_with_multiple_roles_returns_all_contexts(): void
    {
        // Arrange - Agregar rol PLATFORM_ADMIN
        $this->testUser->assignRole('PLATFORM_ADMIN');

        $query = '
            query Me {
                me {
                    roleContexts {
                        roleCode
                        roleName
                        dashboardPath
                    }
                }
            }
        ';

        // Act
        $response = $this->authenticateWithJWT($this->testUser)->graphQL($query);

        // Assert
        $roleContexts = $response->json('data.me.roleContexts');
        $this->assertCount(2, $roleContexts);

        $roleCodes = collect($roleContexts)->pluck('roleCode')->toArray();
        $this->assertContains('USER', $roleCodes);
        $this->assertContains('PLATFORM_ADMIN', $roleCodes);
    }

    /**
     * @test
     * averageRating es null para usuarios sin tickets resueltos
     */
    public function average_rating_is_null_for_users_without_resolved_tickets(): void
    {
        // Arrange
        $query = '
            query Me {
                me {
                    averageRating
                    resolvedTicketsCount
                }
            }
        ';

        // Act
        $response = $this->authenticateWithJWT($this->testUser)->graphQL($query);

        // Assert
        $me = $response->json('data.me');
        $this->assertNull($me['averageRating']);
        $this->assertEquals(0, $me['resolvedTicketsCount']);
    }
}
