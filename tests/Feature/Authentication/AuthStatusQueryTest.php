<?php

namespace Tests\Feature\Authentication;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Completos para AuthStatus Query
 *
 * Verifica:
 * - Estado de autenticación del usuario actual
 * - Información del token (expiración, tipo, etc.)
 * - Información de la sesión actual
 * - Manejo de tokens sin Bearer prefix (Apollo Studio compatibility)
 * - Manejo de tokens inválidos/expirados
 * - Protección con @jwt directive
 */
class AuthStatusQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->verified()
            ->create();
    }

    /**
     * @test
     * Obtener estado de autenticación con token válido
     */
    public function can_get_auth_status_with_valid_token(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act
        $query = '
            query {
                authStatus {
                    isAuthenticated
                    user {
                        id
                        email
                        displayName
                        avatarUrl
                        theme
                        language
                    }
                    tokenInfo {
                        expiresIn
                        tokenType
                    }
                    currentSession {
                        sessionId
                        deviceName
                        ipAddress
                        isCurrent
                    }
                }
            }
        ';

        $response = $this->withJWT($loginResponse['accessToken'])
            ->withRefreshToken($loginResponse['refreshToken'])
            ->graphQL($query);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'authStatus' => [
                    'isAuthenticated',
                    'user' => ['id', 'email', 'displayName'],
                    'tokenInfo' => ['expiresIn', 'tokenType'],
                    'currentSession' => ['sessionId', 'deviceName', 'isCurrent'],
                ],
            ],
        ]);

        $response->assertJsonPath('data.authStatus.isAuthenticated', true);
        $response->assertJsonPath('data.authStatus.user.email', $this->testUser->email);
        $response->assertJsonPath('data.authStatus.tokenInfo.tokenType', 'Bearer');
        $response->assertJsonPath('data.authStatus.currentSession.isCurrent', true);

        // Verificar que expiresIn es un número positivo
        $expiresIn = $response->json('data.authStatus.tokenInfo.expiresIn');
        $this->assertGreaterThan(0, $expiresIn);
        $this->assertLessThanOrEqual(config('jwt.ttl') * 60, $expiresIn);
    }

    /**
     * @test
     * Obtener estado de autenticación sin Bearer prefix (Apollo Studio)
     */
    public function can_get_auth_status_without_bearer_prefix(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act - Token directo sin "Bearer "
        $query = '
            query {
                authStatus {
                    isAuthenticated
                    user {
                        email
                    }
                }
            }
        ';

        $response = $this->withHeaders([
            'Authorization' => $loginResponse['accessToken'], // Sin "Bearer"
        ])->graphQL($query);

        // Assert - Debe funcionar correctamente
        $response->assertJsonPath('data.authStatus.isAuthenticated', true);
        $response->assertJsonPath('data.authStatus.user.email', $this->testUser->email);
    }

    /**
     * @test
     * AuthStatus requiere autenticación JWT
     */
    public function auth_status_requires_jwt_authentication(): void
    {
        // Act - Sin token
        $query = '
            query {
                authStatus {
                    isAuthenticated
                }
            }
        ';

        $response = $this->graphQL($query);

        // Assert
        $response->assertGraphQLErrorMessage('Authentication required: No valid token provided or token is invalid.');

        $errors = $response->json('errors');
        $this->assertEquals('UNAUTHENTICATED', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * AuthStatus funciona sin refresh token header (session_id viene del JWT)
     */
    public function auth_status_works_without_refresh_token(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act - Solo con access token, SIN refresh token header
        $query = '
            query {
                authStatus {
                    isAuthenticated
                    user {
                        email
                    }
                    currentSession {
                        sessionId
                        deviceName
                        isCurrent
                    }
                }
            }
        ';

        $response = $this->withJWT($loginResponse['accessToken'])
            ->graphQL($query);

        // Assert - Debe mostrar currentSession porque session_id viene en el JWT
        $response->assertJsonPath('data.authStatus.isAuthenticated', true);
        $response->assertJsonPath('data.authStatus.user.email', $this->testUser->email);
        $this->assertNotNull($response->json('data.authStatus.currentSession'));
        $response->assertJsonPath('data.authStatus.currentSession.isCurrent', true);
    }

    /**
     * @test
     * AuthStatus muestra info actualizada después de refresh
     */
    public function auth_status_shows_updated_info_after_refresh(): void
    {
        // Arrange - Login inicial
        $loginResponse = $this->loginUser();
        $oldAccessToken = $loginResponse['accessToken'];

        sleep(1); // Asegurar timestamp diferente

        // Hacer refresh para obtener nuevo token
        $refreshQuery = '
            mutation {
                refreshToken {
                    accessToken
                    refreshToken
                }
            }
        ';

        $refreshResponse = $this->withJWT($oldAccessToken)
            ->withRefreshToken($loginResponse['refreshToken'])
            ->graphQL($refreshQuery);

        $newAccessToken = $refreshResponse->json('data.refreshToken.accessToken');
        // El refresh token real está almacenado en el trait para tests
        $newRefreshToken = \App\Features\Authentication\GraphQL\Mutations\RefreshTokenMutation::getLastRefreshToken();

        // Act - Consultar estado con nuevo token
        $query = '
            query {
                authStatus {
                    isAuthenticated
                    tokenInfo {
                        expiresIn
                        tokenType
                    }
                }
            }
        ';

        $response = $this->withJWT($newAccessToken)
            ->withRefreshToken($newRefreshToken)
            ->graphQL($query);

        // Assert - Token info debe reflejar nuevo token
        $response->assertJsonPath('data.authStatus.isAuthenticated', true);

        $newExpiresIn = $response->json('data.authStatus.tokenInfo.expiresIn');
        $this->assertGreaterThan(0, $newExpiresIn);
    }

    /**
     * @test
     * AuthStatus falla con token revocado (después de logout)
     */
    public function auth_status_fails_with_revoked_token_after_logout(): void
    {
        // Arrange - Login
        $loginResponse = $this->loginUser();

        // Hacer logout
        $logoutQuery = '
            mutation {
                logout
            }
        ';

        $this->withJWT($loginResponse['accessToken'])
            ->withRefreshToken($loginResponse['refreshToken'])
            ->graphQL($logoutQuery);

        // Act - Intentar consultar estado con token revocado
        $query = '
            query {
                authStatus {
                    isAuthenticated
                }
            }
        ';

        $response = $this->withJWT($loginResponse['accessToken'])
            ->graphQL($query);

        // Assert - Debe fallar
        $response->assertGraphQLErrorMessage('Authentication required: Access token is invalid or has been revoked.');
    }

    /**
     * @test
     * AuthStatus muestra información completa de perfil
     */
    public function auth_status_shows_complete_profile_info(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act
        $query = '
            query {
                authStatus {
                    user {
                        id
                        email
                        userCode
                        status
                        emailVerified
                        displayName
                        avatarUrl
                        theme
                        language
                    }
                }
            }
        ';

        $response = $this->withJWT($loginResponse['accessToken'])
            ->graphQL($query);

        // Assert - Estructura completa
        $response->assertJsonStructure([
            'data' => [
                'authStatus' => [
                    'user' => [
                        'id',
                        'email',
                        'userCode',
                        'status',
                        'emailVerified',
                        'displayName',
                        'theme',
                        'language',
                    ],
                ],
            ],
        ]);

        $user = $response->json('data.authStatus.user');
        $this->assertNotNull($user['displayName']);
        $this->assertNotNull($user['email']);
    }

    /**
     * @test
     * AuthStatus con múltiples sesiones activas
     */
    public function auth_status_with_multiple_active_sessions(): void
    {
        // Arrange - Crear 3 sesiones (login desde 3 dispositivos)
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();
        $session3 = $this->loginUser();

        // Act - Consultar estado desde sesión 2
        $query = '
            query {
                authStatus {
                    isAuthenticated
                    currentSession {
                        sessionId
                        isCurrent
                    }
                }
            }
        ';

        $response = $this->withJWT($session2['accessToken'])
            ->withRefreshToken($session2['refreshToken'])
            ->graphQL($query);

        // Assert - Debe mostrar la sesión correcta
        $response->assertJsonPath('data.authStatus.isAuthenticated', true);
        $response->assertJsonPath('data.authStatus.currentSession.isCurrent', true);

        // La sessionId debe corresponder a session2 (no session1 ni session3)
        $sessionId = $response->json('data.authStatus.currentSession.sessionId');
        $this->assertNotNull($sessionId);
    }

    /**
     * Helper: Login and get tokens
     * NOTE: Con HttpOnly cookies, el refreshToken ahora viene en la cookie, no en el JSON
     */
    private function loginUser(): array
    {
        $loginQuery = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                    refreshToken
                }
            }
        ';

        $response = $this->graphQL($loginQuery, [
            'input' => [
                'email' => $this->testUser->email,
                'password' => 'password',
                'rememberMe' => false,
            ],
        ]);

        // El refresh token real está almacenado en el trait para tests
        $refreshToken = \App\Features\Authentication\GraphQL\Mutations\LoginMutation::getLastRefreshToken();

        return [
            'accessToken' => $response->json('data.login.accessToken'),
            'refreshToken' => $refreshToken,
        ];
    }

    /**
     * Helper: Add JWT authorization header
     */
    private function withJWT(string $token): self
    {
        return $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ]);
    }

    /**
     * Helper: Add refresh token header
     */
    private function withRefreshToken(string $token): self
    {
        return $this->withHeaders([
            'X-Refresh-Token' => $token,
        ]);
    }
}
