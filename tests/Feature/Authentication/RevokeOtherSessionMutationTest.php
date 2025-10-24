<?php

namespace Tests\Feature\Authentication;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Completos para RevokeOtherSession Mutation
 *
 * Verifica:
 * - Revocar sesión de otro dispositivo correctamente
 * - No permite revocar su propia sesión actual
 * - Solo puede revocar sesiones propias
 * - No puede revocar sesiones ya revocadas
 * - Sistema global de errores
 * - Protección con @jwt directive
 * - Múltiples escenarios de edge cases
 */
class RevokeOtherSessionMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->verified()
            ->create();

        $this->otherUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->verified()
            ->create();
    }

    /**
     * @test
     * Puede revocar sesión de otro dispositivo exitosamente
     */
    public function can_revoke_other_device_session_successfully(): void
    {
        // Arrange - Crear 3 sesiones
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);
        $session3 = $this->loginUser($this->testUser);

        // Obtener sessionId de session2
        $session2Id = $this->getSessionId($session2['refreshToken']);

        // Act - Desde session1, revocar session2
        $mutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        $response = $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($mutation, ['sessionId' => $session2Id]);

        // Assert
        $response->assertJson(['data' => ['revokeOtherSession' => true]]);

        // Verificar que session2 está revocada
        $revokedSession = RefreshToken::find($session2Id);
        $this->assertTrue($revokedSession->is_revoked);
        $this->assertNotNull($revokedSession->revoked_at);

        // Verificar que session1 y session3 siguen activas
        $this->assertEquals(2, RefreshToken::where('user_id', $this->testUser->id)
            ->whereNull('revoked_at')
            ->count());
    }

    /**
     * @test
     * No puede revocar su propia sesión actual
     */
    public function cannot_revoke_current_session(): void
    {
        // Arrange
        $session = $this->loginUser($this->testUser);
        $sessionId = $this->getSessionId($session['refreshToken']);

        // Act - Intentar revocar sesión actual
        $mutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        $response = $this->withJWT($session['accessToken'])
            ->withRefreshToken($session['refreshToken'])
            ->graphQL($mutation, ['sessionId' => $sessionId]);

        // Assert - Debe fallar
        $response->assertGraphQLErrorMessage('Cannot revoke current session. Use logout mutation instead.');

        $errors = $response->json('errors');
        $this->assertEquals('CANNOT_REVOKE_CURRENT_SESSION', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * No puede revocar sesión de otro usuario
     */
    public function cannot_revoke_session_from_other_user(): void
    {
        // Arrange - Crear sesiones de ambos usuarios
        $testUserSession = $this->loginUser($this->testUser);
        $otherUserSession = $this->loginUser($this->otherUser);

        $otherUserSessionId = $this->getSessionId($otherUserSession['refreshToken']);

        // Act - testUser intenta revocar sesión de otherUser
        $mutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        $response = $this->withJWT($testUserSession['accessToken'])
            ->withRefreshToken($testUserSession['refreshToken'])
            ->graphQL($mutation, ['sessionId' => $otherUserSessionId]);

        // Assert - Debe fallar
        $response->assertGraphQLErrorMessage("Session '{$otherUserSessionId}' does not belong to you.");

        $errors = $response->json('errors');
        $this->assertEquals('SESSION_NOT_FOUND', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * No puede revocar sesión que no existe
     */
    public function cannot_revoke_non_existent_session(): void
    {
        // Arrange
        $session = $this->loginUser($this->testUser);
        // Usar un UUID v4 válido pero que no existe en la BD
        $fakeSessionId = '00000000-0000-4000-8000-000000000001';

        // Act
        $mutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        $response = $this->withJWT($session['accessToken'])
            ->withRefreshToken($session['refreshToken'])
            ->graphQL($mutation, ['sessionId' => $fakeSessionId]);

        // Assert
        $response->assertGraphQLErrorMessage("Session '{$fakeSessionId}' not found.");

        $errors = $response->json('errors');
        $this->assertEquals('SESSION_NOT_FOUND', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * No puede revocar sesión ya revocada
     */
    public function cannot_revoke_already_revoked_session(): void
    {
        // Arrange - Crear 2 sesiones
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);

        $session2Id = $this->getSessionId($session2['refreshToken']);

        // Revocar session2 primero
        RefreshToken::find($session2Id)->revoke($this->testUser->id);

        // Act - Intentar revocar nuevamente
        $mutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        $response = $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($mutation, ['sessionId' => $session2Id]);

        // Assert
        $response->assertGraphQLErrorMessage('Session already revoked.');

        $errors = $response->json('errors');
        $this->assertEquals('SESSION_NOT_FOUND', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * RevokeOtherSession requiere autenticación JWT
     */
    public function revoke_other_session_requires_jwt_authentication(): void
    {
        // Act - Sin token
        $mutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        $response = $this->graphQL($mutation, ['sessionId' => 'any-id']);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');

        $errors = $response->json('errors');
        $this->assertEquals('UNAUTHENTICATED', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * Puede revocar múltiples sesiones sucesivamente
     */
    public function can_revoke_multiple_sessions_successively(): void
    {
        // Arrange - Crear 5 sesiones
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);
        $session3 = $this->loginUser($this->testUser);
        $session4 = $this->loginUser($this->testUser);
        $session5 = $this->loginUser($this->testUser);

        $mutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        // Act - Desde session1, revocar session2, session3, session4
        $session2Id = $this->getSessionId($session2['refreshToken']);
        $session3Id = $this->getSessionId($session3['refreshToken']);
        $session4Id = $this->getSessionId($session4['refreshToken']);

        $response1 = $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($mutation, ['sessionId' => $session2Id]);

        $response2 = $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($mutation, ['sessionId' => $session3Id]);

        $response3 = $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($mutation, ['sessionId' => $session4Id]);

        // Assert - Todas exitosas
        $response1->assertJson(['data' => ['revokeOtherSession' => true]]);
        $response2->assertJson(['data' => ['revokeOtherSession' => true]]);
        $response3->assertJson(['data' => ['revokeOtherSession' => true]]);

        // Solo 2 sesiones activas (session1 y session5)
        $this->assertEquals(2, RefreshToken::where('user_id', $this->testUser->id)
            ->whereNull('revoked_at')
            ->count());
    }

    /**
     * @test
     * Después de revocar sesión, esa sesión no puede hacer nada
     */
    public function revoked_session_cannot_perform_actions(): void
    {
        // Arrange - Crear 2 sesiones
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);

        $session2Id = $this->getSessionId($session2['refreshToken']);

        // Revocar session2 desde session1
        $mutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        $this->withJWT($session1['accessToken'])
            ->withRefreshToken($session1['refreshToken'])
            ->graphQL($mutation, ['sessionId' => $session2Id]);

        // Act - Intentar usar session2 para consultar datos
        $query = '
            query {
                authStatus {
                    isAuthenticated
                }
            }
        ';

        $response = $this->withJWT($session2['accessToken'])
            ->graphQL($query);

        // Assert - Debe fallar porque el token está invalidado
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /**
     * @test
     * RevokeOtherSession funciona sin header X-Refresh-Token (pero con limitaciones)
     */
    public function revoke_other_session_works_without_refresh_token_header(): void
    {
        // Arrange
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);

        $session2Id = $this->getSessionId($session2['refreshToken']);

        // Act - Sin X-Refresh-Token header
        $mutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        $response = $this->withJWT($session1['accessToken'])
            // NO agregar withRefreshToken
            ->graphQL($mutation, ['sessionId' => $session2Id]);

        // Assert - Debe funcionar (no requiere refresh token para revocar otras sesiones)
        $response->assertJson(['data' => ['revokeOtherSession' => true]]);
    }

    /**
     * @test
     * Revocar sesión en escenario real: desde mySessions
     */
    public function revoke_session_in_real_world_scenario(): void
    {
        // Arrange - Usuario con 3 dispositivos
        $laptop = $this->loginUser($this->testUser);
        $phone = $this->loginUser($this->testUser);
        $tablet = $this->loginUser($this->testUser);

        // Paso 1: Desde laptop, listar sesiones
        $listQuery = '
            query {
                mySessions {
                    sessionId
                    deviceName
                    isCurrent
                }
            }
        ';

        $listResponse = $this->withJWT($laptop['accessToken'])
            ->withRefreshToken($laptop['refreshToken'])
            ->graphQL($listQuery);

        $sessions = $listResponse->json('data.mySessions');
        $this->assertCount(3, $sessions);

        // Paso 2: Usuario identifica sesión de phone (no marcada como current)
        $phoneSessionId = collect($sessions)
            ->firstWhere('isCurrent', false)['sessionId'];

        // Paso 3: Revocar sesión de phone
        $revokeMutation = '
            mutation RevokeOtherSession($sessionId: String!) {
                revokeOtherSession(sessionId: $sessionId)
            }
        ';

        $revokeResponse = $this->withJWT($laptop['accessToken'])
            ->withRefreshToken($laptop['refreshToken'])
            ->graphQL($revokeMutation, ['sessionId' => $phoneSessionId]);

        // Assert
        $revokeResponse->assertJson(['data' => ['revokeOtherSession' => true]]);

        // Paso 4: Verificar que ahora solo hay 2 sesiones
        $listResponse2 = $this->withJWT($laptop['accessToken'])
            ->withRefreshToken($laptop['refreshToken'])
            ->graphQL($listQuery);

        $sessions2 = $listResponse2->json('data.mySessions');
        $this->assertCount(2, $sessions2);
    }

    /**
     * Helper: Login and get tokens
     * NOTE: Con HttpOnly cookies, el refreshToken ahora viene en la cookie, no en el JSON
     */
    private function loginUser(User $user): array
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
                'email' => $user->email,
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
     * Helper: Get session ID from refresh token
     */
    private function getSessionId(string $refreshToken): string
    {
        $tokenHash = hash('sha256', $refreshToken);
        $session = RefreshToken::where('token_hash', $tokenHash)->first();

        if (!$session) {
            throw new \Exception("Session not found for refresh token. Token may be invalid.");
        }

        return $session->id;
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
