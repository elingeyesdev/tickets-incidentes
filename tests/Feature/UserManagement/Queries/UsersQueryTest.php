<?php

namespace Tests\Feature\UserManagement;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para UsersQuery
 *
 * Verifica:
 * - Lista paginada de usuarios con filtros
 * - Solo accesible por PLATFORM_ADMIN o COMPANY_ADMIN
 * - Filtros avanzados (search, status, role, company, etc.)
 * - Ordenamiento
 * - Company admin solo ve usuarios de su empresa
 */
class UsersQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;
    private User $companyAdmin;
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
    }

    /**
     * @test
     * Platform admin puede listar todos los usuarios
     */
    public function platform_admin_can_list_all_users(): void
    {
        // Arrange - Crear varios usuarios
        User::factory()
            ->count(5)
            ->withProfile()
            ->withRole('USER')
            ->create();

        $query = '
            query Users($page: Int, $first: Int) {
                users(page: $page, first: $first) {
                    data {
                        id
                        userCode
                        email
                        status
                        profile {
                            firstName
                            lastName
                            displayName
                        }
                        roleContexts {
                            roleCode
                            roleName
                        }
                    }
                    paginatorInfo {
                        total
                        perPage
                        currentPage
                        lastPage
                        hasMorePages
                    }
                }
            }
        ';

        $variables = [
            'page' => 1,
            'first' => 15,
        ];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'users' => [
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
                    'paginatorInfo' => [
                        'total',
                        'perPage',
                        'currentPage',
                        'lastPage',
                        'hasMorePages',
                    ],
                ],
            ],
        ]);

        $usersData = $response->json('data.users');
        $this->assertGreaterThanOrEqual(5, $usersData['paginatorInfo']['total']);
        $this->assertFalse($usersData['paginatorInfo']['hasMorePages']);
    }

    /**
     * @test
     * Puede filtrar por búsqueda de texto (email y nombre)
     */
    public function can_filter_by_search_text(): void
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

        $query = '
            query Users($filters: UserFilters) {
                users(filters: $filters) {
                    data {
                        email
                        profile {
                            firstName
                        }
                    }
                    paginatorInfo {
                        total
                    }
                }
            }
        ';

        $variables = [
            'filters' => [
                'search' => 'María',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $users = $response->json('data.users.data');
        $this->assertCount(1, $users);
        $this->assertEquals('maria@example.com', strtolower($users[0]['email']));
    }

    /**
     * @test
     * Puede filtrar por estado
     */
    public function can_filter_by_status(): void
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

        $query = '
            query Users($filters: UserFilters) {
                users(filters: $filters) {
                    data {
                        status
                    }
                }
            }
        ';

        $variables = [
            'filters' => [
                'status' => 'SUSPENDED',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $users = $response->json('data.users.data');
        $this->assertGreaterThanOrEqual(1, count($users));
        foreach ($users as $user) {
            $this->assertEquals('SUSPENDED', $user['status']);
        }
    }

    /**
     * @test
     * Puede filtrar por rol
     */
    public function can_filter_by_role(): void
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

        $query = '
            query Users($filters: UserFilters) {
                users(filters: $filters) {
                    data {
                        roleContexts {
                            roleCode
                        }
                    }
                }
            }
        ';

        $variables = [
            'filters' => [
                'role' => 'AGENT',
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $users = $response->json('data.users.data');
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
    public function company_admin_only_sees_users_from_their_company(): void
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

        $query = '
            query Users {
                users {
                    data {
                        email
                        roleContexts {
                            company {
                                id
                            }
                        }
                    }
                }
            }
        ';

        // Act
        $response = $this->authenticateWithJWT($this->companyAdmin)->graphQL($query);

        // Assert
        $users = $response->json('data.users.data');
        $emails = collect($users)->pluck('email')->toArray();

        $this->assertContains('agent1@company.com', $emails);
        $this->assertNotContains('agent2@othercompany.com', $emails);
    }

    /**
     * @test
     * Puede ordenar por diferentes campos
     */
    public function can_order_by_different_fields(): void
    {
        // Arrange
        User::factory()
            ->count(3)
            ->withProfile()
            ->withRole('USER')
            ->create();

        $query = '
            query Users($orderBy: [UserOrderBy!]) {
                users(orderBy: $orderBy) {
                    data {
                        email
                        createdAt
                    }
                }
            }
        ';

        $variables = [
            'orderBy' => [
                ['field' => 'CREATED_AT', 'order' => 'ASC'],
            ],
        ];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $users = $response->json('data.users.data');
        $this->assertGreaterThanOrEqual(3, count($users));

        // Verificar que están ordenados ascendentemente por createdAt
        $timestamps = collect($users)->pluck('createdAt')->toArray();
        $this->assertEquals($timestamps, collect($timestamps)->sort()->values()->toArray());
    }

    /**
     * @test
     * Respeta límite máximo de 50 por página
     */
    public function respects_maximum_limit_of_50_per_page(): void
    {
        // Arrange
        $query = '
            query Users($first: Int) {
                users(first: $first) {
                    paginatorInfo {
                        perPage
                    }
                }
            }
        ';

        $variables = [
            'first' => 100, // Intentar más de 50
        ];

        // Act
        $response = $this->authenticateWithJWT($this->platformAdmin)->graphQL($query, $variables);

        // Assert
        $perPage = $response->json('data.users.paginatorInfo.perPage');
        $this->assertLessThanOrEqual(50, $perPage);
    }

    /**
     * @test
     * Query requiere ser admin (PLATFORM_ADMIN o COMPANY_ADMIN)
     */
    public function query_requires_admin_role(): void
    {
        // Arrange - Usuario regular sin permisos
        $regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create();

        $query = '
            query Users {
                users {
                    data {
                        id
                    }
                }
            }
        ';

        // Act
        $response = $this->authenticateWithJWT($regularUser)->graphQL($query);

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
            query Users {
                users {
                    data {
                        id
                    }
                }
            }
        ';

        // Act
        $response = $this->graphQL($query);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }
}
