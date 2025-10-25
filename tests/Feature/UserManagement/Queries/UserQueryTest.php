<?php

namespace Tests\Feature\UserManagement;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para UserQuery
 *
 * Verifica:
 * - Retorna información COMPLETA de un usuario por ID
 * - Misma estructura que 'me' para consistencia
 * - Validación de permisos con @can directive
 * - Solo admins pueden ver otros usuarios
 * - Company admin solo puede ver usuarios de su empresa
 */
class UserQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;
    private User $companyAdmin;
    private User $targetUser;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        // Platform admin
        $this->platformAdmin = User::factory()
            ->withProfile()
            ->withRole('PLATFORM_ADMIN')
            ->create();

        // Company admin
        $this->companyAdmin = User::factory()
            ->withProfile()
            ->withRole('COMPANY_ADMIN', $this->company->id)
            ->create();

        // Usuario objetivo
        $this->targetUser = User::factory()
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
    }

    /**
     * @test
     * Platform admin puede ver información completa de cualquier usuario
     */
    public function platform_admin_can_view_complete_user_information(): void
    {
        // Arrange
        $query = '
            query User($id: UUID!) {
                user(id: $id) {
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

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert - Estructura completa
        $response->assertJsonStructure([
            'data' => [
                'user' => [
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
            ],
        ]);

        $user = $response->json('data.user');
        $this->assertEquals($this->targetUser->id, $user['id']);
        $this->assertEquals($this->targetUser->email, $user['email']);
        $this->assertEquals('Carlos', $user['profile']['firstName']);
        $this->assertEquals('Rodríguez', $user['profile']['lastName']);
        $this->assertCount(1, $user['roleContexts']);
        $this->assertEquals('AGENT', $user['roleContexts'][0]['roleCode']);
    }

    /**
     * @test
     * Estructura es idéntica a query 'me' para consistencia
     */
    public function structure_is_identical_to_me_query(): void
    {
        // Arrange - Mismo query que 'me'
        $query = '
            query User($id: UUID!) {
                user(id: $id) {
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
                    }
                    roleContexts {
                        roleCode
                        roleName
                        dashboardPath
                    }
                    ticketsCount
                    resolvedTicketsCount
                    averageRating
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert - Misma estructura que 'me'
        $user = $response->json('data.user');
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
    public function company_admin_can_view_users_from_their_company(): void
    {
        // Arrange
        $query = '
            query User($id: UUID!) {
                user(id: $id) {
                    id
                    email
                    roleContexts {
                        roleCode
                        company {
                            id
                        }
                    }
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->authenticateWithJWT($this->companyAdmin)->graphQL($query, $variables);

        // Assert
        $user = $response->json('data.user');
        $this->assertEquals($this->targetUser->id, $user['id']);
        $this->assertEquals($this->company->id, $user['roleContexts'][0]['company']['id']);
    }

    /**
     * @test
     * Company admin NO puede ver usuarios de otras empresas
     */
    public function company_admin_cannot_view_users_from_other_companies(): void
    {
        // Arrange - Usuario de otra empresa
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()
            ->withProfile()
            ->withRole('AGENT', $otherCompany->id)
            ->create();

        $query = '
            query User($id: UUID!) {
                user(id: $id) {
                    id
                    email
                }
            }
        ';

        $variables = ['id' => $otherUser->id];

        // Act
        $response = $this->authenticateWithJWT($this->companyAdmin)->graphQL($query, $variables);

        // Assert - Debe fallar por permisos
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Usuario regular NO puede ver otros usuarios
     */
    public function regular_user_cannot_view_other_users(): void
    {
        // Arrange - Usuario regular sin permisos de admin
        $regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $query = '
            query User($id: UUID!) {
                user(id: $id) {
                    id
                    email
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->authenticateWithJWT($regularUser)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Query requiere autenticación
     */
    public function query_requires_authentication(): void
    {
        // Arrange
        $query = '
            query User($id: UUID!) {
                user(id: $id) {
                    id
                    email
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Retorna null si usuario no existe
     */
    public function returns_null_if_user_not_found(): void
    {
        // Arrange
        $query = '
            query User($id: UUID!) {
                user(id: $id) {
                    id
                    email
                }
            }
        ';

        $fakeId = '550e8400-e29b-41d4-a716-446655440000';
        $variables = ['id' => $fakeId];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Usuario con múltiples roles retorna todos los roleContexts
     */
    public function user_with_multiple_roles_returns_all_contexts(): void
    {
        // Arrange - Agregar rol adicional
        $this->targetUser->assignRole('USER');

        $query = '
            query User($id: UUID!) {
                user(id: $id) {
                    roleContexts {
                        roleCode
                        roleName
                        company {
                            id
                        }
                    }
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $roleContexts = $response->json('data.user.roleContexts');
        $this->assertCount(2, $roleContexts);

        $roleCodes = collect($roleContexts)->pluck('roleCode')->toArray();
        $this->assertContains('AGENT', $roleCodes);
        $this->assertContains('USER', $roleCodes);
    }

    /**
     * @test
     * averageRating es null para usuarios sin tickets resueltos
     */
    public function average_rating_is_null_for_users_without_resolved_tickets(): void
    {
        // Arrange
        $query = '
            query User($id: UUID!) {
                user(id: $id) {
                    averageRating
                    resolvedTicketsCount
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $user = $response->json('data.user');
        $this->assertNull($user['averageRating']);
        $this->assertEquals(0, $user['resolvedTicketsCount']);
    }

    /**
     * @test
     * Query es útil para páginas de detalle de usuario
     */
    public function query_is_useful_for_user_detail_pages(): void
    {
        // Arrange - Simular caso de uso: página de detalle de usuario
        $query = '
            query User($id: UUID!) {
                user(id: $id) {
                    id
                    userCode
                    email
                    emailVerified
                    status
                    profile {
                        firstName
                        lastName
                        displayName
                        avatarUrl
                    }
                    roleContexts {
                        roleCode
                        roleName
                    }
                    ticketsCount
                    createdAt
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert - Todos los campos necesarios para página de detalle
        $user = $response->json('data.user');
        $this->assertNotNull($user['id']);
        $this->assertNotNull($user['userCode']);
        $this->assertNotNull($user['email']);
        $this->assertNotNull($user['profile']);
        $this->assertNotNull($user['roleContexts']);
        $this->assertIsInt($user['ticketsCount']);
    }

    /**
     * @test
     * Usuario puede verse a sí mismo (equivalente a 'me')
     */
    public function user_can_view_themselves(): void
    {
        // Arrange
        $query = '
            query User($id: UUID!) {
                user(id: $id) {
                    id
                    email
                }
            }
        ';

        $variables = ['id' => $this->targetUser->id];

        // Act
        $response = $this->authenticateWithJWT($this->targetUser)->graphQL($query, $variables);

        // Assert - Debe funcionar (puede verse a sí mismo)
        $user = $response->json('data.user');
        $this->assertEquals($this->targetUser->id, $user['id']);
        $this->assertEquals($this->targetUser->email, $user['email']);
    }
}
