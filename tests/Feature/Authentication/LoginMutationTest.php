<?php

namespace Tests\Feature\Authentication;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserProfile;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Test suite completo para LoginMutation
 *
 * Verifica:
 * - Login exitoso con credenciales válidas
 * - Retorna misma estructura que RegisterMutation (AuthPayload)
 * - Genera access_token y refresh_token válidos
 * - Maneja errores con sistema global de errores
 * - Valida diferentes estados de usuario
 * - Previene login con credenciales inválidas
 */
class LoginMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;
    private string $testPassword = 'SecurePass123!';

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario de prueba con perfil y rol
        $this->testUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create([
                'email' => 'logintest@example.com',
                'password_hash' => Hash::make($this->testPassword),
                'email_verified' => true,
                'status' => UserStatus::ACTIVE,
            ]);
    }

    /**
     * @test
     * Test más importante: Login exitoso retorna AuthPayload completo
     */
    public function user_can_login_successfully(): void
    {
        // Arrange
        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                    refreshToken
                    tokenType
                    expiresIn
                    user {
                        id
                        userCode
                        email
                        emailVerified
                        onboardingCompleted
                        status
                        displayName
                        avatarUrl
                        theme
                        language
                        roleContexts {
                            roleCode
                            roleName
                            dashboardPath
                            company {
                                id
                                companyCode
                                name
                            }
                        }
                    }
                    sessionId
                    loginTimestamp
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'logintest@example.com',
                'password' => $this->testPassword,
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'login' => [
                    'accessToken',
                    'refreshToken',
                    'tokenType',
                    'expiresIn',
                    'user' => [
                        'id',
                        'userCode',
                        'email',
                        'emailVerified',
                        'status',
                        'displayName',
                        'avatarUrl',
                        'theme',
                        'language',
                        'roleContexts' => [
                            '*' => [
                                'roleCode',
                                'roleName',
                                'dashboardPath',
                            ],
                        ],
                    ],
                    'sessionId',
                    'loginTimestamp',
                ],
            ],
        ]);

        $loginData = $response->json('data.login');

        // Verificar tokens
        $this->assertNotEmpty($loginData['accessToken']);
        // RefreshToken ahora devuelve mensaje informativo (no el token real)
        $this->assertEquals('Token stored in secure HttpOnly cookie', $loginData['refreshToken']);
        $this->assertEquals('Bearer', $loginData['tokenType']);
        $this->assertEquals(3600, $loginData['expiresIn']); // 1 hora

        // ✅ NUEVO: Verificar que el refresh token fue almacenado para tests
        $refreshToken = \App\Features\Authentication\GraphQL\Mutations\LoginMutation::getLastRefreshToken();
        $this->assertNotEmpty($refreshToken, 'Refresh token debe estar almacenado para tests');
        $this->assertGreaterThan(40, strlen($refreshToken), 'Refresh token debe tener longitud válida');

        // Verificar datos del usuario
        $this->assertEquals($this->testUser->id, $loginData['user']['id']);
        $this->assertEquals($this->testUser->email, $loginData['user']['email']);
        $this->assertTrue($loginData['user']['emailVerified']);
        $this->assertEquals('ACTIVE', $loginData['user']['status']);

        // Verificar roleContexts (ahora dentro de user)
        $this->assertCount(1, $loginData['user']['roleContexts']);
        $this->assertEquals('USER', $loginData['user']['roleContexts'][0]['roleCode']);
        $this->assertEquals('/tickets', $loginData['user']['roleContexts'][0]['dashboardPath']);

        // Verificar sessionId y timestamp
        $this->assertNotEmpty($loginData['sessionId']);
        $this->assertNotEmpty($loginData['loginTimestamp']);
    }

    /**
     * @test
     * Login falla con credenciales inválidas
     */
    public function cannot_login_with_invalid_credentials(): void
    {
        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'logintest@example.com',
                'password' => 'WrongPassword123!',
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert - Sistema global de errores
        $response->assertGraphQLErrorMessage('Credenciales inválidas. Verifica tu email y contraseña.');

        // Verificar que usa el código de error correcto
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $this->assertEquals('INVALID_CREDENTIALS', $errors[0]['extensions']['code']);
        $this->assertEquals('authentication', $errors[0]['extensions']['category']);
    }

    /**
     * @test
     * Login falla con email que no existe
     */
    public function cannot_login_with_nonexistent_email(): void
    {
        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'nonexistent@example.com',
                'password' => $this->testPassword,
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $response->assertGraphQLErrorMessage('Credenciales inválidas. Verifica tu email y contraseña.');

        $errors = $response->json('errors');
        $this->assertEquals('INVALID_CREDENTIALS', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * Email es case-insensitive (normalizado a lowercase)
     */
    public function email_login_is_case_insensitive(): void
    {
        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    user {
                        email
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'LOGINTEST@EXAMPLE.COM', // Uppercase
                'password' => $this->testPassword,
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert - Debe funcionar
        $response->assertJson([
            'data' => [
                'login' => [
                    'user' => [
                        'email' => 'logintest@example.com', // Normalizado
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     * No puede hacer login con cuenta suspendida
     */
    public function cannot_login_with_suspended_account(): void
    {
        // Arrange - Suspender cuenta
        $this->testUser->update(['status' => UserStatus::SUSPENDED]);

        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'logintest@example.com',
                'password' => $this->testPassword,
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert - Sistema global de errores
        $response->assertGraphQLErrorMessage('Tu cuenta está suspendida. Contacta al administrador.');

        $errors = $response->json('errors');
        $this->assertEquals('ACCOUNT_SUSPENDED', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * Login funciona correctamente sin email verificado
     * (La verificación de email NO bloquea el login)
     */
    public function can_login_without_verified_email(): void
    {
        // Arrange - Email no verificado
        $this->testUser->update(['email_verified' => false]);

        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                    user {
                        emailVerified
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'logintest@example.com',
                'password' => $this->testPassword,
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert - Login exitoso
        $response->assertJson([
            'data' => [
                'login' => [
                    'user' => [
                        'emailVerified' => false,
                    ],
                ],
            ],
        ]);

        $this->assertNotEmpty($response->json('data.login.accessToken'));
    }

    /**
     * @test
     * Validación: email es requerido
     */
    public function email_is_required(): void
    {
        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                }
            }
        ';

        $variables = [
            'input' => [
                'password' => $this->testPassword,
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert - Error de validación de GraphQL (campo requerido)
        $this->assertNotEmpty($response->json('errors'));
    }

    /**
     * @test
     * Validación: password es requerido
     */
    public function password_is_required(): void
    {
        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'logintest@example.com',
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $this->assertNotEmpty($response->json('errors'));
    }

    /**
     * @test
     * Los tokens JWT son válidos y pueden ser decodificados
     */
    public function generated_jwt_tokens_are_valid(): void
    {
        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                    refreshToken
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'logintest@example.com',
                'password' => $this->testPassword,
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $accessToken = $response->json('data.login.accessToken');
        $refreshToken = $response->json('data.login.refreshToken');

        // Verificar formato JWT (3 partes separadas por puntos)
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+$/', $accessToken);

        // RefreshToken ahora es un mensaje informativo (el token real está en cookie)
        $this->assertEquals('Token stored in secure HttpOnly cookie', $refreshToken);

        // Verificar que el token real está almacenado para tests
        $actualRefreshToken = \App\Features\Authentication\GraphQL\Mutations\LoginMutation::getLastRefreshToken();
        $this->assertNotEmpty($actualRefreshToken);
        $this->assertGreaterThan(40, strlen($actualRefreshToken));
    }

    /**
     * @test
     * Login con usuario con múltiples roles retorna todos los roleContexts
     */
    public function login_with_multiple_roles_returns_all_contexts(): void
    {
        // Arrange - Agregar rol PLATFORM_ADMIN al usuario (no requiere empresa)
        $this->testUser->assignRole('PLATFORM_ADMIN');

        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    user {
                        roleContexts {
                            roleCode
                            roleName
                            dashboardPath
                        }
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'logintest@example.com',
                'password' => $this->testPassword,
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $roleContexts = $response->json('data.login.user.roleContexts');

        $this->assertCount(2, $roleContexts);

        $roleCodes = collect($roleContexts)->pluck('roleCode')->toArray();
        $this->assertContains('USER', $roleCodes);
        $this->assertContains('PLATFORM_ADMIN', $roleCodes);
    }

    /**
     * @test
     * Estructura de respuesta es idéntica a RegisterMutation
     */
    public function response_structure_matches_register_mutation(): void
    {
        // Esta es la garantía de que el cliente puede usar el mismo código
        // para manejar las respuestas de login y register

        $query = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                    refreshToken
                    tokenType
                    expiresIn
                    user {
                        id
                        userCode
                        email
                        emailVerified
                        onboardingCompleted
                        status
                        displayName
                        avatarUrl
                        theme
                        language
                        roleContexts {
                            roleCode
                            roleName
                            dashboardPath
                            company {
                                id
                                companyCode
                                name
                            }
                        }
                    }
                    sessionId
                    loginTimestamp
                }
            }
        ';

        $variables = [
            'input' => [
                'email' => 'logintest@example.com',
                'password' => $this->testPassword,
                'rememberMe' => false,
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert - Misma estructura que RegisterMutation
        $response->assertJsonStructure([
            'data' => [
                'login' => [
                    'accessToken',
                    'refreshToken',
                    'tokenType',
                    'expiresIn',
                    'user' => [
                        'roleContexts',
                    ],
                    'sessionId',
                    'loginTimestamp',
                ],
            ],
        ]);

        // Verificar tipos
        $loginData = $response->json('data.login');
        $this->assertIsString($loginData['accessToken']);
        $this->assertIsString($loginData['refreshToken']);
        $this->assertIsString($loginData['tokenType']);
        $this->assertIsInt($loginData['expiresIn']);
        $this->assertIsArray($loginData['user']);
        $this->assertIsArray($loginData['user']['roleContexts']);
        $this->assertIsString($loginData['sessionId']);
        $this->assertIsString($loginData['loginTimestamp']);
    }
}
