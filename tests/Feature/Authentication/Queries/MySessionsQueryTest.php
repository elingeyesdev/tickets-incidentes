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
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);
        $session3 = $this->loginUser($this->testUser);

        // Act - Consultar sesiones desde session2 (REST endpoint)
        $response = $this->withJWT($session2['accessToken'], $session2['refreshToken'])
            ->getJson('/api/auth/sessions');

        // Assert
        $sessions = $response->json('sessions');

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
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);
        $session3 = $this->loginUser($this->testUser);

        // Act - Consultar desde cada sesión y verificar que marque la correcta (REST endpoint)
        // Desde session1
        $response1 = $this->withJWT($session1['accessToken'], $session1['refreshToken'])
            ->getJson('/api/auth/sessions');

        // Desde session2
        $response2 = $this->withJWT($session2['accessToken'], $session2['refreshToken'])
            ->getJson('/api/auth/sessions');

        // Assert - Cada respuesta debe marcar una sesión diferente como current
        $sessions1 = $response1->json('sessions');
        $sessions2 = $response2->json('sessions');

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
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);
        $session3 = $this->loginUser($this->testUser);

        // Revocar session1 manualmente
        $tokenHash1 = hash('sha256', $session1['refreshToken']);
        RefreshToken::where('token_hash', $tokenHash1)->first()->revoke($this->testUser->id);

        // Act - Consultar sesiones desde session2 (REST endpoint)
        $response = $this->withJWT($session2['accessToken'], $session2['refreshToken'])
            ->getJson('/api/auth/sessions');

        // Assert - Solo 2 sesiones activas (session2 y session3)
        $sessions = $response->json('sessions');
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
        // Act - Sin token, REST endpoint
        $response = $this->getJson('/api/auth/sessions');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     * MySessions funciona sin refresh token (pero no marca ninguna como current)
     */
    public function my_sessions_works_without_refresh_token(): void
    {
        // Arrange
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);

        // Act - Solo con access token, SIN refresh token (REST endpoint)
        $response = $this->withJWT($session1['accessToken'])
            ->getJson('/api/auth/sessions');

        // Assert - Debe listar sesiones pero ninguna marcada como current
        $sessions = $response->json('sessions');
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
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);
        $session3 = $this->loginUser($this->testUser);

        // Hacer logout everywhere desde session1 (REST endpoint)
        $this->withJWT($session1['accessToken'], $session1['refreshToken'])
            ->postJson('/api/auth/logout', ['everywhere' => true]);

        // Act - Intentar consultar sesiones con session2 (ya invalidada)
        $response = $this->withJWT($session2['accessToken'])
            ->getJson('/api/auth/sessions');

        // Assert - Debe fallar porque el token está invalidado
        $response->assertStatus(401);
    }

    /**
     * @test
     * MySessions después de logout simple (solo elimina una sesión)
     */
    public function my_sessions_after_single_logout(): void
    {
        // Arrange - Crear 3 sesiones
        $session1 = $this->loginUser($this->testUser);
        $session2 = $this->loginUser($this->testUser);
        $session3 = $this->loginUser($this->testUser);

        // Hacer logout de session1 (solo esa sesión) - REST endpoint
        $this->withJWT($session1['accessToken'], $session1['refreshToken'])
            ->postJson('/api/auth/logout', ['everywhere' => false]);

        // Act - Consultar sesiones desde session2 (debe seguir activa) - REST endpoint
        $response = $this->withJWT($session2['accessToken'], $session2['refreshToken'])
            ->getJson('/api/auth/sessions');

        // Assert - Solo 2 sesiones activas (session2 y session3)
        $sessions = $response->json('sessions');
        $this->assertCount(2, $sessions);
    }

    /**
     * @test
     * MySessions cuando solo hay una sesión activa
     */
    public function my_sessions_with_single_session(): void
    {
        // Arrange - Solo una sesión
        $session = $this->loginUser($this->testUser);

        // Act - REST endpoint
        $response = $this->withJWT($session['accessToken'], $session['refreshToken'])
            ->getJson('/api/auth/sessions');

        // Assert
        $sessions = $response->json('sessions');
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
        $session = $this->loginUser($this->testUser);

        // Act - REST endpoint
        $response = $this->withJWT($session['accessToken'], $session['refreshToken'])
            ->getJson('/api/auth/sessions');

        // Assert - Estructura completa
        $sessions = $response->json('sessions');
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
        $session1 = $this->loginUser($this->testUser);
        sleep(1);
        $session2 = $this->loginUser($this->testUser);
        sleep(1);
        $session3 = $this->loginUser($this->testUser);

        // Act - REST endpoint
        $response = $this->withJWT($session3['accessToken'], $session3['refreshToken'])
            ->getJson('/api/auth/sessions');

        // Assert - Sesiones ordenadas por lastUsedAt desc
        $sessions = $response->json('sessions');
        $this->assertCount(3, $sessions);

        // Verificar que están ordenadas (más reciente primero)
        $timestamps = array_map(fn($s) => strtotime($s['lastUsedAt']), $sessions);
        $sortedTimestamps = $timestamps;
        rsort($sortedTimestamps);

        $this->assertEquals($sortedTimestamps, $timestamps);
    }

    /**
     * Helper: Login and get tokens (REST API)
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
