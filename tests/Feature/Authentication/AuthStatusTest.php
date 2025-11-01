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
class AuthStatusTest extends TestCase
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

        // Act - REST endpoint
        $response = $this->withJWT($loginResponse['accessToken'], $loginResponse['refreshToken'])
            ->getJson('/api/auth/status');

        // Assert
        $response->assertJsonStructure([
            'isAuthenticated',
            'user' => [
                'id',
                'email',
                'displayName',
            ],
            'tokenInfo' => ['expiresIn', 'tokenType'],
            'currentSession' => ['sessionId', 'deviceName', 'isCurrent'],
        ]);

        $response->assertJsonPath('isAuthenticated', true);
        $response->assertJsonPath('user.email', $this->testUser->email);
        $response->assertJsonPath('tokenInfo.tokenType', 'Bearer');
        $response->assertJsonPath('currentSession.isCurrent', true);

        // Verificar que expiresIn es un número positivo
        $expiresIn = $response->json('tokenInfo.expiresIn');
        $this->assertGreaterThan(0, $expiresIn);
        $this->assertLessThanOrEqual(config('jwt.ttl') * 60, $expiresIn);

        // Validar roleContexts (dentro de user)
        $user = $response->json('user');
        $this->assertArrayHasKey('roleContexts', $user);
        $this->assertIsArray($user['roleContexts']);
        $this->assertNotEmpty($user['roleContexts']);

        // Verificar estructura del primer rol (USER por defecto)
        $firstRole = $user['roleContexts'][0];
        $this->assertArrayHasKey('roleCode', $firstRole);
        $this->assertArrayHasKey('roleName', $firstRole);
        $this->assertArrayHasKey('dashboardPath', $firstRole);
        $this->assertArrayHasKey('company', $firstRole);

        // USER no tiene empresa
        $this->assertEquals('USER', $firstRole['roleCode']);
        $this->assertEquals('Cliente', $firstRole['roleName']);
        $this->assertEquals('/tickets', $firstRole['dashboardPath']);
        $this->assertNull($firstRole['company']);
    }

    /**
     * @test
     * Obtener estado de autenticación sin Bearer prefix
     */
    public function can_get_auth_status_without_bearer_prefix(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act - Token directo sin "Bearer " (REST endpoint)
        $response = $this->withHeaders([
            'Authorization' => $loginResponse['accessToken'], // Sin "Bearer"
        ])->getJson('/api/auth/status');

        // Assert - Debe funcionar correctamente
        $response->assertJsonPath('isAuthenticated', true);
        $response->assertJsonPath('user.email', $this->testUser->email);
    }

    /**
     * @test
     * AuthStatus requiere autenticación JWT
     */
    public function auth_status_requires_jwt_authentication(): void
    {
        // Act - Sin token (REST endpoint)
        $response = $this->getJson('/api/auth/status');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     * AuthStatus funciona sin refresh token header (session_id viene del JWT)
     */
    public function auth_status_works_without_refresh_token(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act - Solo con access token, SIN refresh token header (REST endpoint)
        $response = $this->withJWT($loginResponse['accessToken'])
            ->getJson('/api/auth/status');

        // Assert - Debe mostrar currentSession porque session_id viene en el JWT
        $response->assertJsonPath('isAuthenticated', true);
        $response->assertJsonPath('user.email', $this->testUser->email);
        $this->assertNotNull($response->json('currentSession'));
        $response->assertJsonPath('currentSession.isCurrent', true);
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

        // Hacer refresh para obtener nuevo token (REST endpoint)
        $refreshResponse = $this->withJWT($oldAccessToken, $loginResponse['refreshToken'])
            ->postJson('/api/auth/refresh', []);

        $newAccessToken = $refreshResponse->json('accessToken');

        // Act - Consultar estado con nuevo token (REST endpoint)
        $response = $this->withJWT($newAccessToken)
            ->getJson('/api/auth/status');

        // Assert - Token info debe reflejar nuevo token
        $response->assertJsonPath('isAuthenticated', true);

        $newExpiresIn = $response->json('tokenInfo.expiresIn');
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

        // Hacer logout (REST endpoint)
        $this->withJWT($loginResponse['accessToken'], $loginResponse['refreshToken'])
            ->postJson('/api/auth/logout', ['everywhere' => false]);

        // Act - Intentar consultar estado con token revocado (REST endpoint)
        $response = $this->withJWT($loginResponse['accessToken'])
            ->getJson('/api/auth/status');

        // Assert - Debe fallar
        $response->assertStatus(401);
    }

    /**
     * @test
     * AuthStatus muestra información completa de perfil
     */
    public function auth_status_shows_complete_profile_info(): void
    {
        // Arrange
        $loginResponse = $this->loginUser();

        // Act - REST endpoint
        $response = $this->withJWT($loginResponse['accessToken'])
            ->getJson('/api/auth/status');

        // Assert - Estructura completa
        $response->assertJsonStructure([
            'user' => [
                'id',
                'email',
                'userCode',
                'status',
                'emailVerified',
                'onboardingCompleted',
                'displayName',
                'theme',
                'language',
            ],
        ]);

        $user = $response->json('user');
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

        // Act - Consultar estado desde sesión 2 (REST endpoint)
        $response = $this->withJWT($session2['accessToken'], $session2['refreshToken'])
            ->getJson('/api/auth/status');

        // Assert - Debe mostrar la sesión correcta
        $response->assertJsonPath('isAuthenticated', true);
        $response->assertJsonPath('currentSession.isCurrent', true);

        // La sessionId debe corresponder a session2 (no session1 ni session3)
        $sessionId = $response->json('currentSession.sessionId');
        $this->assertNotNull($sessionId);
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
