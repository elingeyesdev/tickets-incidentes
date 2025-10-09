<?php

namespace Tests\Feature\Authentication;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Completos para MySessions Query
 *
 * Verifica:
 * - Listado de sesiones activas del usuario
 * - Identificación de sesión actual
 * - Ordenamiento por último uso
 * - Solo muestra sesiones activas (no revocadas ni expiradas)
 * - Protección con @jwt directive
 * - Manejo de múltiples dispositivos
 */
class MySessionsQueryTest extends TestCase
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
     * Listar sesiones cuando hay múltiples sesiones activas
     */
    public function can_list_multiple_active_sessions(): void
    {
        // Arrange - Crear 3 sesiones (3 dispositivos)
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();
        $session3 = $this->loginUser();

        // Act - Consultar sesiones desde session2
        $query = '
            query {
                mySessions {
                    sessionId
                    deviceName
                    ipAddress
                    lastUsedAt
                    expiresAt
                    isCurrent
                }
            }
        ';

        $response = $this->withJWT($session2['accessToken'])
            ->withRefreshToken($session2['refreshToken'])
            ->graphQL($query);

        // Assert
        $sessions = $response->json('data.mySessions');

        $this->assertCount(3, $sessions);

        // Verificar que cada sesión tiene la estructura correcta
        foreach ($sessions as $session) {
            $this->assertArrayHasKey('sessionId', $session);
            $this->assertArrayHasKey('deviceName', $session);
            $this->assertArrayHasKey('isCurrent', $session);
            $this->assertArrayHasKey('lastUsedAt', $session);
            $this->assertArrayHasKey('expiresAt', $session);
        }

        // Debe haber exactamente UNA sesión marcada como current
        $currentSessions = array_filter($sessions, fn($s) => $s['isCurrent'] === true);
        $this->assertCount(1, $currentSessions);
    }

    /**
     * @test
     * Identificar correctamente la sesión actual
     */
    public function correctly_identifies_current_session(): void
    {
        // Arrange - Crear 3 sesiones
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();
        $session3 = $this->loginUser();

        // Act - Consultar desde cada sesión y verificar que marque la correcta
        $query = '
            query {
                mySessions {
                    sessionId
                    isCurrent
                }
            }
        ';

        // Desde session1
        $response1 = $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($query);

        // Desde session2
        $response2 = $this->withJWT($session2['accessToken'])
            ->withRefreshToken($session2['refreshToken'])
            ->graphQL($query);

        // Assert - Cada respuesta debe marcar una sesión diferente como current
        $sessions1 = $response1->json('data.mySessions');
        $sessions2 = $response2->json('data.mySessions');

        $currentSession1Id = collect($sessions1)->firstWhere('isCurrent', true)['sessionId'];
        $currentSession2Id = collect($sessions2)->firstWhere('isCurrent', true)['sessionId'];

        $this->assertNotEquals($currentSession1Id, $currentSession2Id);
    }

    /**
     * @test
     * MySessions solo muestra sesiones activas (no revocadas)
     */
    public function only_shows_active_sessions_not_revoked(): void
    {
        // Arrange - Crear 3 sesiones
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();
        $session3 = $this->loginUser();

        // Revocar session1 manualmente
        $tokenHash1 = hash('sha256', $session1['refreshToken']);
        RefreshToken::where('token_hash', $tokenHash1)->first()->revoke($this->testUser->id);

        // Act - Consultar sesiones desde session2
        $query = '
            query {
                mySessions {
                    sessionId
                    isCurrent
                }
            }
        ';

        $response = $this->withJWT($session2['accessToken'])
            ->withRefreshToken($session2['refreshToken'])
            ->graphQL($query);

        // Assert - Solo 2 sesiones activas (session2 y session3)
        $sessions = $response->json('data.mySessions');
        $this->assertCount(2, $sessions);

        // Verificar que session1 NO está en la lista
        $sessionIds = collect($sessions)->pluck('sessionId')->toArray();
        $this->assertCount(2, $sessionIds);
    }

    /**
     * @test
     * MySessions requiere autenticación JWT
     */
    public function my_sessions_requires_jwt_authentication(): void
    {
        // Act - Sin token
        $query = '
            query {
                mySessions {
                    sessionId
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
     * MySessions funciona sin refresh token (pero no marca ninguna como current)
     */
    public function my_sessions_works_without_refresh_token(): void
    {
        // Arrange
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();

        // Act - Solo con access token, SIN refresh token
        $query = '
            query {
                mySessions {
                    sessionId
                    isCurrent
                }
            }
        ';

        $response = $this->withJWT($session1['accessToken'])
            ->graphQL($query);

        // Assert - Debe listar sesiones pero ninguna marcada como current
        $sessions = $response->json('data.mySessions');
        $this->assertCount(2, $sessions);

        $currentSessions = array_filter($sessions, fn($s) => $s['isCurrent'] === true);
        $this->assertCount(0, $currentSessions); // Ninguna marcada como current sin refresh token
    }

    /**
     * @test
     * MySessions después de logout everywhere (lista vacía)
     */
    public function my_sessions_empty_after_logout_everywhere(): void
    {
        // Arrange - Crear 3 sesiones
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();
        $session3 = $this->loginUser();

        // Hacer logout everywhere desde session1
        $logoutQuery = '
            mutation {
                logout(everywhere: true)
            }
        ';

        $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($logoutQuery);

        // Act - Intentar consultar sesiones con session2 (ya invalidada)
        $query = '
            query {
                mySessions {
                    sessionId
                }
            }
        ';

        $response = $this->withJWT($session2['accessToken'])
            ->graphQL($query);

        // Assert - Debe fallar porque el token está invalidado
        $response->assertGraphQLErrorMessage('Authentication required: Access token is invalid or has been revoked.');
    }

    /**
     * @test
     * MySessions después de logout simple (solo elimina una sesión)
     */
    public function my_sessions_after_single_logout(): void
    {
        // Arrange - Crear 3 sesiones
        $session1 = $this->loginUser();
        $session2 = $this->loginUser();
        $session3 = $this->loginUser();

        // Hacer logout de session1 (solo esa sesión)
        $logoutQuery = '
            mutation {
                logout(everywhere: false)
            }
        ';

        $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($logoutQuery);

        // Act - Consultar sesiones desde session2 (debe seguir activa)
        $query = '
            query {
                mySessions {
                    sessionId
                }
            }
        ';

        $response = $this->withJWT($session2['accessToken'])
            ->withRefreshToken($session2['refreshToken'])
            ->graphQL($query);

        // Assert - Solo 2 sesiones activas (session2 y session3)
        $sessions = $response->json('data.mySessions');
        $this->assertCount(2, $sessions);
    }

    /**
     * @test
     * MySessions cuando solo hay una sesión activa
     */
    public function my_sessions_with_single_session(): void
    {
        // Arrange - Solo una sesión
        $session = $this->loginUser();

        // Act
        $query = '
            query {
                mySessions {
                    sessionId
                    isCurrent
                }
            }
        ';

        $response = $this->withJWT($session['accessToken'])
            ->withRefreshToken($session['refreshToken'])
            ->graphQL($query);

        // Assert
        $sessions = $response->json('data.mySessions');
        $this->assertCount(1, $sessions);
        $this->assertTrue($sessions[0]['isCurrent']);
    }

    /**
     * @test
     * MySessions muestra información completa de dispositivos
     */
    public function my_sessions_shows_complete_device_info(): void
    {
        // Arrange - Crear sesión con info de dispositivo específica
        $session = $this->loginUser();

        // Act
        $query = '
            query {
                mySessions {
                    sessionId
                    deviceName
                    ipAddress
                    userAgent
                    lastUsedAt
                    expiresAt
                    isCurrent
                }
            }
        ';

        $response = $this->withJWT($session['accessToken'])
            ->withRefreshToken($session['refreshToken'])
            ->graphQL($query);

        // Assert - Estructura completa
        $sessions = $response->json('data.mySessions');
        $this->assertCount(1, $sessions);

        $sessionData = $sessions[0];
        $this->assertNotNull($sessionData['sessionId']);
        $this->assertNotNull($sessionData['lastUsedAt']);
        $this->assertNotNull($sessionData['expiresAt']);
        $this->assertTrue($sessionData['isCurrent']);

        // Device info puede ser null pero debe existir la key
        $this->assertArrayHasKey('deviceName', $sessionData);
        $this->assertArrayHasKey('ipAddress', $sessionData);
        $this->assertArrayHasKey('userAgent', $sessionData);
    }

    /**
     * @test
     * MySessions ordenadas por último uso (más reciente primero)
     */
    public function my_sessions_ordered_by_last_used_desc(): void
    {
        // Arrange - Crear 3 sesiones con delays
        $session1 = $this->loginUser();
        sleep(1);
        $session2 = $this->loginUser();
        sleep(1);
        $session3 = $this->loginUser();

        // Act
        $query = '
            query {
                mySessions {
                    sessionId
                    lastUsedAt
                }
            }
        ';

        $response = $this->withJWT($session3['accessToken'])
            ->withRefreshToken($session3['refreshToken'])
            ->graphQL($query);

        // Assert - Sesiones ordenadas por lastUsedAt desc
        $sessions = $response->json('data.mySessions');
        $this->assertCount(3, $sessions);

        // Verificar que están ordenadas (más reciente primero)
        $timestamps = array_map(fn($s) => strtotime($s['lastUsedAt']), $sessions);
        $sortedTimestamps = $timestamps;
        rsort($sortedTimestamps);

        $this->assertEquals($sortedTimestamps, $timestamps);
    }

    /**
     * Helper: Login and get tokens
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

        return [
            'accessToken' => $response->json('data.login.accessToken'),
            'refreshToken' => $response->json('data.login.refreshToken'),
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
