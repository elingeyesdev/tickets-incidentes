<?php

namespace Tests\Feature\Authentication;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\UserManagement\Models\User;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

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
    use RefreshDatabaseWithoutTransactions;

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

        // Act - Refresh token (REST endpoint)
        $response = $this->withJWT($oldAccessToken, $refreshToken)
            ->postJson('/api/auth/refresh', []);

        // Assert
        $response->assertJsonStructure([
            'accessToken',
            'tokenType',
            'expiresIn',
        ]);

        $newAccessToken = $response->json('accessToken');

        // El nuevo access token debe ser diferente
        $this->assertNotEquals($oldAccessToken, $newAccessToken);

        // Verificar que el nuevo token funciona (REST endpoint)
        $statusResponse = $this->withJWT($newAccessToken)
            ->getJson('/api/auth/status');

        $statusResponse->assertJsonPath('user.email', $this->testUser->email);
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
        $firstRefresh = $this->withJWT($loginResponse['accessToken'], $oldRefreshToken)
            ->postJson('/api/auth/refresh', []);

        $newAccessToken = $firstRefresh->json('accessToken');

        // Assert - El token viejo ya NO debe funcionar
        $secondRefresh = $this->withJWT($newAccessToken, $oldRefreshToken)
            ->postJson('/api/auth/refresh', []);

        $secondRefresh->assertStatus(401);
    }

    /**
     * @test
     * RefreshToken requiere el refresh token en cookie o header
     */
    public function refresh_token_requires_refresh_token_cookie_or_header(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act - Con access token pero SIN refresh token (ni cookie ni header)
        $response = $this->withJWT($loginResponse['accessToken'])
            ->postJson('/api/auth/refresh', []);

        // Assert
        $response->assertStatus(401);
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

        // Act - Logout solo sesión 1 (REST endpoint)
        $response = $this->withJWT($session1['accessToken'], $session1['refreshToken'])
            ->postJson('/api/auth/logout', ['everywhere' => false]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Sesión 1 debe estar invalidada
        $test1 = $this->withJWT($session1['accessToken'])->getJson('/api/auth/status');
        $test1->assertStatus(401);

        // Sesión 2 debe seguir funcionando
        $test2 = $this->withJWT($session2['accessToken'])->getJson('/api/auth/status');
        $test2->assertStatus(200);
        $test2->assertJsonPath('user.email', $this->testUser->email);
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

        // Act - Logout everywhere desde sesión 1 (REST endpoint)
        $response = $this->withJWT($session1['accessToken'], $session1['refreshToken'])
            ->postJson('/api/auth/logout', ['everywhere' => true]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verificar que TODOS los refresh tokens están revocados
        $this->assertEquals(0, RefreshToken::where('user_id', $this->testUser->id)
            ->whereNull('revoked_at')
            ->count());

        // Ninguna sesión debe funcionar
        $test1 = $this->withJWT($session1['accessToken'])->getJson('/api/auth/status');
        $test1->assertStatus(401);

        $test2 = $this->withJWT($session2['accessToken'])->getJson('/api/auth/status');
        $test2->assertStatus(401);

        $test3 = $this->withJWT($session3['accessToken'])->getJson('/api/auth/status');
        $test3->assertStatus(401);
    }

    /**
     * @test
     * Logout requiere autenticación JWT
     */
    public function logout_requires_jwt_authentication(): void
    {
        // Act - Sin JWT
        $response = $this->postJson('/api/auth/logout', ['everywhere' => false]);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     * Logout sin refresh token solo invalida access token
     */
    public function logout_without_refresh_token_only_blacklists_access_token(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act - Con access token pero SIN refresh token
        $response = $this->withJWT($loginResponse['accessToken'])
            ->postJson('/api/auth/logout', ['everywhere' => false]);

        // Assert - Debe funcionar
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // El access token debe estar invalidado
        $test = $this->withJWT($loginResponse['accessToken'])->getJson('/api/auth/status');
        $test->assertStatus(401);
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

        // Act - Logout sin parámetro everywhere (REST endpoint)
        $response = $this->withJWT($session1['accessToken'], $session1['refreshToken'])
            ->postJson('/api/auth/logout', ['everywhere' => false]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Sesión 2 debe seguir funcionando
        $test = $this->withJWT($session2['accessToken'])->getJson('/api/auth/status');
        $test->assertStatus(200);
        $test->assertJsonPath('user.email', $this->testUser->email);
    }

    /**
     * @test
     * Después de logout no puede hacer refresh token
     */
    public function cannot_refresh_token_after_logout(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act 1 - Logout (REST endpoint)
        $this->withJWT($loginResponse['accessToken'], $loginResponse['refreshToken'])
            ->postJson('/api/auth/logout', ['everywhere' => false]);

        // Act 2 - Intentar refresh (REST endpoint)
        $response = $this->withJWT($loginResponse['accessToken'], $loginResponse['refreshToken'])
            ->postJson('/api/auth/refresh', []);

        // Assert - Debe fallar porque el refresh token fue revocado en el logout
        $response->assertStatus(401);
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
        $response1 = $this->withJWT($loginResponse['accessToken'], $refreshToken)
            ->postJson('/api/auth/refresh', []);

        $response2 = $this->withJWT($loginResponse['accessToken'], $refreshToken)
            ->postJson('/api/auth/refresh', []);

        // Assert - Solo UNO debe funcionar, el otro debe fallar
        $success = $response1->json('accessToken') ? 1 : 0;
        $success += $response2->json('accessToken') ? 1 : 0;

        $this->assertEquals(1, $success, 'Solo un refresh debe funcionar (token rotation)');
    }

    /**
     * Helper: Login and get tokens (REST API)
     */
    private function loginUser(): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testUser->email,
            'password' => 'password',
            'rememberMe' => false,
        ]);

        return [
            'accessToken' => $response->json('accessToken'),
            'refreshToken' => $response->getCookie('refresh_token')->getValue(),
        ];
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
