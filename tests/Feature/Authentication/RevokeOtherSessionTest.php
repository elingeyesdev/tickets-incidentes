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
class RevokeOtherSessionTest extends TestCase
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
        $this->loginUser($this->testUser);

        $session2Id = $this->getSessionId($session2['refreshToken']);

        // Act - Desde session1, revocar session2
        $response = $this->withJWT($session1['accessToken'])
            ->deleteJson("/api/auth/sessions/{$session2Id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

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
        $response = $this->withJWT($session['accessToken'], $session['refreshToken'])
            ->deleteJson("/api/auth/sessions/{$sessionId}");

        // Assert - Debe fallar con un error de conflicto
        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'code' => 'CANNOT_REVOKE_CURRENT_SESSION',
            'message' => 'Cannot revoke current session. Use logout mutation instead.',
        ]);
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
        $response = $this->withJWT($testUserSession['accessToken'])
            ->deleteJson("/api/auth/sessions/{$otherUserSessionId}");

        // Assert - Debe fallar con 404 porque la sesión no pertenece al usuario autenticado
        $response->assertStatus(404);
    }

    /**
     * @test
     * No puede revocar sesión que no existe
     */
    public function cannot_revoke_non_existent_session(): void
    {
        // Arrange
        $session = $this->loginUser($this->testUser);
        $fakeSessionId = '00000000-0000-4000-8000-000000000001';

        // Act
        $response = $this->withJWT($session['accessToken'])
            ->deleteJson("/api/auth/sessions/{$fakeSessionId}");

        // Assert
        $response->assertStatus(404);
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
        $response = $this->withJWT($session1['accessToken'])
            ->deleteJson("/api/auth/sessions/{$session2Id}");

        // Assert - Debería ser 404 porque una sesión revocada no se considera "encontrada" para esta operación
        $response->assertStatus(404);
    }

    /**
     * @test
     * RevokeOtherSession requiere autenticación JWT
     */
    public function revoke_other_session_requires_jwt_authentication(): void
    {
        // Act - Sin token
        $response = $this->deleteJson('/api/auth/sessions/any-id');

        // Assert
        $response->assertStatus(401);
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
        $this->loginUser($this->testUser);

        // Act - Desde session1, revocar session2, session3
        $session2Id = $this->getSessionId($session2['refreshToken']);
        $session3Id = $this->getSessionId($session3['refreshToken']);

        $response1 = $this->withJWT($session1['accessToken'])
            ->deleteJson("/api/auth/sessions/{$session2Id}");

        $response2 = $this->withJWT($session1['accessToken'])
            ->deleteJson("/api/auth/sessions/{$session3Id}");

        // Assert - Todas exitosas
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Solo 2 sesiones activas (session1 y la última)
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
        $this->withJWT($session1['accessToken'])
            ->deleteJson("/api/auth/sessions/{$session2Id}");

        // Act - Intentar usar session2 para consultar datos (REST endpoint)
        $response = $this->withJWT($session2['accessToken'])
            ->getJson('/api/auth/status');

        // Assert - Debe fallar porque el token está invalidado
        $response->assertStatus(401);
    }



    /**
     * @test
     * Revocar sesión en escenario real: desde mySessions
     */
    public function revoke_session_in_real_world_scenario(): void
    {
        // Arrange - Usuario con 3 dispositivos
        $laptop = $this->loginUser($this->testUser);
        $this->loginUser($this->testUser);
        $this->loginUser($this->testUser);

        // Paso 1: Desde laptop, listar sesiones (pasa refresh token para identificar la sesión actual)
        $listResponse = $this->withJWT($laptop['accessToken'], $laptop['refreshToken'])
            ->getJson('/api/auth/sessions');

        $sessions = $listResponse->json('sessions');
        $this->assertCount(3, $sessions);

        // Paso 2: Usuario identifica sesión a revocar (una que no sea la actual)
        $phoneSessionId = collect($sessions)
            ->firstWhere('isCurrent', false)['sessionId'];

        // Paso 3: Revocar sesión de phone usando la API REST
        $revokeResponse = $this->withJWT($laptop['accessToken'], $laptop['refreshToken'])
            ->deleteJson("/api/auth/sessions/{$phoneSessionId}");

        $revokeResponse->assertStatus(200);

        // Paso 4: Verificar que ahora solo hay 2 sesiones
        $listResponse2 = $this->withJWT($laptop['accessToken'], $laptop['refreshToken'])
            ->getJson('/api/auth/sessions');

        $listResponse2->assertStatus(200);
        $sessions2 = $listResponse2->json('sessions');
        $this->assertCount(2, $sessions2);
    }

    /**
     * Helper: Login and get tokens
     * NOTE: Con HttpOnly cookies, el refreshToken ahora viene en la cookie, no en el JSON
     */
    private function loginUser(User $user): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password', // Default password from factory
        ]);

        return [
            'accessToken' => $response->json('accessToken'),
            'refreshToken' => $response->getCookie('refresh_token')->getValue(),
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
     * Helper: Add JWT authorization header and optional refresh token
     */
    private function withJWT(string $token, ?string $refreshToken = null): self
    {
        $headers = [
            'Authorization' => "Bearer {$token}",
        ];

        if ($refreshToken) {
            $headers['X-Refresh-Token'] = $refreshToken;
        }

        return $this->withHeaders($headers);
    }


}
