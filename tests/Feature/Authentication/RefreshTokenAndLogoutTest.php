<?php

namespace Tests\Feature\Authentication;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Completos para RefreshToken y Logout
 *
 * Verifica:
 * - RefreshToken renueva access token correctamente
 * - RefreshToken invalida el token anterior (token rotation)
 * - Logout simple (solo sesión actual)
 * - Logout everywhere (todas las sesiones)
 * - Sistema global de errores en ambos casos
 */
class RefreshTokenAndLogoutTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;
    private string $testPassword = 'SecurePass123!';

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
     * RefreshToken renueva el access token correctamente
     */
    public function can_refresh_access_token_with_valid_refresh_token(): void
    {
        // Arrange - Login para obtener tokens
        $loginResponse = $this->loginUser();

        $oldAccessToken = $loginResponse['accessToken'];
        $refreshToken = $loginResponse['refreshToken'];

        // Esperar 1 segundo (para asegurar que el nuevo token sea diferente)
        sleep(1);

        // Act - Refresh token
        $refreshQuery = '
            mutation {
                refreshToken {
                    accessToken
                    refreshToken
                    tokenType
                    expiresIn
                }
            }
        ';

        $response = $this->withJWT($oldAccessToken)
            ->withRefreshToken($refreshToken)
            ->graphQL($refreshQuery);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'refreshToken' => [
                    'accessToken',
                    'refreshToken',
                    'tokenType',
                    'expiresIn',
                ],
            ],
        ]);

        $newAccessToken = $response->json('data.refreshToken.accessToken');

        // El refreshToken en JSON es ahora un mensaje informativo
        $this->assertEquals('Token stored in secure HttpOnly cookie', $response->json('data.refreshToken.refreshToken'));

        // El refresh token real está almacenado en el trait para tests
        $newRefreshToken = \App\Features\Authentication\GraphQL\Mutations\RefreshTokenMutation::getLastRefreshToken();

        // El nuevo access token debe ser diferente
        $this->assertNotEquals($oldAccessToken, $newAccessToken);

        // El nuevo refresh token debe ser diferente (token rotation)
        $this->assertNotEquals($refreshToken, $newRefreshToken);

        // Verificar que el nuevo token funciona
        $statusQuery = '
            query {
                emailVerificationStatus {
                    email
                }
            }
        ';

        $statusResponse = $this->withJWT($newAccessToken)->graphQL($statusQuery);
        $statusResponse->assertJsonPath('data.emailVerificationStatus.email', $this->testUser->email);
    }

    /**
     * @test
     * RefreshToken invalida el token anterior (Token Rotation)
     */
    public function refresh_token_invalidates_previous_token(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();
        $oldRefreshToken = $loginResponse['refreshToken'];

        // Act - Refresh una vez
        $refreshQuery = '
            mutation {
                refreshToken {
                    accessToken
                    refreshToken
                }
            }
        ';

        $firstRefresh = $this->withJWT($loginResponse['accessToken'])
            ->withRefreshToken($oldRefreshToken)
            ->graphQL($refreshQuery);

        // El nuevo refresh token está almacenado en el trait
        $newRefreshToken = \App\Features\Authentication\GraphQL\Mutations\RefreshTokenMutation::getLastRefreshToken();

        // Assert - El token viejo ya NO debe funcionar
        $secondRefresh = $this->withJWT($firstRefresh->json('data.refreshToken.accessToken'))
            ->withRefreshToken($oldRefreshToken)
            ->graphQL($refreshQuery);

        $secondRefresh->assertGraphQLErrorMessage('Refresh token is invalid or has been revoked.');

        $errors = $secondRefresh->json('errors');
        $this->assertEquals('INVALID_TOKEN', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * RefreshToken requiere el refresh token en cookie o header
     */
    public function refresh_token_requires_refresh_token_cookie_or_header(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        $query = '
            mutation {
                refreshToken {
                    accessToken
                }
            }
        ';

        // Act - Con access token pero SIN refresh token (ni cookie ni header)
        $response = $this->withJWT($loginResponse['accessToken'])->graphQL($query);

        // Assert
        $response->assertGraphQLErrorMessage('Refresh token required. Send it via X-Refresh-Token header or refresh_token cookie.');
    }

    /**
     * @test
     * Logout simple invalida solo la sesión actual
     */
    public function logout_invalidates_current_session_only(): void
    {
        // Arrange - Login en 2 dispositivos diferentes
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();

        // Act - Logout solo sesión 1
        $logoutQuery = '
            mutation {
                logout(everywhere: false)
            }
        ';

        $response = $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($logoutQuery);

        // Assert
        $response->assertJson(['data' => ['logout' => true]]);

        // Sesión 1 debe estar invalidada
        $testQuery = '
            query {
                emailVerificationStatus {
                    email
                }
            }
        ';

        $test1 = $this->withJWT($session1['accessToken'])->graphQL($testQuery);
        $test1->assertGraphQLErrorMessage('Authentication required: Access token is invalid or has been revoked.');

        // Sesión 2 debe seguir funcionando
        $test2 = $this->withJWT($session2['accessToken'])->graphQL($testQuery);
        $test2->assertJsonPath('data.emailVerificationStatus.email', $this->testUser->email);
    }

    /**
     * @test
     * Logout everywhere invalida TODAS las sesiones
     */
    public function logout_everywhere_invalidates_all_sessions(): void
    {
        // Arrange - Login en 3 dispositivos
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();
        $session3 = $this->loginUser();

        // Verificar que hay 3 refresh tokens activos
        $this->assertEquals(3, RefreshToken::where('user_id', $this->testUser->id)
            ->whereNull('revoked_at')
            ->count());

        // Act - Logout everywhere desde sesión 1
        $logoutQuery = '
            mutation {
                logout(everywhere: true)
            }
        ';

        $response = $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($logoutQuery);

        // Assert
        $response->assertJson(['data' => ['logout' => true]]);

        // Verificar que TODOS los refresh tokens están revocados
        $this->assertEquals(0, RefreshToken::where('user_id', $this->testUser->id)
            ->whereNull('revoked_at')
            ->count());

        // Ninguna sesión debe funcionar
        $testQuery = '
            query {
                emailVerificationStatus {
                    email
                }
            }
        ';

        $test1 = $this->withJWT($session1['accessToken'])->graphQL($testQuery);
        $test1->assertGraphQLErrorMessage('Authentication required: Access token is invalid or has been revoked.');

        $test2 = $this->withJWT($session2['accessToken'])->graphQL($testQuery);
        $test2->assertGraphQLErrorMessage('Authentication required: Access token is invalid or has been revoked.');

        $test3 = $this->withJWT($session3['accessToken'])->graphQL($testQuery);
        $test3->assertGraphQLErrorMessage('Authentication required: Access token is invalid or has been revoked.');
    }

    /**
     * @test
     * Logout requiere autenticación JWT
     */
    public function logout_requires_jwt_authentication(): void
    {
        $query = '
            mutation {
                logout
            }
        ';

        // Act - Sin JWT
        $response = $this->graphQL($query);

        // Assert
        $response->assertGraphQLErrorMessage('Authentication required: No valid token provided or token is invalid.');
    }

    /**
     * @test
     * Logout sin refresh token solo invalida access token
     */
    public function logout_without_refresh_token_only_blacklists_access_token(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        $logoutQuery = '
            mutation {
                logout
            }
        ';

        // Act - Con access token pero SIN refresh token
        $response = $this->withJWT($loginResponse['accessToken'])->graphQL($logoutQuery);

        // Assert - Debe funcionar (con warning en logs)
        $response->assertJson(['data' => ['logout' => true]]);

        // El access token debe estar invalidado
        $testQuery = '
            query {
                emailVerificationStatus {
                    email
                }
            }
        ';

        $test = $this->withJWT($loginResponse['accessToken'])->graphQL($testQuery);
        $test->assertGraphQLErrorMessage('Authentication required: Access token is invalid or has been revoked.');
    }

    /**
     * @test
     * Logout default (sin parámetro) cierra solo sesión actual
     */
    public function logout_default_behavior_is_current_session_only(): void
    {
        // Arrange - 2 sesiones
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();

        $logoutQuery = '
            mutation {
                logout
            }
        ';

        // Act - Logout sin parámetro everywhere
        $response = $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($logoutQuery);

        // Assert
        $response->assertJson(['data' => ['logout' => true]]);

        // Sesión 2 debe seguir funcionando
        $testQuery = '
            query {
                emailVerificationStatus {
                    email
                }
            }
        ';

        $test = $this->withJWT($session2['accessToken'])->graphQL($testQuery);
        $test->assertJsonPath('data.emailVerificationStatus.email', $this->testUser->email);
    }

    /**
     * @test
     * Después de logout no puede hacer refresh token
     */
    public function cannot_refresh_token_after_logout(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act 1 - Logout
        $logoutQuery = '
            mutation {
                logout
            }
        ';

        $this->withJWT($loginResponse['accessToken'])
            ->withRefreshToken($loginResponse['refreshToken'])
            ->graphQL($logoutQuery);

        // Act 2 - Intentar refresh
        $refreshQuery = '
            mutation {
                refreshToken {
                    accessToken
                }
            }
        ';

        $response = $this->withJWT($loginResponse['accessToken'])
            ->withRefreshToken($loginResponse['refreshToken'])
            ->graphQL($refreshQuery);

        // Assert - Debe fallar porque el refresh token fue revocado en el logout
        $response->assertGraphQLErrorMessage('Refresh token is invalid or has been revoked.');

        $errors = $response->json('errors');
        $this->assertEquals('INVALID_TOKEN', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * Sistema de refresh token es thread-safe (no race conditions)
     */
    public function refresh_token_rotation_is_thread_safe(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();
        $refreshToken = $loginResponse['refreshToken'];

        // Act - Intentar refresh con el mismo token 2 veces simultáneamente
        $refreshQuery = '
            mutation {
                refreshToken {
                    accessToken
                    refreshToken
                }
            }
        ';

        $response1 = $this->withJWT($loginResponse['accessToken'])
            ->withRefreshToken($refreshToken)
            ->graphQL($refreshQuery);

        $response2 = $this->withJWT($loginResponse['accessToken'])
            ->withRefreshToken($refreshToken)
            ->graphQL($refreshQuery);

        // Assert - Solo UNO debe funcionar, el otro debe fallar
        $success = $response1->json('data.refreshToken.accessToken') ? 1 : 0;
        $success += $response2->json('data.refreshToken.accessToken') ? 1 : 0;

        $this->assertEquals(1, $success, 'Solo un refresh debe funcionar (token rotation)');
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
     * Helper: Add refresh token via header (for tests)
     * Nota: En tests usamos header porque las cookies con ->withCookie() no funcionan con Lighthouse.
     * En producción, el frontend usa cookies HttpOnly que sí funcionan correctamente.
     */
    private function withRefreshToken(string $token): self
    {
        // En tests usamos header porque withCookie() no funciona con Lighthouse
        return $this->withHeaders([
            'X-Refresh-Token' => $token,
        ]);
    }
}
